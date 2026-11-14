<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$newsVideos = glob(__DIR__ . '/assets/video/news/*.mp4') ?: [];
sort($newsVideos, SORT_NATURAL | SORT_FLAG_CASE);

$page_title       = 'News & updates | ' . SITE_NAME;
$meta_description = 'Watch news and updates from Phola Health Solutions.';
include __DIR__ . '/includes/header.php';
?>

<header class="page-head">
  <div class="container">
    <nav class="crumbs"><a href="<?= SITE_URL ?>/index.php">Home</a><i class="fa-solid fa-chevron-right"></i><span>News &amp; updates</span></nav>
    <h1>News &amp; updates</h1>
    <p>Watch the latest videos from Phola Health Solutions.</p>
  </div>
</header>

<section class="section section--tight">
  <div class="container">
    <?php if (!$newsVideos): ?>
      <div class="empty card-fc"><div class="empty__icon"><i class="fa-solid fa-newspaper"></i></div><h2>News videos coming soon</h2><p>New videos will appear here when added to our news video collection.</p></div>
    <?php else: ?>
      <div class="news-video-grid">
        <?php foreach ($newsVideos as $video):
          $videoName = pathinfo($video, PATHINFO_FILENAME);
          $videoUrl = SITE_URL . '/assets/video/news/' . rawurlencode(basename($video));
        ?>
        <article class="card-fc card-fc--pad">
          <video class="news-video-player" controls preload="metadata" playsinline>
            <source src="<?= e($videoUrl) ?>" type="video/mp4">
            Your browser does not support video playback.
          </video>
          <h2 class="news-video-title"><?= e(ucwords(str_replace(['-', '_'], ' ', $videoName))) ?></h2>
        </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
