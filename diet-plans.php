<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$plans  = require __DIR__ . '/includes/content/diet-plans.php';
$repo   = new ProductRepository();
$viewer = current_user();
$wish   = $viewer ? wishlist_ids((int) $viewer['id']) : [];

$page_title       = 'Diet plans for chronic conditions — ' . SITE_NAME;
$meta_description = 'Free, practical diet plans for people living with diabetes, high blood pressure, HIV, TB, cancer, heart disease, kidney disease and arthritis — built on everyday South African food.';
include __DIR__ . '/includes/header.php';
?>

<header class="page-head">
  <div class="container">
    <nav class="crumbs"><a href="<?= SITE_URL ?>/index.php">Home</a><i class="fa-solid fa-chevron-right"></i><span>Diet plans</span></nav>
    <h1>Diet plans for chronic conditions</h1>
    <p>Simple eating plans built on everyday South African food. Pick your condition to see what to eat more of, what to limit, and a sample day.</p>
  </div>
</header>

<nav class="ph-rail" aria-label="Conditions" style="position:sticky;top:var(--header-h);z-index:5">
  <div class="container ph-rail__row">
    <?php foreach ($plans as $key => $p): ?>
      <a href="#<?= e($key) ?>" class="ph-need"><span style="color:<?= e($p['colour']) ?>"><i class="fa-solid <?= e($p['icon']) ?>"></i></span><b><?= e($p['name']) ?></b></a>
    <?php endforeach; ?>
  </div>
</nav>

<div class="section">
  <div class="container">
    <div class="alert-fc alert-fc--info mb-4"><i class="fa-solid fa-circle-info"></i>
      <span>These plans are general guidance, not a prescription. Your clinic or a registered dietitian may give you different targets — always follow theirs, especially for kidney disease and during cancer treatment.</span>
    </div>

    <?php foreach ($plans as $key => $p):
      $products = array_values(array_filter(array_map(fn($slug) => $repo->getBySlug($slug), $p['products']),
          fn($x) => $x && $x['status'] === 'published')); ?>
    <section class="diet" id="<?= e($key) ?>" style="--c:<?= e($p['colour']) ?>">
      <div class="diet__head">
        <span class="diet__icon"><i class="fa-solid <?= e($p['icon']) ?>"></i></span>
        <div>
          <h2><?= e($p['name']) ?></h2>
          <p><?= e($p['aim']) ?></p>
        </div>
      </div>

      <div class="diet__grid">
        <div class="diet__box diet__box--more">
          <h3><i class="fa-solid fa-circle-check"></i> Eat more</h3>
          <ul><?php foreach ($p['more'] as $x): ?><li><?= e($x) ?></li><?php endforeach; ?></ul>
        </div>
        <div class="diet__box diet__box--less">
          <h3><i class="fa-solid fa-circle-minus"></i> Limit</h3>
          <ul><?php foreach ($p['less'] as $x): ?><li><?= e($x) ?></li><?php endforeach; ?></ul>
        </div>
        <div class="diet__box diet__box--day">
          <h3><i class="fa-regular fa-calendar"></i> A sample day</h3>
          <dl>
            <?php foreach ($p['day'] as $meal => $food): ?><dt><?= e($meal) ?></dt><dd><?= e($food) ?></dd><?php endforeach; ?>
          </dl>
        </div>
      </div>

      <p class="diet__tip"><i class="fa-solid fa-lightbulb"></i> <b>Tip:</b> <?= e($p['tip']) ?></p>

      <?php if ($products): ?>
        <div class="diet__shop">
          <span class="eyebrow">Supports this plan</span>
          <div class="grid-products">
            <?php foreach (array_slice($products, 0, 4) as $product):
              $in_wishlist = in_array((int) $product['id'], $wish, true);
              include __DIR__ . '/includes/product-card.php';
            endforeach; ?>
          </div>
        </div>
      <?php endif; ?>
    </section>
    <?php endforeach; ?>

    <div class="ph-band mt-4">
      <div>
        <span class="ph-kicker"><i class="fa-solid fa-bell"></i> Free account</span>
        <h2>Track your treatment and readings</h2>
        <p>Sign up free with Google, Facebook or your cellphone. Get a daily wellness quote, dose reminders and a simple chart of your sugar, blood pressure and weight.</p>
      </div>
      <div class="ph-band__cta">
        <a href="<?= SITE_URL ?>/modules/auth/register.php" class="btn-fc btn-fc--lg ph-btn-lime">Create my free account</a>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
