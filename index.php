<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$repo        = new ProductRepository();
$viewer      = current_user();
$wish        = $viewer ? wishlist_ids((int) $viewer['id']) : [];

$featured    = $repo->getFeatured(8);
$bestsellers = $repo->getBestsellers(8);
$categories  = get_categories();

$testimonials = db()->query("SELECT * FROM testimonials WHERE status='published' ORDER BY sort_order, id LIMIT 4")->fetchAll();
$posts        = get_blogs([], 1, 3)['items'];
$productCount = (int) db()->query("SELECT COUNT(*) FROM products WHERE status='published'")->fetchColumn();
$waDisplay    = Health::displayPhone(whatsapp_number());

// Shop-window slides: featured products that have a wide in-store photo.
$slides = array_values(array_filter($featured, fn($p) => !empty($p['header_image'])));
// Two big category tiles for the spotlight row, the rest go in the rail.
$spot = array_slice(array_values(array_filter($categories, fn($c) => (int) $c['product_count'] > 0)), 0, 2);

$socials = array_filter([
    'Facebook'  => [get_setting('facebook_url', ''),  'fa-facebook-f'],
    'Instagram' => [get_setting('instagram_url', ''), 'fa-instagram'],
    'TikTok'    => [get_setting('tiktok_url', ''),    'fa-tiktok'],
    'YouTube'   => [get_setting('youtube_url', ''),   'fa-youtube'],
    'X'         => [get_setting('x_url', ''),         'fa-x-twitter'],
    'LinkedIn'  => [get_setting('linkedin_url', ''),  'fa-linkedin-in'],
], fn($s) => $s[0] !== '');

$page_title       = 'Phola Health Solutions — Health & Wellness, Delivered.';
$meta_description = 'Shop immune support, blood sugar, heart, brain, men\'s and women\'s health supplements online. Clear prices, order on WhatsApp, courier delivery across South Africa.';
$og_image         = SITE_URL . '/assets/img/products/01.jpg';
$json_ld = [
    '@type' => 'WebSite',
    'name'  => SITE_NAME,
    'url'   => SITE_URL,
    'potentialAction' => [
        '@type'       => 'SearchAction',
        'target'      => SITE_URL . '/shop.php?q={search_term_string}',
        'query-input' => 'required name=search_term_string',
    ],
];
include __DIR__ . '/includes/header.php';
?>

<!-- ── Hero: text + shop-window slider ─────────────────────────────── -->
<section class="ph-hero">
  <div class="container ph-hero__grid">
    <div class="ph-hero__copy">
      <span class="ph-kicker"><i class="fa-solid fa-leaf"></i> Online health store · South Africa</span>
      <h1>Health &amp; wellness,<br><em>delivered.</em></h1>
      <p>
        <?= $productCount ?> supplements and wellness products with honest labels and clear prices.
        Choose online, confirm on WhatsApp, and we courier it to your door.
      </p>
      <form class="ph-hero__search" action="<?= SITE_URL ?>/shop.php" method="get" role="search">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="search" name="q" placeholder="Search immune, blood sugar, magnesium…" aria-label="Search products">
        <button type="submit">Search</button>
      </form>
      <div class="ph-hero__cta">
        <a href="<?= SITE_URL ?>/shop.php" class="btn-fc btn-fc--lg ph-btn-lime"><i class="fa-solid fa-basket-shopping"></i> Shop all products</a>
        <a href="<?= e(wa_link("Hi Phola, I'd like some help choosing a product.")) ?>" target="_blank" rel="noopener" class="btn-fc btn-fc--lg btn-fc--on-dark">
          <i class="fa-brands fa-whatsapp"></i> <?= e($waDisplay) ?>
        </a>
      </div>
    </div>

    <?php if ($slides): ?>
    <div class="ph-window" data-window>
      <div class="ph-window__track">
        <?php foreach ($slides as $i => $s): ?>
        <a class="ph-window__slide" href="<?= SITE_URL ?>/modules/shop/product.php?slug=<?= e($s['slug']) ?>" aria-label="<?= e($s['name']) ?>">
          <img src="<?= e(product_image($s['header_image'])) ?>" alt="<?= e($s['name']) ?>" <?= $i ? 'loading="lazy"' : 'fetchpriority="high"' ?>>
          <span class="ph-window__tag">
            <b><?= e($s['name']) ?></b>
            <span class="money"><?= format_price_short(Pricing::retail($s)) ?></span>
          </span>
        </a>
        <?php endforeach; ?>
      </div>
      <div class="ph-window__dots" role="tablist">
        <?php foreach ($slides as $i => $s): ?><button type="button" aria-label="Slide <?= $i + 1 ?>" class="<?= $i ? '' : 'is-on' ?>"></button><?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
</section>

<!-- ── Need rail ────────────────────────────────────────────────────── -->
<nav class="ph-rail" aria-label="Shop by need">
  <div class="container ph-rail__row">
    <?php foreach ($categories as $c): if ((int) $c['product_count'] === 0) continue; ?>
      <a href="<?= SITE_URL ?>/shop.php?category=<?= e($c['slug']) ?>" class="ph-need">
        <span><i class="fa-solid <?= e($c['icon'] ?: 'fa-tag') ?>"></i></span>
        <b><?= e($c['name']) ?></b>
      </a>
    <?php endforeach; ?>
  </div>
</nav>

<!-- ── Promise strip ────────────────────────────────────────────────── -->
<section class="ph-promise">
  <div class="container ph-promise__grid">
    <div><i class="fa-brands fa-whatsapp"></i><b>Order on WhatsApp</b><span>A real person confirms every order</span></div>
    <div><i class="fa-solid fa-truck-fast"></i><b>Nationwide courier</b><span>2–4 working days, tracking on WhatsApp</span></div>
    <div><i class="fa-solid fa-tag"></i><b>Clear prices</b><span>Free delivery over <?= format_price_short((float) get_setting('free_shipping_threshold', '1000')) ?></span></div>
    <div><i class="fa-solid fa-user-shield"></i><b>Private &amp; POPIA-safe</b><span>Your health details stay yours</span></div>
  </div>
</section>

<!-- ── Featured ─────────────────────────────────────────────────────── -->
<section class="section">
  <div class="container">
    <div class="ph-head">
      <div>
        <span class="eyebrow">Hand-picked</span>
        <h2>Customer favourites</h2>
      </div>
      <a href="<?= SITE_URL ?>/shop.php?sort=featured" class="ph-link">See all products <i class="fa-solid fa-arrow-right"></i></a>
    </div>
    <div class="grid-products">
      <?php foreach ($featured as $product):
        $in_wishlist = in_array((int) $product['id'], $wish, true);
        include __DIR__ . '/includes/product-card.php';
      endforeach; ?>
    </div>
  </div>
</section>

<!-- ── Category spotlight (bento) ───────────────────────────────────── -->
<?php if (count($spot) === 2): ?>
<section class="section section--tight">
  <div class="container ph-bento">
    <?php foreach ($spot as $i => $c): ?>
    <a class="ph-bento__tile <?= $i ? 'is-alt' : '' ?>" href="<?= SITE_URL ?>/shop.php?category=<?= e($c['slug']) ?>" data-reveal>
      <img src="<?= e(product_image($c['image'])) ?>" alt="" loading="lazy">
      <span class="ph-bento__text">
        <span class="ph-kicker"><i class="fa-solid <?= e($c['icon'] ?: 'fa-tag') ?>"></i> <?= (int) $c['product_count'] ?> products</span>
        <b><?= e($c['name']) ?></b>
        <span><?= e($c['blurb'] ?? '') ?></span>
        <span class="ph-link">Shop now <i class="fa-solid fa-arrow-right"></i></span>
      </span>
    </a>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<!-- ── Best sellers ─────────────────────────────────────────────────── -->
<?php if ($bestsellers): ?>
<section class="section ph-tint">
  <div class="container">
    <div class="ph-head">
      <div>
        <span class="eyebrow">Reordered most</span>
        <h2>Best sellers</h2>
      </div>
      <a href="<?= SITE_URL ?>/shop.php?sort=popular" class="ph-link">Full ranking <i class="fa-solid fa-arrow-right"></i></a>
    </div>
    <div class="grid-products">
      <?php foreach ($bestsellers as $product):
        $in_wishlist = in_array((int) $product['id'], $wish, true);
        include __DIR__ . '/includes/product-card.php';
      endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ── How ordering works ───────────────────────────────────────────── -->
<section class="section">
  <div class="container">
    <div class="ph-head ph-head--center">
      <div>
        <span class="eyebrow">How it works</span>
        <h2>Three steps, one WhatsApp chat</h2>
        <p class="lede">No card details on a website. We confirm stock, total and delivery with you first.</p>
      </div>
    </div>
    <div class="ph-steps">
      <?php foreach ([
        ['fa-basket-shopping', 'Pick your products', 'Tap Enquire on a product, or add several to your list and send them together.'],
        ['fa-brands fa-whatsapp', 'Confirm on WhatsApp', 'Your order arrives at ' . $waDisplay . ' with products and prices filled in. We send payment details.'],
        ['fa-box-open', 'Delivered to your door', 'We courier nationwide in 2–4 working days and send the tracking number on WhatsApp.'],
      ] as $i => [$icon, $title, $text]): ?>
      <div class="ph-step" data-reveal>
        <span class="ph-step__n"><?= $i + 1 ?></span>
        <i class="<?= str_starts_with($icon, 'fa-brands') ? $icon : 'fa-solid ' . $icon ?>"></i>
        <h3><?= e($title) ?></h3>
        <p><?= e($text) ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ── Reminders band ───────────────────────────────────────────────── -->
<section class="section section--tight">
  <div class="container">
    <div class="ph-band" data-reveal>
      <div>
        <span class="ph-kicker"><i class="fa-solid fa-bell"></i> Free with every order</span>
        <h2>Never miss a dose.</h2>
        <p>We set up your routine after you buy. Sign in with your cellphone number and a 4-digit PIN to get email reminders, tick off doses and track how you feel.</p>
      </div>
      <div class="ph-band__cta">
        <a href="<?= SITE_URL ?>/modules/auth/login.php" class="btn-fc btn-fc--lg ph-btn-lime"><i class="fa-solid fa-mobile-screen"></i> Sign in</a>
        <a href="<?= SITE_URL ?>/modules/auth/register.php" class="btn-fc btn-fc--lg btn-fc--on-dark">Create my account</a>
      </div>
    </div>
  </div>
</section>

<!-- ── Testimonials ─────────────────────────────────────────────────── -->
<?php if ($testimonials): ?>
<section class="section">
  <div class="container">
    <div class="ph-head ph-head--center"><div><span class="eyebrow">Reviews</span><h2>What customers say</h2></div></div>
    <div class="row g-3">
      <?php foreach ($testimonials as $t): ?>
      <div class="col-lg-3 col-md-6" data-reveal>
        <div class="quote-card">
          <?= stars_html((float) $t['rating']) ?>
          <p class="mt-2">&ldquo;<?= e($t['quote']) ?>&rdquo;</p>
          <div class="quote-card__who">
            <span class="quote-card__av"><?= e(mb_substr($t['name'], 0, 1)) ?></span>
            <span><b><?= e($t['name']) ?></b><span><?= e((string) $t['location']) ?></span></span>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ── Learn ────────────────────────────────────────────────────────── -->
<?php if ($posts): ?>
<section class="section ph-tint">
  <div class="container">
    <div class="ph-head">
      <div><span class="eyebrow">Wellness journal</span><h2>Worth reading</h2></div>
      <a href="<?= SITE_URL ?>/modules/blog/index.php" class="ph-link">All articles <i class="fa-solid fa-arrow-right"></i></a>
    </div>
    <div class="row g-3">
      <?php foreach ($posts as $post): ?>
      <div class="col-lg-4 col-md-6" data-reveal>
        <a class="post-card" href="<?= SITE_URL ?>/modules/blog/post.php?slug=<?= e($post['slug']) ?>">
          <div class="post-card__media"><img src="<?= e(product_image($post['image'])) ?>" alt="<?= e($post['title']) ?>" loading="lazy"></div>
          <div class="post-card__body">
            <h3><?= e($post['title']) ?></h3>
            <p><?= e(excerpt($post['excerpt'], 110)) ?></p>
            <div class="post-card__meta"><span><i class="fa-regular fa-clock me-1"></i><?= (int) $post['read_mins'] ?> min read</span></div>
          </div>
        </a>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ── Newsletter + social ──────────────────────────────────────────── -->
<section class="section">
  <div class="container">
    <div class="ph-join" data-reveal>
      <div>
        <span class="eyebrow">Join the Phola family</span>
        <h2>Specials and new arrivals, first.</h2>
        <p>One short email a fortnight. Unsubscribe any time.</p>
        <?php if ($socials): ?>
        <div class="ph-follow">
          <span>Follow us</span>
          <?php foreach ($socials as $label => [$url, $icon]): ?>
            <a href="<?= e($url) ?>" target="_blank" rel="noopener" aria-label="<?= e($label) ?>"><i class="fa-brands <?= e($icon) ?>"></i></a>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
      <form class="ph-join__form" data-newsletter="home">
        <input type="email" name="email" required placeholder="Your email address" aria-label="Email address">
        <button class="btn-fc ph-btn-lime" type="submit">Subscribe</button>
        <div data-newsletter-msg class="ph-join__msg"></div>
      </form>
    </div>
  </div>
</section>

<script>
// Shop-window slider: swipe/scroll natively, auto-advance every 5 s, dots follow.
(function () {
  var w = document.querySelector('[data-window]'); if (!w) return;
  var track = w.querySelector('.ph-window__track'), dots = w.querySelectorAll('.ph-window__dots button'), i = 0, timer;
  function go(n) { i = (n + dots.length) % dots.length; track.scrollTo({ left: track.clientWidth * i, behavior: 'smooth' }); }
  function mark() { i = Math.round(track.scrollLeft / track.clientWidth); dots.forEach(function (d, k) { d.classList.toggle('is-on', k === i); }); }
  dots.forEach(function (d, k) { d.addEventListener('click', function () { go(k); restart(); }); });
  track.addEventListener('scroll', function () { window.requestAnimationFrame(mark); }, { passive: true });
  function restart() { clearInterval(timer); if (!matchMedia('(prefers-reduced-motion: reduce)').matches) timer = setInterval(function () { go(i + 1); }, 5000); }
  w.addEventListener('mouseenter', function () { clearInterval(timer); });
  w.addEventListener('mouseleave', restart);
  restart();
})();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
