<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

// Headlines imported from health news feeds (Admin → Health news). We show the
// headline and a short summary only; the full story stays on the publisher's site.
$sources = db()->query('SELECT id, name FROM rss_sources WHERE is_active = 1 ORDER BY name')->fetchAll();
$src     = (int) ($_GET['source'] ?? 0);
$page    = max(1, (int) ($_GET['page'] ?? 1));
$per     = 18;

$where  = 'n.is_hidden = 0' . ($src ? ' AND n.source_id = :s' : '');
$params = $src ? [':s' => $src] : [];
$count  = db()->prepare("SELECT COUNT(*) FROM health_news n WHERE $where");
$count->execute($params);
$total  = (int) $count->fetchColumn();
$pages  = max(1, (int) ceil($total / $per));

$stmt = db()->prepare("SELECT n.*, s.name AS source_name FROM health_news n LEFT JOIN rss_sources s ON s.id = n.source_id
                       WHERE $where ORDER BY n.published_at DESC LIMIT $per OFFSET " . (($page - 1) * $per));
$stmt->execute($params);
$news = $stmt->fetchAll();

$guides = db()->query("SELECT title, slug, image, read_mins FROM blogs WHERE status = 'published' AND category = 'Chronic conditions' ORDER BY id LIMIT 8")->fetchAll();

$page_title       = 'Health news — ' . SITE_NAME;
$meta_description = 'The latest health and medical news from South Africa and the world, updated through the day, plus Phola guides on living with chronic conditions.';
include __DIR__ . '/includes/header.php';
?>

<header class="page-head">
  <div class="container">
    <nav class="crumbs"><a href="<?= SITE_URL ?>/index.php">Home</a><i class="fa-solid fa-chevron-right"></i><span>Health news</span></nav>
    <h1>Health news</h1>
    <p>Headlines from trusted health publishers, refreshed automatically. Tap a story to read it in full on the publisher's site.</p>
  </div>
</header>

<div class="section section--tight">
  <div class="container">
    <div class="ph-rail__row mb-3" style="padding-top:0">
      <a class="ph-need<?= $src ? '' : ' is-on' ?>" href="health-news.php"><span><i class="fa-solid fa-earth-africa"></i></span><b>All sources</b></a>
      <?php foreach ($sources as $s): ?>
        <a class="ph-need<?= $src === (int) $s['id'] ? ' is-on' : '' ?>" href="health-news.php?source=<?= (int) $s['id'] ?>"><span><i class="fa-solid fa-rss"></i></span><b><?= e($s['name']) ?></b></a>
      <?php endforeach; ?>
    </div>

    <div class="news-layout">
      <div>
        <?php if (!$news): ?>
          <div class="empty card-fc"><div class="empty__icon"><i class="fa-solid fa-newspaper"></i></div><h2>No news yet</h2><p>The first headlines arrive within a few hours.</p></div>
        <?php else: ?>
        <div class="news-grid">
          <?php foreach ($news as $n): ?>
          <a class="news-card" href="<?= e($n['url']) ?>" target="_blank" rel="noopener nofollow">
            <?php if ($n['image']): ?>
              <div class="news-card__img"><img src="<?= e($n['image']) ?>" alt="" loading="lazy" referrerpolicy="no-referrer" onerror="this.parentNode.remove()"></div>
            <?php endif; ?>
            <div class="news-card__body">
              <span class="news-card__src"><i class="fa-solid fa-rss"></i> <?= e((string) $n['source_name']) ?> · <?= $n['published_at'] ? date('j M', strtotime($n['published_at'])) : '' ?></span>
              <h3><?= e($n['title']) ?></h3>
              <?php if ($n['summary']): ?><p><?= e($n['summary']) ?></p><?php endif; ?>
              <span class="ph-link">Read on <?= e((string) parse_url($n['url'], PHP_URL_HOST)) ?> <i class="fa-solid fa-arrow-up-right-from-square"></i></span>
            </div>
          </a>
          <?php endforeach; ?>
        </div>

        <?php if ($pages > 1): ?>
        <nav class="d-flex gap-2 justify-content-center mt-4" aria-label="Pages">
          <?php for ($i = 1; $i <= $pages; $i++): ?>
            <a class="btn-fc btn-fc--sm <?= $i === $page ? '' : 'btn-fc--ghost' ?>" href="?<?= http_build_query(array_filter(['source' => $src ?: null, 'page' => $i])) ?>"><?= $i ?></a>
          <?php endfor; ?>
        </nav>
        <?php endif; ?>
        <?php endif; ?>
      </div>

      <aside class="news-side">
        <div class="card-fc card-fc--pad mb-3">
          <span class="eyebrow">Phola guides</span>
          <h2 style="font-size:1.25rem">Living with a chronic condition</h2>
          <?php foreach ($guides as $g): ?>
            <a class="news-guide" href="<?= SITE_URL ?>/modules/blog/post.php?slug=<?= e($g['slug']) ?>">
              <img src="<?= e(product_image($g['image'])) ?>" alt="" loading="lazy">
              <span><b><?= e($g['title']) ?></b><small><?= (int) $g['read_mins'] ?> min read</small></span>
            </a>
          <?php endforeach; ?>
        </div>
        <a class="ph-band d-block" href="<?= SITE_URL ?>/diet-plans.php" style="padding:1.5rem">
          <span class="ph-kicker"><i class="fa-solid fa-carrot"></i> Free</span>
          <h2 style="font-size:1.35rem">Diet plans for chronic conditions</h2>
          <p style="font-size:.9rem">Diabetes, blood pressure, HIV, TB, kidneys and more — with a sample day of South African meals.</p>
        </a>
      </aside>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
