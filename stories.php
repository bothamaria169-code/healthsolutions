<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

// Patient stories: testimonials with a longer story or a video (Admin → Testimonials & stories).
$hubs    = condition_hubs();
$stories = db()->query("SELECT * FROM testimonials WHERE status = 'published' AND (story IS NOT NULL OR video_url IS NOT NULL) ORDER BY sort_order, id DESC")->fetchAll();
$quotes  = db()->query("SELECT * FROM testimonials WHERE status = 'published' AND story IS NULL AND video_url IS NULL ORDER BY sort_order, id DESC LIMIT 12")->fetchAll();

/** YouTube links become an embedded player; other video links open on their site. */
$youtube = function (string $url): ?string {
    return preg_match('~(?:youtu\.be/|youtube\.com/(?:watch\?v=|shorts/|embed/))([\w-]{11})~', $url, $m) ? 'https://www.youtube-nocookie.com/embed/' . $m[1] : null;
};

$page_title       = 'Patient stories — real people, real routines | ' . SITE_NAME;
$meta_description = 'Stories from Phola customers living with diabetes, high blood pressure, HIV and other chronic conditions — how they keep their routine and stay well.';
include __DIR__ . '/includes/header.php';
?>

<header class="page-head">
  <div class="container">
    <nav class="crumbs"><a href="<?= SITE_URL ?>/index.php">Home</a><i class="fa-solid fa-chevron-right"></i><span>Patient stories</span></nav>
    <h1>Patient stories</h1>
    <p>Real customers, in their own words, on living well with a long-term condition. Shared with their permission.</p>
  </div>
</header>

<div class="section section--tight">
  <div class="container">
    <?php if (!$stories && !$quotes): ?>
      <div class="empty card-fc"><div class="empty__icon"><i class="fa-solid fa-comment-dots"></i></div><h2>Stories coming soon</h2><p>Would you share yours? WhatsApp us.</p></div>
    <?php endif; ?>

    <?php foreach ($stories as $s): $embed = $s['video_url'] ? $youtube((string) $s['video_url']) : null; ?>
    <article class="story">
      <?php if ($embed): ?>
        <div class="story__media"><iframe src="<?= e($embed) ?>" title="<?= e($s['name']) ?>'s story" loading="lazy" allow="accelerometer; encrypted-media; picture-in-picture" allowfullscreen></iframe></div>
      <?php elseif ($s['avatar']): ?>
        <div class="story__media story__media--photo"><img src="<?= e(product_image($s['avatar'])) ?>" alt="<?= e($s['name']) ?>" loading="lazy"></div>
      <?php endif; ?>
      <div class="story__body">
        <?php if ($s['condition_tag'] && isset($hubs[$s['condition_tag']])): ?><a class="pdp-hero__chip" href="<?= SITE_URL ?>/condition.php?c=<?= e($s['condition_tag']) ?>"><i class="fa-solid <?= e($hubs[$s['condition_tag']]['icon']) ?>"></i> <?= e($hubs[$s['condition_tag']]['name']) ?></a><?php endif; ?>
        <h2>&ldquo;<?= e($s['quote']) ?>&rdquo;</h2>
        <?php if ($s['story']): ?><div class="prose"><?= nl2br(e((string) $s['story'])) ?></div><?php endif; ?>
        <?php if ($s['video_url'] && !$embed): ?><a class="ph-link" href="<?= e($s['video_url']) ?>" target="_blank" rel="noopener">Watch the video <i class="fa-solid fa-play"></i></a><?php endif; ?>
        <p class="story__who"><b><?= e($s['name']) ?></b> · <?= e(trim(($s['role'] ?? '') . ' · ' . ($s['location'] ?? ''), ' ·')) ?> <?= stars_html((float) $s['rating']) ?></p>
      </div>
    </article>
    <?php endforeach; ?>

    <?php if ($quotes): ?>
    <div class="ph-head mt-4"><div><span class="eyebrow">In a few words</span><h2>What customers say</h2></div></div>
    <div class="row g-3">
      <?php foreach ($quotes as $t): ?>
      <div class="col-lg-3 col-md-6"><div class="quote-card"><?= stars_html((float) $t['rating']) ?><p class="mt-2">&ldquo;<?= e($t['quote']) ?>&rdquo;</p>
        <div class="quote-card__who"><span class="quote-card__av"><?= e(mb_substr($t['name'], 0, 1)) ?></span><span><b><?= e($t['name']) ?></b><span><?= e((string) $t['location']) ?></span></span></div></div></div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="ph-band mt-5">
      <div><span class="ph-kicker"><i class="fa-solid fa-heart"></i> Your story matters</span><h2>Would you share yours?</h2>
        <p>Your story could help someone who has just been diagnosed. We only publish your first name and town, and only with your permission.</p></div>
      <div class="ph-band__cta"><a class="btn-fc btn-fc--lg ph-btn-lime" href="<?= e(wa_link("Hi Phola, I'd like to share my story.")) ?>" target="_blank" rel="noopener"><i class="fa-brands fa-whatsapp"></i> Share my story</a></div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
