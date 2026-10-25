<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

// For South African-based workers buying for family in Zimbabwe, Malawi,
// Mozambique and the rest of SADC.
$depots  = require __DIR__ . '/includes/content/sadc-depots.php';
$repo    = new ProductRepository();
$popular = $repo->getBestsellers(8);
$viewer  = current_user();
$wish    = $viewer ? wishlist_ids((int) $viewer['id']) : [];

$page_title       = 'Send health products to family in Zimbabwe, Malawi, Mozambique & SADC | ' . SITE_NAME;
$meta_description = 'Buy supplements in South Africa and send them to your family\'s nearest bus depot in Zimbabwe, Malawi, Mozambique, Botswana, Zambia, Lesotho, Eswatini or Namibia — or anywhere in the world with DHL.';
include __DIR__ . '/includes/header.php';
?>

<section class="ph-hero" style="padding:clamp(2rem,5vw,3.5rem) 0">
  <div class="container" style="position:relative;z-index:1;max-width:860px">
    <span class="ph-kicker"><i class="fa-solid fa-heart"></i> Look after family back home</span>
    <h1>Send health products to your family — <em>wherever they are.</em></h1>
    <p style="color:var(--on-dark-2);font-size:1.05rem;max-width:60ch">Order and pay here in South Africa. We pack the parcel in your family's name and send it with a trusted cross-border bus or taxi to the depot nearest them — or with DHL anywhere in the world. They just collect it with their ID.</p>
    <div class="ph-hero__cta mt-3">
      <a href="<?= SITE_URL ?>/shop.php" class="btn-fc btn-fc--lg ph-btn-lime"><i class="fa-solid fa-basket-shopping"></i> Choose products</a>
      <a href="<?= e(wa_link("Hi Phola, I'd like to send products to my family in ")) ?>" target="_blank" rel="noopener" class="btn-fc btn-fc--lg btn-fc--on-dark"><i class="fa-brands fa-whatsapp"></i> Ask us first</a>
    </div>
  </div>
</section>

<div class="section">
  <div class="container">
    <div class="ph-steps">
      <?php foreach ([
        ['fa-basket-shopping', 'Fill your basket', 'Choose the products, then pick "Bus / taxi depot" or DHL as the delivery.'],
        ['fa-gift', 'Tick "Sending to someone else"', 'Add your family member\'s name and cellphone. We put the parcel in their name.'],
        ['fa-bus', 'They collect at the depot', 'We send you both the reference and the bus or taxi details. They collect with their ID.'],
      ] as $i => [$icon, $title, $text]): ?>
      <div class="ph-step"><span class="ph-step__n"><?= $i + 1 ?></span><i class="fa-solid <?= $icon ?>"></i><h3><?= e($title) ?></h3><p><?= e($text) ?></p></div>
      <?php endforeach; ?>
    </div>

    <div class="ph-head mt-5"><div><span class="eyebrow">Where we send</span><h2>Popular routes from South Africa</h2></div>
      <a class="ph-link" href="<?= SITE_URL ?>/delivery.php#sadc">All delivery details <i class="fa-solid fa-arrow-right"></i></a></div>
    <div class="depot-grid">
      <?php foreach (array_slice($depots, 0, 6, true) as $country => [$flag, $routes]): ?>
      <div class="depot-card"><h4><span aria-hidden="true"><?= $flag ?></span> <?= e($country) ?></h4>
        <ul><?php foreach (array_slice($routes, 0, 3) as [$from, $to]): ?><li><span><?= e($from) ?></span><i class="fa-solid fa-arrow-right"></i><b><?= e($to) ?></b></li><?php endforeach; ?></ul></div>
      <?php endforeach; ?>
    </div>

    <div class="ph-head mt-5"><div><span class="eyebrow">Most sent home</span><h2>Family favourites</h2></div></div>
    <div class="grid-products">
      <?php foreach ($popular as $product): $in_wishlist = in_array((int) $product['id'], $wish, true); include __DIR__ . '/includes/product-card.php'; endforeach; ?>
    </div>

    <div class="dv-faq mt-5">
      <details><summary>Can my family pay, or do I pay?</summary><p>You pay in South Africa by EFT — request the banking details after you send your order. Your family only collects.</p></details>
      <details><summary>Will there be duty at the border?</summary><p>Personal quantities of supplements usually cross without trouble, but customs rules differ by country. Any duty is paid by the receiver.</p></details>
      <details><summary>Can I set reminders for my mother's tablets?</summary><p>Yes — create a free Phola account in her name (or yours) and add her medication. Reminders go to the email you choose.</p></details>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
