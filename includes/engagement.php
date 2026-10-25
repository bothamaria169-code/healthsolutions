<?php
declare(strict_types=1);

/**
 * Phola engagement features (loaded after features.php):
 *   - rewards points and refer-a-friend
 *   - what happens when an order is marked sold (stock, best-sellers, points)
 *   - EFT banking details for the order confirmation page
 *   - the language menu (Google Translate, loaded only once a visitor picks a language)
 *   - condition hub pages
 */

/**
 * Plain text from a form: tags stripped and trimmed, but NOT HTML-escaped —
 * every page escapes with e() when it prints, so escaping here as well would
 * show "Q&amp;A" and "I&#039;ve". (The older sanitize() does escape; it is kept
 * for the original CureMeds forms.)
 */
function clean_text(?string $s): string
{
    return trim(strip_tags((string) $s));
}

// ── Rewards points ───────────────────────────────────────────────────────
function points_balance(int $userId): int
{
    $s = db()->prepare('SELECT COALESCE(SUM(points), 0) FROM points_ledger WHERE user_id = :u');
    $s->execute([':u' => $userId]);
    return (int) $s->fetchColumn();
}

/** Points already promised to open orders, so they can't be spent twice. */
function points_reserved(int $userId): int
{
    $s = db()->prepare("SELECT COALESCE(SUM(points_redeemed), 0) FROM enquiries WHERE user_id = :u AND sold_processed = 0 AND status IN ('new','contacted')");
    $s->execute([':u' => $userId]);
    return (int) $s->fetchColumn();
}

function points_available(int $userId): int
{
    return max(0, points_balance($userId) - points_reserved($userId));
}

function points_add(int $userId, int $points, string $reason, ?int $enquiryId = null): void
{
    if ($points === 0) { return; }
    db_insert('points_ledger', ['user_id' => $userId, 'points' => $points, 'reason' => mb_substr($reason, 0, 160), 'enquiry_id' => $enquiryId]);
}

/** Rand value of a number of points. */
function points_value(int $points): float
{
    return $points * (float) get_setting('point_value_cents', '10') / 100;
}

/** Each customer's personal referral code, created the first time it is needed. */
function referral_code(int $userId): string
{
    $s = db()->prepare('SELECT referral_code, name FROM users WHERE id = :id');
    $s->execute([':id' => $userId]);
    $row = $s->fetch();
    if (!empty($row['referral_code'])) { return (string) $row['referral_code']; }
    $base = strtoupper(preg_replace('/[^A-Za-z]/', '', explode(' ', (string) ($row['name'] ?? 'PHOLA'))[0]) ?: 'PHOLA');
    do {
        $code = substr($base, 0, 6) . random_int(10, 99) . strtoupper(bin2hex(random_bytes(1)));
        $taken = db()->prepare('SELECT 1 FROM users WHERE referral_code = :c');
        $taken->execute([':c' => $code]);
    } while ($taken->fetch());
    db_update('users', $userId, ['referral_code' => $code]);
    return $code;
}

function referral_link(int $userId): string
{
    return SITE_URL . '/?ref=' . rawurlencode(referral_code($userId));
}

/** Remembers who sent a visitor (?ref=CODE) for 30 days. */
function capture_referral(): void
{
    if (PHP_SAPI === 'cli' || empty($_GET['ref']) || headers_sent()) { return; }
    $code = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $_GET['ref']) ?? '');
    if ($code !== '' && strlen($code) <= 12) {
        setcookie('phola_ref', $code, ['expires' => time() + 30 * 86400, 'path' => '/', 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS']), 'httponly' => true]);
        $_COOKIE['phola_ref'] = $code;
    }
}

/** Call right after a new customer account is created (phone or Google/Facebook). */
function rewards_on_signup(int $userId): void
{
    $bonus = (int) get_setting('signup_points', '50');
    points_add($userId, $bonus, 'Welcome bonus');
    $code = (string) ($_COOKIE['phola_ref'] ?? '');
    if ($code === '') { return; }
    $s = db()->prepare('SELECT id FROM users WHERE referral_code = :c AND id <> :me');
    $s->execute([':c' => $code, ':me' => $userId]);
    if ($ref = $s->fetchColumn()) {
        db_update('users', $userId, ['referred_by' => (int) $ref]);
    }
}

/**
 * Everything that should happen once when an order is sold (paid): stock and
 * best-seller counts, points earned and spent, and the referral bonus for
 * whoever sent this customer — on their first order only. Safe to call twice.
 */
function enquiry_mark_sold(int $enquiryId): void
{
    $s = db()->prepare('SELECT * FROM enquiries WHERE id = :id');
    $s->execute([':id' => $enquiryId]);
    $e = $s->fetch();
    if (!$e || (int) $e['sold_processed'] === 1) { return; }

    foreach (json_decode((string) $e['items'], true) ?: [] as $item) {
        db()->prepare('UPDATE products SET sold_count = sold_count + :q, stock_qty = GREATEST(0, stock_qty - :q2) WHERE id = :p')
            ->execute([':q' => (int) $item['qty'], ':q2' => (int) $item['qty'], ':p' => (int) $item['product_id']]);
    }

    // An order placed without signing in still earns points if the cellphone matches an account.
    $userId = (int) ($e['user_id'] ?? 0);
    if (!$userId && !empty($e['phone'])) {
        $u = db()->prepare('SELECT id FROM users WHERE phone = :p');
        $u->execute([':p' => $e['phone']]);
        $userId = (int) ($u->fetchColumn() ?: 0);
        if ($userId) { db_update('enquiries', $enquiryId, ['user_id' => $userId]); }
    }

    if ($userId) {
        $products = array_sum(array_map(fn($i) => (float) $i['price'] * (int) $i['qty'], json_decode((string) $e['items'], true) ?: []));
        $earned = (int) floor($products / 10) * (int) get_setting('points_per_r10', '1');
        points_add($userId, $earned, 'Order E' . $enquiryId, $enquiryId);
        if ((int) $e['points_redeemed'] > 0) {
            points_add($userId, -(int) $e['points_redeemed'], 'Spent on order E' . $enquiryId, $enquiryId);
        }
        $first = db()->prepare('SELECT COUNT(*) FROM enquiries WHERE user_id = :u AND sold_processed = 1');
        $first->execute([':u' => $userId]);
        $ref = db()->prepare('SELECT referred_by, name FROM users WHERE id = :u');
        $ref->execute([':u' => $userId]);
        $me = $ref->fetch();
        if ((int) $first->fetchColumn() === 0 && !empty($me['referred_by'])) {
            points_add((int) $me['referred_by'], (int) get_setting('referral_points', '200'), 'Referral: ' . explode(' ', (string) $me['name'])[0] . "'s first order", $enquiryId);
        }
    }
    db_update('enquiries', $enquiryId, ['sold_processed' => 1, 'status' => 'sold']);
}

// ── Orders: confirmation page and banking details ────────────────────────
function order_url(array $enquiry): string
{
    return SITE_URL . '/order.php?ref=E' . (int) $enquiry['id'] . '&t=' . rawurlencode((string) $enquiry['token']);
}

/** The order for a confirmation-page link, or null if the link is wrong. */
function order_from_link(string $ref, string $token): ?array
{
    $id = (int) ltrim(strtoupper($ref), 'E');
    if ($id <= 0 || strlen($token) !== 32) { return null; }
    $s = db()->prepare('SELECT * FROM enquiries WHERE id = :id');
    $s->execute([':id' => $id]);
    $e = $s->fetch();
    return ($e && !empty($e['token']) && hash_equals((string) $e['token'], $token)) ? $e : null;
}

/** @return array{name:string,holder:string,number:string,branch:string,type:string}|null */
function bank_details(): ?array
{
    $b = [
        'name'   => trim((string) get_setting('bank_name', '')),
        'holder' => trim((string) get_setting('bank_account_holder', '')),
        'number' => trim((string) get_setting('bank_account_number', '')),
        'branch' => trim((string) get_setting('bank_branch_code', '')),
        'type'   => trim((string) get_setting('bank_account_type', '')),
    ];
    return $b['name'] !== '' && $b['number'] !== '' ? $b : null;
}

function bank_details_html(array $b, string $reference): string
{
    $row = fn($k, $v) => $v !== '' ? '<tr><td style="padding:6px 12px 6px 0;color:#7D8E83;">' . e($k) . '</td><td style="padding:6px 0;font-weight:700;color:#0F3D24;">' . e($v) . '</td></tr>' : '';
    return '<table role="presentation" style="border-collapse:collapse;font-size:15px;">'
        . $row('Bank', $b['name']) . $row('Account holder', $b['holder']) . $row('Account number', $b['number'])
        . $row('Branch code', $b['branch']) . $row('Account type', $b['type']) . $row('Payment reference', $reference)
        . '</table>';
}

// ── Languages ────────────────────────────────────────────────────────────
/** Language menu: Google Translate codes. English is the original. */
const SITE_LANGUAGES = [
    'en' => 'English', 'zu' => 'isiZulu', 'xh' => 'isiXhosa', 'st' => 'Sesotho', 'af' => 'Afrikaans',
    'sn' => 'chiShona', 'ny' => 'Chichewa', 'pt' => 'Português', 'sw' => 'Kiswahili', 'fr' => 'Français',
];

/** The language the visitor picked (from Google Translate's own cookie), or 'en'. */
function current_language(): string
{
    $c = (string) ($_COOKIE['googtrans'] ?? '');
    $code = preg_match('~^/en/([a-z]{2,3})$~', $c, $m) ? $m[1] : 'en';
    return isset(SITE_LANGUAGES[$code]) ? $code : 'en';
}

// ── Condition hubs ───────────────────────────────────────────────────────
function condition_hubs(): array
{
    return require __DIR__ . '/content/conditions.php';
}

capture_referral();
