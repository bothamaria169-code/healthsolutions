<?php
declare(strict_types=1);

/**
 * Phola additions on top of the CureMeds codebase:
 *   - self-applying database upgrades (no SQL to import on the live server)
 *   - delivery: The Courier Guy (door, locker, pick-up point, kiosk), SADC bus/taxi depots and DHL worldwide
 *   - the daily wellness quote (dashboard + optional morning email)
 *   - health-news RSS import
 *   - Google and Facebook sign-in
 * Loaded at the end of includes/functions.php.
 */

// ── Database upgrades ────────────────────────────────────────────────────
// Each step runs once, in order; the number reached is kept in settings as
// `schema_version`. Steps only ever add (tables, columns, rows), so running a
// new deploy against the live database is always safe.
const PHOLA_SCHEMA_VERSION = 2;

function phola_migrate(): void
{
    try {
        $current = (int) get_setting('schema_version', '0');
    } catch (Throwable) {
        return; // no database yet — the install screen in config/db.php handles that
    }
    if ($current >= PHOLA_SCHEMA_VERSION) {
        return;
    }

    $pdo = db();
    $hasCol = function (string $table, string $col) use ($pdo): bool {
        $s = $pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c');
        $s->execute([':t' => $table, ':c' => $col]);
        return (int) $s->fetchColumn() > 0;
    };
    $addCol = function (string $table, string $col, string $def) use ($pdo, $hasCol): void {
        if (!$hasCol($table, $col)) {
            $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$col` $def");
        }
    };

    try {
        if ($current < 1) {
            $addCol('products', 'header_image', 'VARCHAR(500) DEFAULT NULL AFTER `featured_image`');
            $addCol('users', 'oauth_provider', "VARCHAR(20) DEFAULT NULL");
            $addCol('users', 'oauth_id', "VARCHAR(100) DEFAULT NULL");
            $addCol('users', 'daily_quotes', 'TINYINT(1) NOT NULL DEFAULT 1');
            $addCol('users', 'last_quote_on', 'DATE DEFAULT NULL');
            $addCol('enquiries', 'delivery', 'TEXT DEFAULT NULL');

            $pdo->exec("CREATE TABLE IF NOT EXISTS `rss_sources` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(120) NOT NULL,
                `url` VARCHAR(500) NOT NULL,
                `is_active` TINYINT(1) NOT NULL DEFAULT 1,
                `last_fetched_at` DATETIME DEFAULT NULL,
                `last_error` VARCHAR(255) DEFAULT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            $pdo->exec("CREATE TABLE IF NOT EXISTS `health_news` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `source_id` INT UNSIGNED DEFAULT NULL,
                `title` VARCHAR(300) NOT NULL,
                `url` VARCHAR(700) NOT NULL,
                `url_hash` CHAR(40) NOT NULL,
                `summary` VARCHAR(400) DEFAULT NULL,
                `image` VARCHAR(700) DEFAULT NULL,
                `published_at` DATETIME DEFAULT NULL,
                `is_hidden` TINYINT(1) NOT NULL DEFAULT 0,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_hash` (`url_hash`),
                KEY `idx_pub` (`published_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            if ((int) $pdo->query('SELECT COUNT(*) FROM rss_sources')->fetchColumn() === 0) {
                $ins = $pdo->prepare('INSERT INTO rss_sources (name, url) VALUES (:n, :u)');
                foreach ([
                    ['Health-e News (South Africa)', 'https://health-e.org.za/feed/'],
                    ['World Health Organization', 'https://www.who.int/rss-feeds/news-english.xml'],
                    ['ScienceDaily — Health & Medicine', 'https://www.sciencedaily.com/rss/health_medicine.xml'],
                    ['Medical Xpress', 'https://medicalxpress.com/rss-feed/'],
                ] as [$n, $u]) {
                    $ins->execute([':n' => $n, ':u' => $u]);
                }
            }

            // Chronic-condition articles for the Learn hub (skipped if the slug exists).
            $author = (int) ($pdo->query("SELECT id FROM users WHERE role = 'admin' ORDER BY id LIMIT 1")->fetchColumn() ?: 1);
            $ins = $pdo->prepare('INSERT IGNORE INTO blogs (title, slug, image, excerpt, content, category, author_id, read_mins, is_featured)
                                  VALUES (:t, :s, :i, :x, :c, :cat, :a, :m, 1)');
            foreach (require __DIR__ . '/content/chronic-articles.php' as $a) {
                $ins->execute([':t' => $a['title'], ':s' => $a['slug'], ':i' => $a['image'], ':x' => $a['excerpt'],
                               ':c' => $a['html'], ':cat' => 'Chronic conditions', ':a' => $author, ':m' => $a['mins']]);
            }

            // The top-bar message; Admin → Settings changes it.
            foreach (['announcement' => 'Courier Guy delivery nationwide: door, locker, pick-up point or kiosk · SADC via bus & taxi depots · DHL worldwide · WhatsApp +27 63 043 7385'] as $k => $v) {
                if (get_setting($k) === null || $k === 'announcement') {
                    set_setting($k, $v);
                }
            }
        }
        if ($current < 2) {
            $pdo->exec("DELETE FROM settings WHERE key_name IN ('tcg_door_fee','tcg_locker_fee','tcg_point_fee','tcg_kiosk_fee','sadc_fee_note')");
            set_setting('announcement', 'Courier Guy delivery nationwide: door, locker, pick-up point or kiosk · SADC via bus & taxi depots · DHL worldwide · WhatsApp +27 63 043 7385');
        }
        set_setting('schema_version', (string) PHOLA_SCHEMA_VERSION);
    } catch (Throwable $e) {
        error_log('Phola migration failed: ' . $e->getMessage());
    }
}

// ── Delivery ─────────────────────────────────────────────────────────────
/** SADC countries we send to by bus or taxi, to the customer's nearest depot. */
const SADC_COUNTRIES = ['Botswana', 'Eswatini', 'Lesotho', 'Namibia', 'Zimbabwe', 'Mozambique', 'Zambia', 'Malawi', 'Angola', 'DR Congo', 'Tanzania'];

/**
 * Every way an order can reach the customer. No prices here on purpose: the
 * delivery cost is confirmed with the customer on WhatsApp with the order.
 * key => label, by, icon, blurb, time, field (what we need to know), region (za|sadc|intl)
 */
function delivery_options(): array
{
    return [
        'tcg_door'   => ['label' => 'Door-to-door', 'by' => 'The Courier Guy', 'icon' => 'fa-house-chimney', 'region' => 'za', 'time' => '2–4 working days',
                         'blurb' => 'Delivered to your home or office.', 'field' => 'Street address, suburb, city and postal code'],
        'tcg_locker' => ['label' => 'Locker', 'by' => 'The Courier Guy · Pudo', 'icon' => 'fa-box-archive', 'region' => 'za', 'time' => '2–4 working days',
                         'blurb' => 'Collect any time from a 24/7 smart locker near you.', 'field' => 'Locker name or code (e.g. Pudo, Engen Rivonia)'],
        'tcg_point'  => ['label' => 'Pick-up point', 'by' => 'The Courier Guy', 'icon' => 'fa-store', 'region' => 'za', 'time' => '2–4 working days',
                         'blurb' => 'Collect from a partner store close to home.', 'field' => 'Pick-up point name and town'],
        'tcg_kiosk'  => ['label' => 'Kiosk', 'by' => 'The Courier Guy', 'icon' => 'fa-shop', 'region' => 'za', 'time' => '2–4 working days',
                         'blurb' => 'Collect from a Courier Guy kiosk in a mall or garage.', 'field' => 'Kiosk name and town'],
        'sadc_depot' => ['label' => 'Bus / taxi depot', 'by' => 'SADC countries', 'icon' => 'fa-bus', 'region' => 'sadc', 'time' => '2–5 days',
                         'blurb' => 'Sent with a trusted cross-border bus or taxi to the depot nearest you.', 'field' => 'Nearest depot / town (e.g. Harare Roadport)'],
        'dhl_intl'   => ['label' => 'International', 'by' => 'DHL Express', 'icon' => 'fa-plane-departure', 'region' => 'intl', 'time' => '3–7 working days',
                         'blurb' => 'Door-to-door anywhere in the world, tracked all the way.', 'field' => 'Full delivery address, city and postal code'],
    ];
}

/** Delivery is always confirmed on WhatsApp, so there is no fee to add up on the website. */
function delivery_fee(string $method, float $subtotal): ?float
{
    return null;
}

/** "Roodepoort → Harare" style suggestions for the basket's depot field. */
function sadc_depot_suggestions(): array
{
    $out = [];
    foreach (require __DIR__ . '/content/sadc-depots.php' as $country => [, $routes]) {
        foreach ($routes as [, $to]) {
            $out[$to] = $country;
        }
    }
    return $out;
}

// ── Daily wellness quote ─────────────────────────────────────────────────
function daily_quote(?string $date = null): string
{
    $quotes = require __DIR__ . '/content/daily-quotes.php';
    $day    = (int) date('z', strtotime($date ?? 'today'));
    return $quotes[$day % count($quotes)];
}

/** Morning email with the day's quote, once per customer per day (cron). */
function send_daily_quotes(int $limit = 150): array
{
    if ((int) date('G') < 7) {
        return ['sent' => 0, 'skipped' => 'before 07:00'];
    }
    $today = date('Y-m-d');
    $stmt = db()->prepare("SELECT id, name, email FROM users
        WHERE role IN ('customer','reseller') AND is_active = 1 AND daily_quotes = 1 AND email IS NOT NULL AND email <> ''
          AND (last_quote_on IS NULL OR last_quote_on < :d) ORDER BY id LIMIT " . $limit);
    $stmt->execute([':d' => $today]);
    $quote = daily_quote();
    $sent  = 0;
    $mark  = db()->prepare('UPDATE users SET last_quote_on = :d WHERE id = :id');
    foreach ($stmt->fetchAll() as $u) {
        $first = e(explode(' ', (string) $u['name'])[0]);
        $html  = '<p>Good morning ' . $first . ',</p>'
               . '<blockquote style="margin:18px 0;padding:16px 20px;border-left:4px solid #8CC63F;background:#EAF4E2;border-radius:8px;font-size:18px;color:#0F3D24;">' . e($quote) . '</blockquote>'
               . '<p><a href="' . SITE_URL . '/dashboards/customer/index.php" style="display:inline-block;background:#2E8B3E;color:#fff;padding:11px 22px;border-radius:999px;font-weight:700;">Open my tracker</a></p>'
               . '<p style="font-size:12px;color:#7D8E83;">Don\'t want the daily quote? Switch it off under <a href="' . SITE_URL . '/dashboards/customer/account.php">My details</a>.</p>';
        // Mark first: a slow or failing mail server must never cause a double send.
        $mark->execute([':d' => $today, ':id' => $u['id']]);
        if (Mailer::send((string) $u['email'], 'Your Phola quote for today', email_wrap($html, null, $quote))) {
            $sent++;
        }
    }
    return ['sent' => $sent];
}

// ── Health news (RSS) ────────────────────────────────────────────────────
function http_get(string $url, int $timeout = 12): ?string
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_MAXREDIRS => 4,
        CURLOPT_TIMEOUT => $timeout, CURLOPT_CONNECTTIMEOUT => 6,
        CURLOPT_USERAGENT => 'PholaHealthNews/1.0 (+https://' . SITE_DOMAIN . ')',
        CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
    ]);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    return ($body !== false && $code >= 200 && $code < 300) ? (string) $body : null;
}

/** Pulls every active feed into health_news. Only headlines + a short summary are kept; readers click through to the source. */
function import_health_news(): array
{
    $added = 0; $errors = [];
    $ins = db()->prepare('INSERT IGNORE INTO health_news (source_id, title, url, url_hash, summary, image, published_at)
                          VALUES (:s, :t, :u, :h, :x, :i, :p)');
    foreach (db()->query('SELECT * FROM rss_sources WHERE is_active = 1')->fetchAll() as $src) {
        $xml = http_get((string) $src['url']);
        $error = null;
        if ($xml === null) {
            $error = 'Could not download the feed';
        } else {
            libxml_use_internal_errors(true);
            $feed = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NONET);
            if (!$feed) {
                $error = 'Not a valid RSS/Atom feed';
            } else {
                $items = $feed->channel->item ?? $feed->item ?? [];
                if (!count($items) && isset($feed->entry)) { $items = $feed->entry; } // Atom
                $n = 0;
                foreach ($items as $it) {
                    if (++$n > 25) { break; }
                    $link = (string) ($it->link['href'] ?? '') ?: trim((string) $it->link);
                    $title = trim(html_entity_decode(strip_tags((string) $it->title), ENT_QUOTES, 'UTF-8'));
                    if ($title === '' || !preg_match('~^https?://~i', $link)) { continue; }
                    $desc = (string) ($it->description ?? $it->summary ?? '');
                    $summary = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($desc), ENT_QUOTES, 'UTF-8')) ?? '');
                    if (mb_strlen($summary) > 260) { $summary = rtrim(mb_substr($summary, 0, 257)) . '…'; }
                    $img = null;
                    $media = $it->children('http://search.yahoo.com/mrss/');
                    if (isset($media->content)) { $img = (string) $media->content->attributes()->url; }
                    elseif (isset($media->thumbnail)) { $img = (string) $media->thumbnail->attributes()->url; }
                    elseif (isset($it->enclosure) && str_starts_with((string) $it->enclosure['type'], 'image')) { $img = (string) $it->enclosure['url']; }
                    $date = (string) ($it->pubDate ?? $it->published ?? $it->updated ?? '');
                    $ts = $date !== '' ? strtotime($date) : false;
                    $ins->execute([
                        ':s' => $src['id'], ':t' => mb_substr($title, 0, 300), ':u' => mb_substr($link, 0, 700), ':h' => sha1($link),
                        ':x' => $summary ?: null, ':i' => ($img && preg_match('~^https://~i', $img)) ? mb_substr($img, 0, 700) : null,
                        ':p' => $ts ? date('Y-m-d H:i:s', min($ts, time())) : date('Y-m-d H:i:s'),
                    ]);
                    $added += $ins->rowCount();
                }
            }
        }
        db()->prepare('UPDATE rss_sources SET last_fetched_at = NOW(), last_error = :e WHERE id = :id')
            ->execute([':e' => $error, ':id' => $src['id']]);
        if ($error) { $errors[] = $src['name'] . ': ' . $error; }
    }
    // Keep the table small: news older than 90 days is dropped.
    db()->exec('DELETE FROM health_news WHERE published_at < NOW() - INTERVAL 90 DAY');
    set_setting('rss_last_run', date('Y-m-d H:i:s'));
    return ['added' => $added, 'errors' => $errors];
}

// ── Google / Facebook sign-in ────────────────────────────────────────────
function oauth_config(string $provider): ?array
{
    $redirect = SITE_URL . '/modules/auth/' . $provider . '.php';
    if ($provider === 'google') {
        $id = trim((string) get_setting('google_client_id', ''));
        $secret = trim((string) get_setting('google_client_secret', ''));
        return $id && $secret ? ['id' => $id, 'secret' => $secret, 'redirect' => $redirect] : null;
    }
    if ($provider === 'facebook') {
        // Same Meta app as Facebook/Instagram posting (Admin → AI & social media).
        $id = preg_replace('/\D/', '', (string) get_setting('meta_app_id', '')) ?? '';
        $secret = trim((string) get_setting('meta_app_secret', ''));
        return $id && $secret ? ['id' => $id, 'secret' => $secret, 'redirect' => $redirect] : null;
    }
    return null;
}

function oauth_enabled(string $provider): bool
{
    return oauth_config($provider) !== null;
}

/** POST/GET to a provider's token or profile endpoint, returning decoded JSON. */
function oauth_http(string $url, ?array $post = null, ?string $bearer = null): array
{
    $ch = curl_init($url);
    $opts = [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15, CURLOPT_HTTPHEADER => ['Accept: application/json']];
    if ($post !== null) { $opts[CURLOPT_POST] = true; $opts[CURLOPT_POSTFIELDS] = http_build_query($post); }
    if ($bearer) { $opts[CURLOPT_HTTPHEADER][] = 'Authorization: Bearer ' . $bearer; }
    curl_setopt_array($ch, $opts);
    $body = curl_exec($ch);
    curl_close($ch);
    return json_decode((string) $body, true) ?: [];
}

/**
 * The whole sign-in round trip for modules/auth/google.php and facebook.php:
 * first visit sends the shopper to the provider, the return visit (with ?code)
 * signs them in — creating a free customer account the first time.
 */
function oauth_handle(string $provider): never
{
    $cfg = oauth_config($provider);
    if (!$cfg) {
        flash('warn', ucfirst($provider) . ' sign-in is not switched on yet. Please use your cellphone number instead.');
        redirect('modules/auth/register.php');
    }

    if (empty($_GET['code'])) {
        if (!empty($_GET['error'])) {
            flash('info', 'Sign-in was cancelled.');
            redirect('modules/auth/login.php');
        }
        $state = bin2hex(random_bytes(16));
        $_SESSION['oauth_state'] = $state;
        $_SESSION['oauth_next']  = safe_local_path((string) ($_GET['redirect'] ?? ''), 'dashboards/customer/index.php');
        $url = $provider === 'google'
            ? 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
                'client_id' => $cfg['id'], 'redirect_uri' => $cfg['redirect'], 'response_type' => 'code',
                'scope' => 'openid email profile', 'state' => $state, 'prompt' => 'select_account'])
            : 'https://www.facebook.com/v19.0/dialog/oauth?' . http_build_query([
                'client_id' => $cfg['id'], 'redirect_uri' => $cfg['redirect'], 'state' => $state, 'scope' => 'email,public_profile']);
        header('Location: ' . $url, true, 302);
        exit;
    }

    $state = (string) ($_GET['state'] ?? '');
    if (empty($_SESSION['oauth_state']) || !hash_equals((string) $_SESSION['oauth_state'], $state)) {
        flash('danger', 'That sign-in link expired. Please try again.');
        redirect('modules/auth/login.php');
    }
    unset($_SESSION['oauth_state']);

    if ($provider === 'google') {
        $tok = oauth_http('https://oauth2.googleapis.com/token', [
            'code' => (string) $_GET['code'], 'client_id' => $cfg['id'], 'client_secret' => $cfg['secret'],
            'redirect_uri' => $cfg['redirect'], 'grant_type' => 'authorization_code']);
        $me = !empty($tok['access_token']) ? oauth_http('https://openidconnect.googleapis.com/v1/userinfo', null, $tok['access_token']) : [];
        $profile = ['id' => (string) ($me['sub'] ?? ''), 'name' => (string) ($me['name'] ?? ''),
                    'email' => !empty($me['email_verified']) ? (string) ($me['email'] ?? '') : '', 'avatar' => (string) ($me['picture'] ?? '')];
    } else {
        $tok = oauth_http('https://graph.facebook.com/v19.0/oauth/access_token?' . http_build_query([
            'client_id' => $cfg['id'], 'client_secret' => $cfg['secret'], 'redirect_uri' => $cfg['redirect'], 'code' => (string) $_GET['code']]));
        $me = !empty($tok['access_token']) ? oauth_http('https://graph.facebook.com/me?' . http_build_query([
            'fields' => 'id,name,email,picture.type(large)', 'access_token' => $tok['access_token']])) : [];
        $profile = ['id' => (string) ($me['id'] ?? ''), 'name' => (string) ($me['name'] ?? ''),
                    'email' => (string) ($me['email'] ?? ''), 'avatar' => (string) ($me['picture']['data']['url'] ?? '')];
    }

    if ($profile['id'] === '') {
        flash('danger', 'We could not sign you in with ' . ucfirst($provider) . '. Please try again or use your cellphone number.');
        redirect('modules/auth/login.php');
    }

    // Same provider account → same user. Otherwise link by verified email, else create.
    $stmt = db()->prepare('SELECT * FROM users WHERE oauth_provider = :p AND oauth_id = :i LIMIT 1');
    $stmt->execute([':p' => $provider, ':i' => $profile['id']]);
    $user = $stmt->fetch();
    if (!$user && $profile['email'] !== '') {
        $stmt = db()->prepare('SELECT * FROM users WHERE email = :e LIMIT 1');
        $stmt->execute([':e' => $profile['email']]);
        $user = $stmt->fetch();
        if ($user && in_array($user['role'], ['admin', 'editor'], true)) {
            // Staff keep signing in with their password; a social account can't take one over.
            flash('warn', 'Staff accounts sign in with email and password.');
            redirect('modules/auth/login.php?mode=staff');
        }
        if ($user) {
            db_update('users', (int) $user['id'], ['oauth_provider' => $provider, 'oauth_id' => $profile['id']]);
        }
    }
    $isNew = false;
    if (!$user) {
        $id = db_insert('users', [
            'name' => mb_substr(strip_tags($profile['name'] ?: 'Phola customer'), 0, 120),
            'email' => $profile['email'] ?: null, 'role' => 'customer',
            'oauth_provider' => $provider, 'oauth_id' => $profile['id'],
            'avatar' => $profile['avatar'] ? mb_substr($profile['avatar'], 0, 500) : null,
            'daily_quotes' => 1,
        ]);
        $stmt = db()->prepare('SELECT * FROM users WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch();
        $isNew = true;
    }
    if (empty($user['is_active'])) {
        flash('danger', 'This account is switched off. WhatsApp us for help.');
        redirect('modules/auth/login.php');
    }

    db_update('users', (int) $user['id'], ['last_login_at' => date('Y-m-d H:i:s')]);
    JWTHandler::setToken($user);
    session_regenerate_id(true);

    if ($isNew) {
        if (!empty($user['email'])) {
            $html = '<p>Welcome to Phola, ' . e(explode(' ', (string) $user['name'])[0]) . '!</p>'
                  . '<p>Your free account is ready. Every morning we\'ll send you a short wellness quote, and you can set up your medication tracker with reminders for every dose.</p>'
                  . '<p><a href="' . SITE_URL . '/dashboards/customer/medications.php" style="display:inline-block;background:#2E8B3E;color:#fff;padding:11px 22px;border-radius:999px;font-weight:700;">Set up my tracker</a></p>';
            Mailer::send((string) $user['email'], 'Welcome to Phola Health Solutions', email_wrap($html));
        }
        Mailer::send(ADMIN_EMAIL, 'New customer sign-up (' . ucfirst($provider) . ') — ' . $user['name'],
            email_wrap('<p>' . e((string) $user['name']) . ' (' . e((string) ($user['email'] ?? 'no email')) . ') signed up with ' . ucfirst($provider) . '.</p>'));
        flash('success', 'Welcome to Phola! Your free account is ready.');
    }
    $next = (string) ($_SESSION['oauth_next'] ?? 'dashboards/customer/index.php');
    unset($_SESSION['oauth_next']);
    redirect($next);
}

/** The "Continue with Google / Facebook" buttons for the sign-in and sign-up pages. */
function oauth_buttons_html(string $redirect = ''): string
{
    $q = $redirect !== '' ? '?redirect=' . rawurlencode($redirect) : '';
    $out = '';
    if (oauth_enabled('google')) {
        $out .= '<a class="oauth-btn oauth-btn--google" href="' . SITE_URL . '/modules/auth/google.php' . e($q) . '"><i class="fa-brands fa-google"></i> Continue with Google</a>';
    }
    if (oauth_enabled('facebook')) {
        $out .= '<a class="oauth-btn oauth-btn--facebook" href="' . SITE_URL . '/modules/auth/facebook.php' . e($q) . '"><i class="fa-brands fa-facebook-f"></i> Continue with Facebook</a>';
    }
    return $out === '' ? '' : '<div class="oauth-row">' . $out . '</div><div class="oauth-or"><span>or use your cellphone</span></div>';
}

phola_migrate();
