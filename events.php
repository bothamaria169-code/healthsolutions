<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

// Health talks: free WhatsApp-group talks, webinars and in-person days (Admin → Health talks).
$viewer = current_user();
$flash  = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf((string) ($_POST['csrf_token'] ?? ''))) {
    $eid   = (int) ($_POST['event_id'] ?? 0);
    $name  = mb_substr(trim(strip_tags((string) ($_POST['name'] ?? ''))), 0, 120);
    $phone = Health::normalizePhone((string) ($_POST['phone'] ?? '')) ?: mb_substr(preg_replace('/[^\d+]/', '', (string) ($_POST['phone'] ?? '')) ?? '', 0, 20);
    $email = safe_email((string) ($_POST['email'] ?? '')) ? trim((string) $_POST['email']) : '';
    $ev = db()->prepare("SELECT * FROM events WHERE id = :id AND status = 'published' AND starts_at > NOW()");
    $ev->execute([':id' => $eid]);
    $event = $ev->fetch();
    if (!$event || $name === '' || ($phone === '' && $email === '')) {
        $flash = ['danger', 'Please add your name and a cellphone number or email.'];
    } elseif (!rate_limit('rsvp', 8, 3600)) {
        $flash = ['danger', 'Too many sign-ups from this device — try again later.'];
    } else {
        db_insert('event_rsvps', ['event_id' => $eid, 'name' => $name, 'phone' => $phone ?: null, 'email' => $email ?: null]);
        $when = date('l j F \a\t H:i', strtotime($event['starts_at']));
        if ($email !== '') {
            Mailer::send($email, "You're booked: " . $event['title'],
                email_wrap('<p>Hi ' . e(explode(' ', $name)[0]) . ', you are booked for <b>' . e($event['title']) . '</b> on <b>' . e($when) . '</b> (' . e($event['channel']) . ').</p>'
                    . ($event['join_link'] ? '<p><a href="' . e($event['join_link']) . '" style="display:inline-block;background:#2E8B3E;color:#fff;padding:11px 22px;border-radius:999px;font-weight:700;">Join link</a></p>' : '<p>We will send you the joining details before it starts.</p>')));
        }
        $flash = ['success', "You're booked for " . $event['title'] . ' on ' . $when . '. ' . ($event['join_link'] ? 'The join link is on this page and in your email.' : 'We will send the joining details.')];
    }
}

$upcoming = db()->query("SELECT e.*, (SELECT COUNT(*) FROM event_rsvps r WHERE r.event_id = e.id) booked FROM events e WHERE status = 'published' AND starts_at > NOW() - INTERVAL 2 HOUR ORDER BY starts_at")->fetchAll();
$past     = db()->query("SELECT title, starts_at, channel FROM events WHERE status = 'published' AND starts_at <= NOW() - INTERVAL 2 HOUR ORDER BY starts_at DESC LIMIT 6")->fetchAll();

$page_title       = 'Free health talks — WhatsApp & online | ' . SITE_NAME;
$meta_description = 'Join free Phola health talks on WhatsApp and online: diabetes Q&A nights, blood pressure, HIV wellness, healthy eating and more.';
include __DIR__ . '/includes/header.php';
?>

<header class="page-head">
  <div class="container">
    <nav class="crumbs"><a href="<?= SITE_URL ?>/index.php">Home</a><i class="fa-solid fa-chevron-right"></i><span>Health talks</span></nav>
    <h1>Free health talks</h1>
    <p>Short, friendly talks on WhatsApp and online — bring your questions. Free for everyone.</p>
  </div>
</header>

<div class="section section--tight">
  <div class="container">
    <?php if ($flash): ?><div class="alert-fc alert-fc--<?= $flash[0] ?>"><i class="fa-solid fa-circle-info"></i><span><?= e($flash[1]) ?></span></div><?php endif; ?>

    <?php if (!$upcoming): ?>
      <div class="empty card-fc"><div class="empty__icon"><i class="fa-solid fa-calendar-days"></i></div><h2>New talks coming soon</h2>
        <p class="mb-3">Want to hear about the next one first?</p>
        <a class="btn-fc btn-fc--whatsapp" href="<?= e(wa_link('Hi Phola, please tell me about your next health talk.')) ?>" target="_blank" rel="noopener"><i class="fa-brands fa-whatsapp"></i> Tell me on WhatsApp</a></div>
    <?php endif; ?>

    <?php foreach ($upcoming as $ev): $t = strtotime($ev['starts_at']); ?>
    <article class="event">
      <div class="event__date"><b><?= date('j', $t) ?></b><span><?= date('M', $t) ?></span><small><?= date('H:i', $t) ?></small></div>
      <div class="event__body">
        <span class="eyebrow"><i class="fa-<?= str_contains(strtolower($ev['channel']), 'whatsapp') ? 'brands fa-whatsapp' : 'solid fa-video' ?> me-1"></i><?= e($ev['channel']) ?> · <?= date('l', $t) ?></span>
        <h2><?= e($ev['title']) ?></h2>
        <?php if ($ev['description']): ?><p><?= nl2br(e($ev['description'])) ?></p><?php endif; ?>
        <p class="text-muted-3" style="font-size:.82rem"><?= (int) $ev['booked'] ?> booked</p>
      </div>
      <form method="post" class="event__form">
        <?= csrf_field() ?><input type="hidden" name="event_id" value="<?= (int) $ev['id'] ?>">
        <input class="input-fc" name="name" placeholder="Your name" value="<?= e((string) ($viewer['name'] ?? '')) ?>" required>
        <input class="input-fc" name="phone" type="tel" placeholder="Cellphone (for WhatsApp)" value="<?= e($viewer ? Health::displayPhone($viewer['phone'] ?? '') : '') ?>">
        <input class="input-fc" name="email" type="email" placeholder="Email (optional)" value="<?= e((string) ($viewer['email'] ?? '')) ?>">
        <button class="btn-fc btn-fc--block" type="submit"><i class="fa-solid fa-calendar-check"></i> Book my free spot</button>
        <?php if ($ev['join_link'] && $flash && $flash[0] === 'success'): ?><a class="ph-link mt-1" href="<?= e($ev['join_link']) ?>" target="_blank" rel="noopener">Join link <i class="fa-solid fa-arrow-up-right-from-square"></i></a><?php endif; ?>
      </form>
    </article>
    <?php endforeach; ?>

    <?php if ($past): ?>
      <h2 class="mt-5" style="font-size:1.3rem">Past talks</h2>
      <ul class="text-muted-2"><?php foreach ($past as $p): ?><li><?= e($p['title']) ?> — <?= date('j M Y', strtotime($p['starts_at'])) ?> (<?= e($p['channel']) ?>)</li><?php endforeach; ?></ul>
    <?php endif; ?>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
