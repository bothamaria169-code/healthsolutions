<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

/**
 * Order confirmation, reached from the basket (and from the link in our emails):
 *   1. send the order on WhatsApp
 *   2. request the banking details (shown here and emailed)
 *   3. upload the proof of payment
 * The link carries a random token, so only the customer can open their order.
 */
$order = order_from_link((string) ($_GET['ref'] ?? ''), (string) ($_GET['t'] ?? ''));
if (!$order) {
    http_response_code(404);
    $page_title = 'Order not found — ' . SITE_NAME;
    include __DIR__ . '/includes/header.php';
    echo '<div class="section"><div class="container"><div class="empty card-fc"><div class="empty__icon"><i class="fa-solid fa-receipt"></i></div><h2>We couldn\'t find that order</h2><p>Please use the link from your email, or WhatsApp us your order number.</p><a class="btn-fc" href="' . e(wa_link('Hi Phola, I need help with my order.')) . '">WhatsApp us</a></div></div></div>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

$ref      = 'E' . (int) $order['id'];
$items    = json_decode((string) $order['items'], true) ?: [];
$delivery = json_decode((string) ($order['delivery'] ?? ''), true) ?: null;
$recip    = json_decode((string) ($order['recipient'] ?? ''), true) ?: null;
$products = array_sum(array_map(fn($i) => (float) $i['price'] * (int) $i['qty'], $items));
$discount = points_value((int) $order['points_redeemed']);
$bank     = bank_details();
$url      = order_url($order);

// The ready-made WhatsApp message (kept from the basket; rebuilt if the session is gone).
$message = $_SESSION['order_message'][(int) $order['id']] ?? ("Hi Phola, I placed order " . $ref . " on the website (" . format_price_short($products) . "). Please confirm stock and delivery.");

$flash = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf((string) ($_POST['csrf_token'] ?? ''))) {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'bank' && $bank) {
        $email = safe_email((string) ($_POST['email'] ?? '')) ? trim((string) $_POST['email']) : (string) ($order['email'] ?? '');
        db_update('enquiries', (int) $order['id'], ['bank_requested_at' => date('Y-m-d H:i:s'), 'email' => $email ?: null]);
        if ($email !== '' && rate_limit('bank_' . $order['id'], 3, 3600)) {
            $html = '<p>Thank you for your order <b>' . $ref . '</b>.</p>'
                  . '<p>Please pay by EFT into the account below, using <b>' . $ref . '</b> as the reference. '
                  . 'Pay once we have confirmed the final total (products and delivery) with you on WhatsApp.</p>'
                  . bank_details_html($bank, $ref)
                  . '<p>Then upload your proof of payment here:</p>'
                  . '<p><a href="' . e($url) . '#pop" style="display:inline-block;background:#2E8B3E;color:#fff;padding:11px 22px;border-radius:999px;font-weight:700;">Upload proof of payment</a></p>';
            Mailer::send($email, 'Banking details for your Phola order ' . $ref, email_wrap($html));
        }
        Mailer::send(ADMIN_EMAIL, 'Banking details requested — ' . $ref,
            email_wrap('<p>' . e((string) $order['name']) . ' requested banking details for order <b>' . $ref . '</b> (' . format_price_short($products) . ').</p><p><a href="' . SITE_URL . '/dashboards/admin/enquiries.php?id=' . (int) $order['id'] . '">Open the order</a></p>'));
        $flash = ['success', $email !== '' ? 'Banking details are below and on their way to ' . $email . '.' : 'Banking details are below.'];
        $order['bank_requested_at'] = date('Y-m-d H:i:s');
    }

    if ($action === 'pop' && !empty($_FILES['pop']['name'])) {
        $check = validate_upload($_FILES['pop'], ALLOWED_POP_TYPES);
        if (!$check['ok']) {
            $flash = ['danger', 'Please upload a photo (JPG or PNG) or a PDF of your proof of payment, up to 8 MB.'];
        } else {
            // Proof of payment is private: the folder is locked from the web; staff open it through the admin.
            $dir = UPLOAD_PATH . 'pop/';
            if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
            if (!is_file($dir . '.htaccess')) { @file_put_contents($dir . '.htaccess', "Require all denied\nDeny from all\n"); }
            $name = $ref . '-' . bin2hex(random_bytes(6)) . '.' . strtolower(pathinfo((string) $_FILES['pop']['name'], PATHINFO_EXTENSION));
            if (move_uploaded_file($_FILES['pop']['tmp_name'], $dir . $name)) {
                db_update('enquiries', (int) $order['id'], ['pop_file' => $name, 'pop_uploaded_at' => date('Y-m-d H:i:s')]);
                Mailer::send(ADMIN_EMAIL, 'Proof of payment uploaded — ' . $ref,
                    email_wrap('<p>' . e((string) $order['name']) . ' uploaded proof of payment for order <b>' . $ref . '</b>.</p><p><a href="' . SITE_URL . '/dashboards/admin/enquiries.php?id=' . (int) $order['id'] . '">Check it and mark the order sold</a></p>'));
                $flash = ['success', 'Thank you — we have your proof of payment. We will confirm on WhatsApp and dispatch your order.'];
                $order['pop_uploaded_at'] = date('Y-m-d H:i:s');
            } else {
                $flash = ['danger', 'The upload failed. Please try again or send it to us on WhatsApp.'];
            }
        }
    }
}

$paid = $order['status'] === 'sold';
$page_title  = 'Order ' . $ref . ' — ' . SITE_NAME;
$meta_robots = 'noindex';
include __DIR__ . '/includes/header.php';
?>

<header class="page-head">
  <div class="container">
    <nav class="crumbs"><a href="<?= SITE_URL ?>/index.php">Home</a><i class="fa-solid fa-chevron-right"></i><span>Order <?= $ref ?></span></nav>
    <h1><?= $paid ? 'Order ' . $ref . ' is paid — thank you!' : 'Order ' . $ref . ' received' ?></h1>
    <p>Keep this page: it's your order. We have also saved it on our side, so nothing is lost if WhatsApp doesn't open.</p>
  </div>
</header>

<div class="section section--tight">
  <div class="container">
    <?php if ($flash): ?><div class="alert-fc alert-fc--<?= $flash[0] ?>"><i class="fa-solid fa-circle-info"></i><span><?= e($flash[1]) ?></span></div><?php endif; ?>

    <div class="cart-layout">
      <div>
        <!-- 1. WhatsApp -->
        <div class="order-step <?= $paid ? 'is-done' : '' ?>">
          <span class="order-step__n">1</span>
          <div>
            <h2>Send your order on WhatsApp</h2>
            <p>Your order is typed in for you — just press send. We confirm stock and the delivery cost.</p>
            <a class="btn-fc btn-fc--whatsapp btn-fc--lg" href="<?= e(wa_link($message)) ?>" target="_blank" rel="noopener"><i class="fa-brands fa-whatsapp"></i> Send order <?= $ref ?></a>
          </div>
        </div>

        <!-- 2. Banking details -->
        <div class="order-step <?= $order['bank_requested_at'] ? 'is-done' : '' ?>">
          <span class="order-step__n">2</span>
          <div style="flex:1;min-width:0">
            <h2>Request banking details</h2>
            <?php if (!$bank): ?>
              <p>We'll send our banking details on WhatsApp with your confirmed total.</p>
            <?php elseif ($order['bank_requested_at']): ?>
              <p>Pay by EFT using <b><?= $ref ?></b> as the reference, once we've confirmed the total on WhatsApp.</p>
              <div class="bank-box"><?= bank_details_html($bank, $ref) ?></div>
              <button class="btn-fc btn-fc--ghost btn-fc--sm mt-2" type="button" onclick="navigator.clipboard.writeText(<?= e(json_encode($bank['number'])) ?>);this.textContent='Account number copied'"><i class="fa-solid fa-copy"></i> Copy account number</button>
            <?php else: ?>
              <p>Pay by EFT. Request the details and we'll show them here and email them to you.</p>
              <form method="post" class="d-flex gap-2 flex-wrap">
                <?= csrf_field() ?><input type="hidden" name="action" value="bank">
                <input class="input-fc" type="email" name="email" value="<?= e((string) ($order['email'] ?? '')) ?>" placeholder="Email (optional)" style="flex:1;min-width:200px">
                <button class="btn-fc" type="submit"><i class="fa-solid fa-building-columns"></i> Request banking details</button>
              </form>
            <?php endif; ?>
          </div>
        </div>

        <!-- 3. Proof of payment -->
        <div class="order-step <?= $order['pop_uploaded_at'] ? 'is-done' : '' ?>" id="pop">
          <span class="order-step__n">3</span>
          <div style="flex:1;min-width:0">
            <h2>Upload proof of payment</h2>
            <?php if ($order['pop_uploaded_at']): ?>
              <p><i class="fa-solid fa-circle-check text-leaf me-1"></i>Received on <?= date('j M Y, H:i', strtotime($order['pop_uploaded_at'])) ?>. We dispatch once it reflects — usually the same or next working day.</p>
            <?php else: ?>
              <p>A screenshot or PDF from your banking app is perfect.</p>
              <form method="post" enctype="multipart/form-data" class="d-flex gap-2 flex-wrap">
                <?= csrf_field() ?><input type="hidden" name="action" value="pop">
                <input class="input-fc" type="file" name="pop" accept="image/jpeg,image/png,application/pdf" required style="flex:1;min-width:200px">
                <button class="btn-fc" type="submit"><i class="fa-solid fa-upload"></i> Upload</button>
              </form>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="summary">
        <div class="card-fc card-fc--pad">
          <h2 style="font-size:1.15rem">Order <?= $ref ?></h2>
          <?php foreach ($items as $i): ?>
            <div class="summary-row"><span><?= (int) $i['qty'] ?> × <?= e($i['name']) ?></span><b><?= format_price_short((float) $i['price'] * (int) $i['qty']) ?></b></div>
          <?php endforeach; ?>
          <?php if ($discount > 0): ?><div class="summary-row"><span>Rewards points (<?= (int) $order['points_redeemed'] ?>)</span><b>−<?= format_price_short($discount) ?></b></div><?php endif; ?>
          <div class="summary-row"><span>Delivery</span><b><?= $order['delivery_fee'] !== null ? format_price_short((float) $order['delivery_fee']) : 'Confirmed on WhatsApp' ?></b></div>
          <div class="summary-total"><span><?= $order['delivery_fee'] !== null ? 'Total to pay' : 'Products total' ?></span>
            <b class="money"><?= format_price(max(0, $products - $discount + (float) ($order['delivery_fee'] ?? 0))) ?></b></div>
          <?php if ($delivery): ?>
            <p class="field__hint mt-2"><i class="fa-solid fa-truck-fast text-leaf me-1"></i><?= e($delivery['label']) ?><br><?= e($delivery['address']) ?><?= $delivery['country'] !== 'South Africa' ? ', ' . e($delivery['country']) : '' ?></p>
          <?php endif; ?>
          <?php if ($recip): ?><p class="field__hint"><i class="fa-solid fa-gift text-leaf me-1"></i>For <?= e($recip['name']) ?><?= $recip['phone'] ? ' · ' . e($recip['phone']) : '' ?></p><?php endif; ?>
          <?php if (!current_user()): ?>
            <a class="btn-fc btn-fc--light btn-fc--block mt-2" href="<?= SITE_URL ?>/modules/auth/register.php"><i class="fa-solid fa-coins"></i> Create a free account to earn points</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
