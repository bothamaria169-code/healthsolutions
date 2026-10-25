<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$options = delivery_options();
$freeAt  = (float) get_setting('free_shipping_threshold', '1000');

$page_title       = 'Delivery — The Courier Guy & SADC depots | ' . SITE_NAME;
$meta_description = 'Phola delivers across South Africa with The Courier Guy — door-to-door, Pudo lockers, pick-up points and kiosks — and to SADC countries by bus and taxi to your nearest depot.';
include __DIR__ . '/includes/header.php';
?>

<header class="page-head">
  <div class="container">
    <nav class="crumbs"><a href="<?= SITE_URL ?>/index.php">Home</a><i class="fa-solid fa-chevron-right"></i><span>Delivery</span></nav>
    <h1>Delivery across South Africa and SADC</h1>
    <p>Choose how you want your order when you check out your basket. Free delivery in South Africa on orders over <?= format_price_short($freeAt) ?>.</p>
  </div>
</header>

<div class="section">
  <div class="container">
    <div class="ph-head"><div><span class="eyebrow">South Africa</span><h2>With The Courier Guy</h2></div></div>
    <div class="deliv-grid">
      <?php foreach ($options as $key => $o): if ($o['region'] !== 'za') continue; ?>
      <div class="deliv-card">
        <i class="fa-solid <?= e($o['icon']) ?>"></i>
        <h3><?= e($o['label']) ?></h3>
        <p><?= e($o['blurb']) ?></p>
        <span class="money"><?= format_price_short((float) $o['fee']) ?></span>
        <small>Free over <?= format_price_short($freeAt) ?></small>
      </div>
      <?php endforeach; ?>
    </div>
    <p class="text-muted-2 mt-3" style="font-size:.9rem"><i class="fa-solid fa-truck-fast me-1 text-leaf"></i>
      Usually 2–4 working days to main centres, up to 5 to outlying areas. Your tracking number comes on WhatsApp.
      Find your nearest locker, kiosk or pick-up point on <a class="ph-link" href="https://www.thecourierguy.co.za" target="_blank" rel="noopener">thecourierguy.co.za</a>.</p>

    <div class="ph-band mt-5">
      <div>
        <span class="ph-kicker"><i class="fa-solid fa-bus"></i> SADC countries</span>
        <h2>Bus &amp; taxi to your nearest depot</h2>
        <p>We send to <?= e(implode(', ', array_slice(SADC_COUNTRIES, 0, -1)) . ' and ' . SADC_COUNTRIES[count(SADC_COUNTRIES) - 1]) ?> with trusted long-distance bus and taxi services. Tell us your country and nearest depot at checkout and we quote the transport on WhatsApp before you pay.</p>
      </div>
      <div class="ph-band__cta">
        <a href="<?= SITE_URL ?>/shop.php" class="btn-fc btn-fc--lg ph-btn-lime"><i class="fa-solid fa-basket-shopping"></i> Start shopping</a>
        <a href="<?= e(wa_link("Hi Phola, I'm outside South Africa. Can you quote delivery to my nearest depot?")) ?>" target="_blank" rel="noopener" class="btn-fc btn-fc--lg btn-fc--on-dark"><i class="fa-brands fa-whatsapp"></i> Ask for a quote</a>
      </div>
    </div>

    <div class="ph-steps mt-5">
      <?php foreach ([
        ['fa-basket-shopping', 'Fill your basket', 'Add products and choose door, locker, pick-up point, kiosk or SADC depot.'],
        ['fa-brands fa-whatsapp', 'Confirm and pay', 'We confirm stock and the total on WhatsApp and send EFT details.'],
        ['fa-box-open', 'We dispatch', 'Packed the same or next working day, with tracking sent on WhatsApp.'],
      ] as $i => [$icon, $title, $text]): ?>
      <div class="ph-step"><span class="ph-step__n"><?= $i + 1 ?></span>
        <i class="<?= str_starts_with($icon, 'fa-brands') ? $icon : 'fa-solid ' . $icon ?>"></i><h3><?= e($title) ?></h3><p><?= e($text) ?></p></div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
