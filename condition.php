<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$hubs = condition_hubs();
$key  = (string) ($_GET['c'] ?? '');

// No condition chosen: the index of all hubs.
if (!isset($hubs[$key])) {
    $page_title       = 'Health conditions — guides, diet plans and products | ' . SITE_NAME;
    $meta_description = 'Everything in one place for diabetes, high blood pressure, HIV, TB, cancer care and arthritis: plain-language guides, diet plans, calculators and supporting products.';
    include __DIR__ . '/includes/header.php'; ?>
<header class="page-head"><div class="container">
  <nav class="crumbs"><a href="<?= SITE_URL ?>/index.php">Home</a><i class="fa-solid fa-chevron-right"></i><span>Conditions</span></nav>
  <h1>Health conditions</h1><p>Choose a condition for a guide, a diet plan, a calculator, supporting products and the latest news.</p>
</div></header>
<div class="section"><div class="container hub-grid">
  <?php foreach ($hubs as $k => $h): ?>
    <a class="hub-tile" href="<?= SITE_URL ?>/condition.php?c=<?= e($k) ?>"><i class="fa-solid <?= e($h['icon']) ?>"></i><b><?= e($h['name']) ?></b><span><?= e(excerpt($h['intro'], 110)) ?></span></a>
  <?php endforeach; ?>
</div></div>
<?php include __DIR__ . '/includes/footer.php'; exit;
}

$h      = $hubs[$key];
$plans  = require __DIR__ . '/includes/content/diet-plans.php';
$plan   = $plans[$h['diet']] ?? null;
$viewer = current_user();
$wish   = $viewer ? wishlist_ids((int) $viewer['id']) : [];
$products = (new ProductRepository())->getAll(['category' => $h['category'], 'sort' => 'popular'], 1, 4)['items'];

$slugs = array_merge([$h['article']], $h['extra']);
$in    = implode(',', array_fill(0, count($slugs), '?'));
$st    = db()->prepare("SELECT title, slug, image, excerpt, read_mins FROM blogs WHERE status = 'published' AND slug IN ($in)");
$st->execute($slugs);
$guides = $st->fetchAll();

$like = implode(' OR ', array_fill(0, count($h['news']), 'LOWER(CONCAT(" ", title, " ", COALESCE(summary, ""), " ")) LIKE ?'));
$st   = db()->prepare("SELECT n.*, s.name source_name FROM health_news n LEFT JOIN rss_sources s ON s.id = n.source_id WHERE n.is_hidden = 0 AND ($like) ORDER BY n.published_at DESC LIMIT 6");
$st->execute(array_map(fn($w) => '%' . strtolower($w) . '%', $h['news']));
$news = $st->fetchAll();

$questions = db()->prepare("SELECT slug, question FROM questions WHERE status = 'published' AND condition_tag = :c ORDER BY answered_at DESC LIMIT 5");
$questions->execute([':c' => $key]);
$questions = $questions->fetchAll();

$page_title       = $h['title'] . ' — guide, diet plan & products | ' . SITE_NAME;
$meta_description = excerpt($h['intro'], 155);
$og_image         = $guides ? product_image($guides[0]['image']) : null;
include __DIR__ . '/includes/header.php';
?>

<header class="page-head">
  <div class="container">
    <nav class="crumbs"><a href="<?= SITE_URL ?>/index.php">Home</a><i class="fa-solid fa-chevron-right"></i><a href="<?= SITE_URL ?>/condition.php">Conditions</a><i class="fa-solid fa-chevron-right"></i><span><?= e($h['name']) ?></span></nav>
    <h1><?= e($h['title']) ?></h1>
    <p><?= e($h['intro']) ?></p>
  </div>
</header>

<div class="section section--tight">
  <div class="container">
    <div class="cond-facts">
      <?php foreach ($h['facts'] as $f): ?><div><i class="fa-solid fa-circle-check"></i><?= e($f) ?></div><?php endforeach; ?>
    </div>

    <div class="cond-grid">
      <div>
        <?php foreach ($guides as $g): ?>
          <a class="post-card post-card--row mb-3" href="<?= SITE_URL ?>/modules/blog/post.php?slug=<?= e($g['slug']) ?>">
            <div class="post-card__media"><img src="<?= e(product_image($g['image'])) ?>" alt="" loading="lazy"></div>
            <div class="post-card__body"><span class="eyebrow">Guide · <?= (int) $g['read_mins'] ?> min read</span><h3><?= e($g['title']) ?></h3><p><?= e(excerpt($g['excerpt'], 140)) ?></p></div>
          </a>
        <?php endforeach; ?>

        <?php if ($plan): ?>
        <div class="card-fc card-fc--pad mb-3">
          <span class="eyebrow">Diet plan</span>
          <h2 style="font-size:1.4rem"><?= e($plan['aim']) ?></h2>
          <div class="diet__grid mt-3">
            <div class="diet__box diet__box--more"><h3><i class="fa-solid fa-circle-check"></i> Eat more</h3><ul><?php foreach (array_slice($plan['more'], 0, 4) as $x): ?><li><?= e($x) ?></li><?php endforeach; ?></ul></div>
            <div class="diet__box diet__box--less"><h3><i class="fa-solid fa-circle-minus"></i> Limit</h3><ul><?php foreach (array_slice($plan['less'], 0, 4) as $x): ?><li><?= e($x) ?></li><?php endforeach; ?></ul></div>
          </div>
          <a class="ph-link mt-3" href="<?= SITE_URL ?>/diet-plans.php#<?= e($h['diet']) ?>">Full plan with a sample day <i class="fa-solid fa-arrow-right"></i></a>
        </div>
        <?php endif; ?>

        <?php if ($products): ?>
        <div class="ph-head mt-4"><div><span class="eyebrow">Supporting products</span><h2 style="font-size:1.5rem">Shop for <?= e(strtolower($h['name'])) ?></h2></div>
          <a class="ph-link" href="<?= SITE_URL ?>/shop.php?category=<?= e($h['category']) ?>">See all <i class="fa-solid fa-arrow-right"></i></a></div>
        <div class="grid-products">
          <?php foreach ($products as $product): $in_wishlist = in_array((int) $product['id'], $wish, true); include __DIR__ . '/includes/product-card.php'; endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

      <aside>
        <a class="ph-band d-block mb-3" href="<?= SITE_URL ?>/health-tools.php#<?= e($h['tool']) ?>" style="padding:1.4rem">
          <span class="ph-kicker"><i class="fa-solid fa-calculator"></i> Free tool</span>
          <h2 style="font-size:1.3rem">Check your numbers</h2>
          <p style="font-size:.9rem">Use our free calculator — it takes 10 seconds.</p>
        </a>

        <div class="card-fc card-fc--pad mb-3">
          <span class="eyebrow">Ask a pharmacist</span>
          <?php foreach ($questions as $q): ?>
            <a class="news-guide" href="<?= SITE_URL ?>/ask.php?q=<?= e($q['slug']) ?>"><span><b><?= e($q['question']) ?></b></span></a>
          <?php endforeach; ?>
          <a class="btn-fc btn-fc--sm mt-2" href="<?= SITE_URL ?>/ask.php?condition=<?= e($key) ?>#ask"><i class="fa-solid fa-circle-question"></i> Ask your question</a>
        </div>

        <?php if ($news): ?>
        <div class="card-fc card-fc--pad">
          <span class="eyebrow">In the news</span>
          <?php foreach ($news as $n): ?>
            <a class="news-guide" href="<?= e($n['url']) ?>" target="_blank" rel="noopener nofollow"><img src="<?= e(news_image($n)) ?>" alt="" loading="lazy" referrerpolicy="no-referrer" onerror="this.onerror=null;this.src='<?= SITE_URL ?>/assets/img/stock/pharmacy.jpg'"><span><b><?= e($n['title']) ?></b><small><?= e((string) $n['source_name']) ?></small></span></a>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </aside>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
