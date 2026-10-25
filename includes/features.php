<?php
declare(strict_types=1);

/**
 * Phola additions on top of the CureMeds codebase:
 *   - self-applying database upgrades (no SQL to import on the live server)
 *   - delivery: The Courier Guy / Pudo from our locker (to door, locker, pick-up point or kiosk), SADC bus/taxi depots and DHL worldwide
 *   - the daily wellness quote (dashboard + optional morning email)
 *   - health-news RSS import
 *   - Google and Facebook sign-in
 * Loaded at the end of includes/functions.php.
 */

// ── Database upgrades ────────────────────────────────────────────────────
// Each step runs once, in order; the number reached is kept in settings as
// `schema_version`. Steps only ever add (tables, columns, rows), so running a
// new deploy against the live database is always safe.
const PHOLA_SCHEMA_VERSION = 8;

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
        if ($current < 3) {
            // Refer-a-friend + rewards points
            $addCol('users', 'referral_code', 'VARCHAR(12) DEFAULT NULL');
            $addCol('users', 'referred_by', 'INT UNSIGNED DEFAULT NULL');
            $pdo->exec("CREATE TABLE IF NOT EXISTS `points_ledger` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `user_id` INT UNSIGNED NOT NULL,
                `points` INT NOT NULL,
                `reason` VARCHAR(160) NOT NULL,
                `enquiry_id` INT UNSIGNED DEFAULT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`), KEY `idx_user` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            // Orders: confirmation page, banking details, proof of payment, gifts, points
            foreach ([
                'email' => 'VARCHAR(180) DEFAULT NULL', 'token' => 'CHAR(32) DEFAULT NULL', 'recipient' => 'TEXT DEFAULT NULL',
                'points_redeemed' => 'INT NOT NULL DEFAULT 0', 'sold_processed' => 'TINYINT(1) NOT NULL DEFAULT 0',
                'delivery_fee' => 'DECIMAL(10,2) DEFAULT NULL', 'bank_requested_at' => 'DATETIME DEFAULT NULL',
                'pop_file' => 'VARCHAR(255) DEFAULT NULL', 'pop_uploaded_at' => 'DATETIME DEFAULT NULL',
            ] as $col => $def) {
                $addCol('enquiries', $col, $def);
            }
            // Patient stories live with the testimonials
            $addCol('testimonials', 'story', 'LONGTEXT DEFAULT NULL');
            $addCol('testimonials', 'video_url', 'VARCHAR(300) DEFAULT NULL');
            $addCol('testimonials', 'condition_tag', 'VARCHAR(60) DEFAULT NULL');
            // Ask a pharmacist
            $pdo->exec("CREATE TABLE IF NOT EXISTS `questions` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(120) DEFAULT NULL,
                `email` VARCHAR(180) DEFAULT NULL,
                `condition_tag` VARCHAR(60) DEFAULT NULL,
                `question` TEXT NOT NULL,
                `answer` TEXT DEFAULT NULL,
                `slug` VARCHAR(200) DEFAULT NULL,
                `status` ENUM('pending','published','hidden') NOT NULL DEFAULT 'pending',
                `answered_at` DATETIME DEFAULT NULL,
                `views` INT UNSIGNED NOT NULL DEFAULT 0,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`), UNIQUE KEY `uq_slug` (`slug`), KEY `idx_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            // Health talks (WhatsApp group talks, webinars, in-person days)
            $pdo->exec("CREATE TABLE IF NOT EXISTS `events` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `title` VARCHAR(200) NOT NULL,
                `starts_at` DATETIME NOT NULL,
                `channel` VARCHAR(40) NOT NULL DEFAULT 'WhatsApp group',
                `join_link` VARCHAR(400) DEFAULT NULL,
                `description` TEXT DEFAULT NULL,
                `status` ENUM('published','draft') NOT NULL DEFAULT 'published',
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`), KEY `idx_start` (`starts_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            $pdo->exec("CREATE TABLE IF NOT EXISTS `event_rsvps` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `event_id` INT UNSIGNED NOT NULL,
                `name` VARCHAR(120) NOT NULL,
                `phone` VARCHAR(20) DEFAULT NULL,
                `email` VARCHAR(180) DEFAULT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`), KEY `idx_event` (`event_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            foreach (['points_per_r10' => '1', 'point_value_cents' => '10', 'referral_points' => '200', 'signup_points' => '50', 'min_redeem_points' => '100'] as $k => $v) {
                if (get_setting($k) === null) { set_setting($k, $v); }
            }
        }
        if ($current < 4) {
            // A live database that only had database.sql imported gets the starter
            // catalogue, the admin account and the demo patient from seed.sql.
            phola_seed_starter_data($pdo);
            // Product 24 got its own pack shot.
            $pdo->exec("UPDATE products SET featured_image = 'assets/img/products/24b.jpg'
                        WHERE slug = 'nad-resveratrol-1000mg' AND (featured_image IS NULL OR featured_image = '')");
            // New listing from the product photos (also in seed.sql as id 34).
            $pdo->exec("INSERT IGNORE INTO products
                (category_id, brand_id, name, slug, short_desc, description, benefits, usage_notes, type, retail_price, pack_size, sku, stock_status, stock_qty, featured_image, is_new, status, tags)
                SELECT 8, 3, 'Purely Lung Assistance Respiratory Support', 'purely-lung-assistance',
                       'Purely Nutraceuticals Lung Assistance: a herbal respiratory support formula for lung health and easier breathing.',
                       '<p>Purely Nutraceuticals Lung Assistance: a herbal respiratory support formula for lung health and easier breathing.</p><p><b>This is a supplement, not a medicine.</b> It does not diagnose, treat, cure or prevent any disease and never replaces treatment prescribed by your doctor or clinic. Keep taking your prescribed medication and tell your clinic you are using it.</p>',
                       'Supports lung health and breathing\nHerbal respiratory formula\n60 capsules',
                       'Take two capsules daily with food, or as directed on the label.',
                       'simple', 420.00, '60 capsules', 'PHL-34', 'in_stock', 50, 'assets/img/products/34b.jpg', 1, 'published', 'lungs,respiratory,herbal,breathing'
                FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug = 'purely-lung-assistance')
                  AND EXISTS (SELECT 1 FROM categories WHERE id = 8) AND EXISTS (SELECT 1 FROM brands WHERE id = 3)");
        }
        if ($current < 5) {
            // The SAHPRA disclaimer FAQ was taken off the site.
            $pdo->exec("DELETE FROM faqs WHERE answer LIKE '%SAHPRA%'");
        }
        if ($current < 6) {
            // Products 25-33 got their pack shots (NNb.jpg next to the NN.jpg header).
            $pdo->exec("UPDATE products SET featured_image = REPLACE(header_image, '.jpg', 'b.jpg')
                        WHERE header_image REGEXP '^assets/img/products/(2[5-9]|3[0-3])\\\\.jpg$'
                          AND (featured_image IS NULL OR featured_image = '')");
        }
        if ($current < 7) {
            // Testimonials can be a screenshot of the customer's own message, shown as-is.
            $addCol('testimonials', 'screenshot', 'VARCHAR(500) DEFAULT NULL');
            // "From our customers" photo gallery, managed in Admin -> Customer gallery.
            $pdo->exec("CREATE TABLE IF NOT EXISTS `customer_gallery` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `image` VARCHAR(500) NOT NULL,
                `caption` VARCHAR(200) DEFAULT NULL,
                `status` ENUM('published','draft') NOT NULL DEFAULT 'published',
                `sort_order` INT NOT NULL DEFAULT 0,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            // The four starter quotes were placeholders: hide them so only real customers show.
            $pdo->exec("UPDATE testimonials SET status = 'draft'
                        WHERE name IN ('Thandi M.','Sipho K.','Lerato D.','Johan P.') AND avatar IS NULL");
        }
        if ($current < 8) {
            // Home page hero slider, picked from the product feed in Admin -> Home slider.
            $pdo->exec("CREATE TABLE IF NOT EXISTS `home_slides` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `product_id` INT UNSIGNED NOT NULL,
                `image` VARCHAR(500) DEFAULT NULL,
                `status` ENUM('published','draft') NOT NULL DEFAULT 'published',
                `sort_order` INT NOT NULL DEFAULT 0,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            // Copies of our photos and reels on ImageKit, so Meta fetches them from a fast public CDN.
            $pdo->exec("CREATE TABLE IF NOT EXISTS `imagekit_files` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `local_path` VARCHAR(500) NOT NULL,
                `local_mtime` INT UNSIGNED NOT NULL DEFAULT 0,
                `file_id` VARCHAR(80) NOT NULL,
                `url` VARCHAR(600) NOT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`), KEY `idx_path` (`local_path`(191))
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            $addCol('social_posts', 'media_type', "VARCHAR(10) NOT NULL DEFAULT 'photo'");
            $addCol('social_posts', 'media_url', 'VARCHAR(600) DEFAULT NULL');
        }
        set_setting('schema_version', (string) PHOLA_SCHEMA_VERSION);
    } catch (Throwable $e) {
        error_log('Phola migration failed: ' . $e->getMessage());
    }
}

/**
 * Runs database/seed.sql when the database has no admin account or no products,
 * i.e. seed.sql was never imported in phpMyAdmin. Rows that already exist are
 * kept: keyed tables use ON DUPLICATE KEY / INSERT IGNORE, and the unkeyed
 * content tables are only filled while they are still empty.
 */
function phola_seed_starter_data(PDO $pdo): void
{
    $admins   = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
    $products = (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
    $file     = __DIR__ . '/../database/seed.sql';
    if (($admins > 0 && $products > 0) || !is_file($file)) {
        return;
    }
    $onlyIfEmpty = ['testimonials', 'faqs', 'product_gallery', 'side_effects'];
    $seededProducts = $products === 0;

    foreach (sql_statements((string) file_get_contents($file)) as $sql) {
        if (preg_match('/^UPDATE\s+`?products`?/i', $sql)) {
            if (!$seededProducts) { continue; }  // made-up sales counts only for a fresh catalogue
        } elseif (preg_match('/^INSERT\s+(?:IGNORE\s+)?INTO\s+`?(\w+)`?/i', $sql, $m)) {
            if (in_array($m[1], $onlyIfEmpty, true) && (int) $pdo->query("SELECT COUNT(*) FROM `{$m[1]}`")->fetchColumn() > 0) {
                continue;
            }
            $sql = preg_replace('/^INSERT\s+INTO/i', 'INSERT IGNORE INTO', $sql);
        } elseif (!preg_match('/^SET\s/i', $sql)) {
            continue;
        }
        try {
            $pdo->exec($sql);
        } catch (Throwable $e) {
            error_log('Phola seed statement skipped: ' . $e->getMessage());
        }
    }
}

/** Splits an SQL dump into statements, ignoring semicolons inside quotes and -- comments. */
function sql_statements(string $sql): array
{
    $out = [];
    $buf = '';
    $quote = null;
    $len = strlen($sql);
    for ($i = 0; $i < $len; $i++) {
        $c = $sql[$i];
        if ($quote !== null) {
            $buf .= $c;
            if ($c === '\\' && $i + 1 < $len) {
                $buf .= $sql[++$i];
            } elseif ($c === $quote) {
                if ($i + 1 < $len && $sql[$i + 1] === $quote) {
                    $buf .= $sql[++$i];  // '' inside a string
                } else {
                    $quote = null;
                }
            }
        } elseif ($c === "'" || $c === '"' || $c === '`') {
            $quote = $c;
            $buf .= $c;
        } elseif ($c === '-' && substr($sql, $i, 3) === '-- ') {
            $nl = strpos($sql, "\n", $i);
            $i = $nl === false ? $len : $nl;
            $buf .= "\n";
        } elseif ($c === ';') {
            if (trim($buf) !== '') { $out[] = trim($buf); }
            $buf = '';
        } else {
            $buf .= $c;
        }
    }
    if (trim($buf) !== '') { $out[] = trim($buf); }
    return $out;
}

/**
 * Lets the owner get into the admin without email or phpMyAdmin: put
 * 'admin_password' => '…' in config/secrets.php and open the staff sign-in page.
 * The admin account (ADMIN_EMAIL) is created, or its password set to that value,
 * once per new value, so changing the password in the admin later still sticks.
 */
function phola_apply_admin_password(): void
{
    if (ADMIN_SETUP_PASSWORD === '') {
        return;
    }
    $mark = hash('sha256', ADMIN_EMAIL . '|' . ADMIN_SETUP_PASSWORD . '|' . JWT_SECRET);
    if (get_setting('admin_password_applied') === $mark) {
        return;
    }
    $hash = password_hash(ADMIN_SETUP_PASSWORD, PASSWORD_DEFAULT);
    $id = db()->prepare('SELECT id FROM users WHERE email = :e LIMIT 1');
    $id->execute([':e' => ADMIN_EMAIL]);
    $id = $id->fetchColumn();
    if ($id) {
        db()->prepare("UPDATE users SET password = :p, role = 'admin', is_active = 1 WHERE id = :id")
            ->execute([':p' => $hash, ':id' => $id]);
    } else {
        db()->prepare("INSERT INTO users (name, email, password, role, is_active, reminders_enabled) VALUES ('Phola Admin', :e, :p, 'admin', 1, 0)")
            ->execute([':e' => ADMIN_EMAIL, ':p' => $hash]);
    }
    set_setting('admin_password_applied', $mark);
    // Clear any lock-out from earlier wrong guesses.
    unset($_SESSION['_rl_login_' . md5(ADMIN_EMAIL)]);
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
        'tcg_door'   => ['label' => 'Locker to door', 'by' => 'The Courier Guy · Pudo', 'icon' => 'fa-house-chimney', 'region' => 'za', 'time' => '2–4 working days',
                         'blurb' => 'We send from our locker; the courier delivers to your home or office.', 'field' => 'Street address, suburb, city and postal code'],
        'tcg_locker' => ['label' => 'Locker to locker', 'by' => 'The Courier Guy · Pudo', 'icon' => 'fa-box-archive', 'region' => 'za', 'time' => '2–4 working days',
                         'blurb' => 'From our locker to a 24/7 Pudo smart locker near you — collect any time with a PIN.', 'field' => 'Your Pudo locker name or code (e.g. Engen Rivonia)'],
        'tcg_point'  => ['label' => 'Locker to pick-up point', 'by' => 'The Courier Guy · Pudo', 'icon' => 'fa-store', 'region' => 'za', 'time' => '2–4 working days',
                         'blurb' => 'From our locker to a partner store close to home.', 'field' => 'Pick-up point name and town'],
        'tcg_kiosk'  => ['label' => 'Locker to kiosk', 'by' => 'The Courier Guy · Pudo', 'icon' => 'fa-shop', 'region' => 'za', 'time' => '2–4 working days',
                         'blurb' => 'From our locker to a Courier Guy kiosk in a mall or garage.', 'field' => 'Kiosk name and town'],
        'sadc_depot' => ['label' => 'Bus / taxi depot', 'by' => 'SADC countries', 'icon' => 'fa-bus', 'region' => 'sadc', 'time' => '2–5 days',
                         'blurb' => 'Sent with a trusted cross-border bus or taxi to the depot nearest you.', 'field' => 'Nearest depot / town (e.g. Harare Roadport)'],
        'dhl_intl'   => ['label' => 'International', 'by' => 'DHL Express', 'icon' => 'fa-plane-departure', 'region' => 'intl', 'time' => '3–7 working days',
                         'blurb' => 'Door-to-door anywhere in the world, tracked all the way.', 'field' => 'Full delivery address, city and postal code'],
    ];
}

/**
 * The carrier's logo for a delivery option, or null (then the icon is shown).
 * Logo files live in assets/img/partners/: tcg.* for The Courier Guy (also the
 * Pudo lockers), dhl.* for DHL. SADC bus/taxi has no single carrier.
 */
function delivery_logo(string $method): ?string
{
    $brand = str_starts_with($method, 'tcg_') ? 'tcg' : (str_starts_with($method, 'dhl') ? 'dhl' : null);
    if (!$brand) { return null; }
    foreach (['svg', 'png', 'webp', 'jpg'] as $ext) {
        $file = 'assets/img/partners/' . $brand . '.' . $ext;
        if (is_file(__DIR__ . '/../' . $file)) { return SITE_URL . '/' . $file; }
    }
    return null;
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
                    if (!$img) {
                        $html = (string) ($it->children('http://purl.org/rss/1.0/modules/content/')->encoded ?? '') . $desc;
                        if (preg_match('~<img[^>]+src=["\'](https://[^"\']+)~i', $html, $m)) { $img = html_entity_decode($m[1]); }
                    }
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
    news_fill_images();
    // Keep the table small: news older than 90 days is dropped.
    db()->exec('DELETE FROM health_news WHERE published_at < NOW() - INTERVAL 90 DAY');
    set_setting('rss_last_run', date('Y-m-d H:i:s'));
    return ['added' => $added, 'errors' => $errors];
}

/**
 * Most feeds don't include a picture, but almost every news page has an
 * og:image for social sharing. Look it up for a handful of recent stories per
 * run. image = '' marks "looked, none found" so a page is only fetched once.
 */
function news_fill_images(int $limit = 15): int
{
    $rows = db()->query("SELECT id, url FROM health_news WHERE image IS NULL ORDER BY published_at DESC LIMIT " . $limit)->fetchAll();
    $found = 0;
    $upd = db()->prepare('UPDATE health_news SET image = :i WHERE id = :id');
    foreach ($rows as $r) {
        $img = '';
        $html = http_get((string) $r['url'], 8);
        if ($html !== null) {
            $head = substr($html, 0, 200000);
            if (preg_match('~<meta[^>]+(?:property|name)=["\'](?:og:image|twitter:image)(?::src)?["\'][^>]*content=["\']([^"\']+)~i', $head, $m)
                || preg_match('~<meta[^>]+content=["\']([^"\']+)["\'][^>]*(?:property|name)=["\'](?:og:image|twitter:image)~i', $head, $m)) {
                $img = html_entity_decode(trim($m[1]), ENT_QUOTES, 'UTF-8');
                if (str_starts_with($img, '//')) { $img = 'https:' . $img; }
                if (!preg_match('~^https://~i', $img)) { $img = ''; }
            }
        }
        $upd->execute([':i' => mb_substr($img, 0, 700), ':id' => $r['id']]);
        if ($img !== '') { $found++; }
    }
    return $found;
}

/** The picture for a news card: the story's own, or a topic photo of ours. */
function news_image(array $n): string
{
    if (!empty($n['image'])) {
        return (string) $n['image'];
    }
    $text = strtolower(' ' . $n['title'] . ' ' . ($n['summary'] ?? '') . ' ');
    foreach ([
        'glucobalance.jpg' => ['diabet', 'insulin', 'glucose', 'blood sugar'],
        'bp-check.jpg'     => ['blood pressure', 'hypertension', 'stroke', 'heart', 'cardio'],
        'patient-care.jpg' => ['hiv', 'aids', 'antiretroviral', 'tuberculosis', ' tb '],
        'healthy-food.jpg' => ['diet', 'food', 'nutrition', 'obesity', 'weight', 'eat'],
        'fitness.jpg'      => ['exercise', 'physical activity', 'walking', 'fitness'],
        'senior-care.jpg'  => ['dementia', 'alzheimer', 'ageing', 'aging', 'older adults', 'elderly'],
        'pharmacist.jpg'   => ['drug', 'medicine', 'medication', 'pharma', 'antibiotic', 'vaccine'],
        'nurse.jpg'        => ['hospital', 'clinic', 'nurse', 'health worker', 'patients'],
        'sleep-drops.jpg'  => ['sleep', 'insomnia', 'mental health', 'depression', 'anxiety', 'stress'],
        'probiotic.jpg'    => ['gut', 'microbiome', 'bacteria'],
        'doctor-smile.jpg' => ['cancer', 'tumour', 'tumor', 'chemo'],
    ] as $file => $words) {
        foreach ($words as $w) {
            if (str_contains($text, $w)) { return SITE_URL . '/assets/img/stock/' . $file; }
        }
    }
    $generic = ['hero-consult.jpg', 'pharmacy.jpg', 'nurse-bp.jpg', 'pharmacy-shopper.jpg', 'doctor-smile.jpg'];
    return SITE_URL . '/assets/img/stock/' . $generic[crc32((string) $n['title']) % count($generic)];
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
        rewards_on_signup((int) $id);
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
