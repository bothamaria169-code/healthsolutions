<?php
declare(strict_types=1);

/**
 * Every "Enquire on WhatsApp" button lands here. The enquiry is saved first
 * (Admin → Enquiries), then the visitor is sent on to WhatsApp with the
 * message already typed — so a lead is never lost even if they don't press Send.
 *
 *   GET  ?product=ID[&qty=N]      one product (product cards and pages)
 *   POST from cart.php            the whole basket, with name, phone, email, delivery choice,
 *                                 an optional recipient and points; ends on the order page
 */
require_once __DIR__ . '/includes/functions.php';

$repo  = new ProductRepository();
$user  = current_user();
$items = [];
$name  = '';
$phone = '';
$source = 'product';
$delivery = null;
$email = '';
$recipient = null;
$points = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf((string) ($_POST['csrf_token'] ?? ''))) {
        redirect('cart.php');
    }
    $source = 'list';
    $name   = mb_substr(trim(strip_tags((string) ($_POST['name'] ?? ''))), 0, 120);
    $phone  = Health::normalizePhone((string) ($_POST['phone'] ?? '')) ?? '';
    $email  = safe_email((string) ($_POST['email'] ?? '')) ? trim((string) $_POST['email']) : '';
    if (!empty($_POST['gift']) && trim((string) ($_POST['recipient_name'] ?? '')) !== '') {
        $recipient = [
            'name'  => mb_substr(trim(strip_tags((string) $_POST['recipient_name'])), 0, 120),
            'phone' => mb_substr(preg_replace('/[^\d+ ]/', '', (string) ($_POST['recipient_phone'] ?? '')) ?? '', 0, 20),
        ];
    }
    if ($user && !empty($_POST['use_points'])) {
        $cap    = (int) floor(cart_subtotal() * 100 / max(1, (float) get_setting('point_value_cents', '10')));
        $points = max(0, min((int) $_POST['use_points'], points_available((int) $user['id']), $cap));
        if ($points < (int) get_setting('min_redeem_points', '100')) { $points = 0; }
    }
    $method = (string) ($_POST['delivery'] ?? '');
    $opts   = delivery_options();
    if (isset($opts[$method])) {
        $region   = $opts[$method]['region'];
        $country  = match (true) {
            $region === 'sadc' && in_array($_POST['country'] ?? '', SADC_COUNTRIES, true) => (string) $_POST['country'],
            $region === 'intl' => mb_substr(trim(strip_tags((string) ($_POST['country_intl'] ?? ''))), 0, 60) ?: 'Outside South Africa',
            default => 'South Africa',
        };
        $delivery = [
            'method'  => $method,
            'label'   => $opts[$method]['by'] . ' — ' . $opts[$method]['label'],
            'country' => $country,
            'address' => mb_substr(trim(strip_tags((string) ($_POST['address'] ?? ''))), 0, 250),
            'fee'     => delivery_fee($method, cart_subtotal()),
        ];
    }
    foreach (cart_items() as $line) {
        $items[] = [
            'product_id' => (int) $line['product']['id'],
            'name'       => $line['product']['name'] . ($line['variant_text'] ? ' (' . $line['variant_text'] . ')' : ''),
            'pack'       => (string) ($line['product']['pack_size'] ?? ''),
            'qty'        => (int) $line['qty'],
            'price'      => (float) $line['unit_price'],
        ];
    }
} else {
    $product = $repo->getById((int) ($_GET['product'] ?? 0));
    if ($product && $product['status'] === 'published') {
        $items[] = [
            'product_id' => (int) $product['id'],
            'name'       => $product['name'],
            'pack'       => (string) ($product['pack_size'] ?? ''),
            'qty'        => max(1, min(99, (int) ($_GET['qty'] ?? 1))),
            'price'      => Pricing::retail($product),
        ];
    }
}

if (!$items) {
    redirect(wa_link("Hi Phola, I have a question."));
}

if ($user) {
    $name  = $name ?: (string) $user['name'];
    $phone = $phone ?: (string) ($user['phone'] ?? '');
}

$total = array_sum(array_map(fn($i) => $i['price'] * $i['qty'], $items));
$grand = $total + (float) ($delivery['fee'] ?? 0);

// The message the customer sends. Prices are for reference: staff confirm the final total.
$lines = array_map(fn($i) => '• ' . $i['qty'] . ' × ' . $i['name'] . ($i['pack'] ? ' (' . $i['pack'] . ')' : '')
    . ' — ' . format_price_short($i['price'] * $i['qty']), $items);
$message = "Hi Phola, I'd like to order:\n" . implode("\n", $lines)
    . (count($items) > 1 || $delivery ? "\n\nProducts: " . format_price_short($total) : '')
    . ($delivery ? "\nDelivery: " . $delivery['label'] . ' — please quote'
        . "\nTo: " . $delivery['address'] . ($delivery['country'] !== 'South Africa' ? ', ' . $delivery['country'] : '') : '')
    . ($recipient ? "\nFor: " . $recipient['name'] . ($recipient['phone'] !== '' ? ' (' . $recipient['phone'] . ')' : '') : '')
    . ($points > 0 ? "\nUsing " . $points . ' rewards points (' . format_price_short(points_value($points)) . ' off)' : '')
    . ($name !== '' ? "\n\nName: " . $name : '')
    . ($phone !== '' ? "\nCell: " . Health::displayPhone($phone) : '')
    . "\nPlease confirm stock and send payment details.";

// Don't let a reload or a bot fill the enquiries table: one save per list per 10 minutes.
$fingerprint = md5(json_encode($items) . $phone);
if (rate_limit('enquiry', 20, 3600) && ($_SESSION['last_enquiry'] ?? '') !== $fingerprint) {
    try {
        $id = db_insert('enquiries', [
            'user_id' => $user['id'] ?? null,
            'name'    => $name ?: null,
            'phone'   => $phone ?: null,
            'items'   => json_encode($items, JSON_UNESCAPED_UNICODE),
            'total'   => $grand,
            'delivery'=> $delivery ? json_encode($delivery, JSON_UNESCAPED_UNICODE) : null,
            'email'   => $email ?: null,
            'recipient' => $recipient ? json_encode($recipient, JSON_UNESCAPED_UNICODE) : null,
            'points_redeemed' => $points,
            'token'   => $token = bin2hex(random_bytes(16)),
            'source'  => $source,
        ]);
        $_SESSION['last_enquiry'] = $fingerprint;
        if ($email !== '') {
            $link = SITE_URL . '/order.php?ref=E' . $id . '&t=' . $token;
            Mailer::send($email, 'We received your Phola order E' . $id,
                email_wrap('<p>Thank you' . ($name !== '' ? ', ' . e(explode(' ', $name)[0]) : '') . '! We have your order <b>E' . $id . '</b> (' . format_price_short($total) . ').</p>'
                    . '<p>We will confirm stock and the delivery cost on WhatsApp. Open your order to request banking details and upload your proof of payment:</p>'
                    . '<p><a href="' . e($link) . '" style="display:inline-block;background:#2E8B3E;color:#fff;padding:11px 22px;border-radius:999px;font-weight:700;">View my order</a></p>'));
        }
        $message .= "\n(Ref: E" . $id . ')';
    } catch (Throwable $e) {
        // Never block the customer from reaching us because the log failed.
        error_log('Enquiry not saved: ' . $e->getMessage());
    }
}

if ($source === 'list') {
    cart_clear();
    // Basket orders land on their own confirmation page: send on WhatsApp,
    // request banking details, upload proof of payment.
    if (!empty($id) && !empty($token)) {
        $_SESSION['order_message'][(int) $id] = $message;
        redirect('order.php?ref=E' . (int) $id . '&t=' . $token);
    }
}
redirect(wa_link($message));
