<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$reviewImages = glob(__DIR__ . '/assets/img/testimonials/*.{jpg,jpeg,png,webp}', GLOB_BRACE) ?: [];
sort($reviewImages, SORT_NATURAL | SORT_FLAG_CASE);
$screenshots = db()->query("SELECT * FROM testimonials WHERE status='published' AND screenshot IS NOT NULL AND screenshot <> '' ORDER BY sort_order, id")->fetchAll();
$reviewVideos = db()->query("SELECT * FROM testimonials WHERE status='published' AND video_url LIKE 'uploads/testimonials/%.mp4' ORDER BY sort_order, id")->fetchAll();

$page_title       = 'Customer testimonials | ' . SITE_NAME;
$meta_description = 'Read customer reviews and testimonials shared with Phola Health Solutions.';
include __DIR__ . '/includes/header.php';
?>

<header class="page-head">
  <div class="container">
    <nav class="crumbs"><a href="<?= SITE_URL ?>/index.php">Home</a><i class="fa-solid fa-chevron-right"></i><span>Testimonials</span></nav>
    <h1>Reviews from our customers</h1>
    <p>Customer testimonials shared with Phola Health Solutions.</p>
  </div>
</header>

<section class="section section--tight">
  <div class="container">
    <?php if (!$reviewImages && !$screenshots && !$reviewVideos): ?>
      <div class="empty card-fc"><div class="empty__icon"><i class="fa-solid fa-star"></i></div><h2>Testimonials coming soon</h2><p>New review images will appear here when added to our testimonials collection.</p></div>
    <?php else: ?>
      <?php if ($reviewImages || $screenshots): ?>
      <div class="review-media-grid">
        <?php foreach ($reviewImages as $i => $image): ?>
        <a class="review-media-card" href="<?= e(SITE_URL . '/assets/img/testimonials/' . rawurlencode(basename($image))) ?>" target="_blank" rel="noopener">
          <img src="<?= e(SITE_URL . '/assets/img/testimonials/' . rawurlencode(basename($image))) ?>" alt="Customer testimonial <?= $i + 1 ?>" loading="lazy">
        </a>
        <?php endforeach; ?>
        <?php foreach ($screenshots as $s): ?>
        <a class="review-media-card" href="<?= e(product_image($s['screenshot'])) ?>" target="_blank" rel="noopener">
          <img src="<?= e(product_image($s['screenshot'])) ?>" alt="Review shared by <?= e($s['name']) ?>" loading="lazy">
        </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
      <?php if ($reviewVideos): ?>
      <div class="row g-3 mt-2">
        <?php foreach ($reviewVideos as $video): ?>
        <div class="col-lg-6">
          <article class="card-fc card-fc--pad">
            <video class="news-video-player" controls preload="metadata" playsinline>
              <source src="<?= e(product_image($video['video_url'])) ?>" type="video/mp4">
              Your browser does not support video playback.
            </video>
            <h2 class="news-video-title"><?= e($video['name']) ?>’s testimonial</h2>
            <?php if ($video['quote'] !== ''): ?><p class="mt-2">&ldquo;<?= e($video['quote']) ?>&rdquo;</p><?php endif; ?>
          </article>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
