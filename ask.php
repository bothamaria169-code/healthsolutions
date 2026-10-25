<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

// Ask a pharmacist: visitors send a question, staff answer it in the admin, and
// published answers appear here (and on the matching condition page) without
// the asker's name.
$hubs   = condition_hubs();
$topics = ['general' => 'General health'] + array_map(fn($h) => $h['name'], $hubs);
$viewer = current_user();

$flash = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf((string) ($_POST['csrf_token'] ?? ''))) {
    $q = trim(strip_tags((string) ($_POST['question'] ?? '')));
    $email = safe_email((string) ($_POST['email'] ?? '')) ? trim((string) $_POST['email']) : '';
    if (!empty($_POST['website'])) {
        $flash = ['success', 'Thank you!']; // bots fill the hidden field
    } elseif (mb_strlen($q) < 15) {
        $flash = ['danger', 'Please write your question in a sentence or two.'];
    } elseif (!rate_limit('ask', 5, 3600)) {
        $flash = ['danger', 'You have sent a few questions already — please try again in an hour.'];
    } else {
        $topic = array_key_exists($_POST['topic'] ?? '', $topics) ? (string) $_POST['topic'] : 'general';
        db_insert('questions', [
            'name' => mb_substr(trim(strip_tags((string) ($_POST['name'] ?? ''))), 0, 120) ?: null,
            'email' => $email ?: null, 'condition_tag' => $topic, 'question' => mb_substr($q, 0, 2000),
        ]);
        Mailer::send(ADMIN_EMAIL, 'New "Ask a pharmacist" question', email_wrap('<p>' . nl2br(e($q)) . '</p><p><a href="' . SITE_URL . '/dashboards/admin/questions.php">Answer it in the admin</a></p>'));
        $flash = ['success', 'Thank you — your question is with our team.' . ($email ? ' We will email you when it is answered.' : ' Check back here in a day or two.')];
    }
}

// One question
if (!empty($_GET['q'])) {
    $st = db()->prepare("SELECT * FROM questions WHERE slug = :s AND status = 'published'");
    $st->execute([':s' => (string) $_GET['q']]);
    $one = $st->fetch();
    if ($one) {
        db()->prepare('UPDATE questions SET views = views + 1 WHERE id = :id')->execute([':id' => $one['id']]);
        $page_title = excerpt($one['question'], 70) . ' — Ask a pharmacist | ' . SITE_NAME;
        $meta_description = excerpt(strip_tags((string) $one['answer']), 155);
        $json_ld = ['@type' => 'QAPage', 'mainEntity' => ['@type' => 'Question', 'name' => $one['question'], 'answerCount' => 1,
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => strip_tags((string) $one['answer'])]]];
        include __DIR__ . '/includes/header.php'; ?>
<header class="page-head"><div class="container container--narrow">
  <nav class="crumbs"><a href="<?= SITE_URL ?>/index.php">Home</a><i class="fa-solid fa-chevron-right"></i><a href="<?= SITE_URL ?>/ask.php">Ask a pharmacist</a><i class="fa-solid fa-chevron-right"></i><span>Question</span></nav>
  <span class="eyebrow"><?= e($topics[$one['condition_tag']] ?? 'General health') ?></span>
  <h1 style="font-size:clamp(1.6rem,3.4vw,2.4rem)"><?= e($one['question']) ?></h1>
</div></header>
<div class="section section--tight"><div class="container container--narrow">
  <div class="qa-answer"><span class="qa-answer__who"><i class="fa-solid fa-user-doctor"></i> Phola team answer · <?= date('j M Y', strtotime((string) $one['answered_at'])) ?></span>
    <div class="prose"><?= nl2br(e((string) $one['answer'])) ?></div></div>
  <p class="field__hint mt-3">General information only, not a diagnosis. For your own treatment, speak to your doctor or clinic.</p>
  <div class="d-flex gap-2 flex-wrap mt-3">
    <a class="btn-fc" href="<?= SITE_URL ?>/ask.php#ask"><i class="fa-solid fa-circle-question"></i> Ask your own question</a>
    <?php if (isset($hubs[$one['condition_tag']])): ?><a class="btn-fc btn-fc--ghost" href="<?= SITE_URL ?>/condition.php?c=<?= e($one['condition_tag']) ?>">More on <?= e($hubs[$one['condition_tag']]['name']) ?></a><?php endif; ?>
  </div>
</div></div>
<?php include __DIR__ . '/includes/footer.php'; exit;
    }
}

$topic = array_key_exists($_GET['condition'] ?? '', $topics) ? (string) $_GET['condition'] : '';
$sql = "SELECT slug, question, answer, condition_tag, answered_at FROM questions WHERE status = 'published'" . ($topic ? ' AND condition_tag = :t' : '') . ' ORDER BY answered_at DESC LIMIT 60';
$st = db()->prepare($sql);
$st->execute($topic ? [':t' => $topic] : []);
$list = $st->fetchAll();

$page_title       = 'Ask a pharmacist — free health questions answered | ' . SITE_NAME;
$meta_description = 'Ask our team a question about supplements, chronic conditions, diet or your medication routine. Free, private, and answered in plain language.';
include __DIR__ . '/includes/header.php';
?>

<header class="page-head">
  <div class="container">
    <nav class="crumbs"><a href="<?= SITE_URL ?>/index.php">Home</a><i class="fa-solid fa-chevron-right"></i><span>Ask a pharmacist</span></nav>
    <h1>Ask a pharmacist</h1>
    <p>Free questions about supplements, chronic conditions, diet and daily routines, answered by our team in plain language. We never publish your name.</p>
  </div>
</header>

<div class="section section--tight">
  <div class="container">
    <?php if ($flash): ?><div class="alert-fc alert-fc--<?= $flash[0] ?>"><i class="fa-solid fa-circle-info"></i><span><?= e($flash[1]) ?></span></div><?php endif; ?>
    <div class="news-layout">
      <div>
        <div class="ph-rail__row mb-3" style="padding-top:0">
          <a class="ph-need<?= $topic ? '' : ' is-on' ?>" href="ask.php"><span><i class="fa-solid fa-list"></i></span><b>All topics</b></a>
          <?php foreach ($topics as $k => $t): ?><a class="ph-need<?= $topic === $k ? ' is-on' : '' ?>" href="ask.php?condition=<?= e($k) ?>"><span><i class="fa-solid fa-tag"></i></span><b><?= e($t) ?></b></a><?php endforeach; ?>
        </div>
        <?php if (!$list): ?>
          <div class="empty card-fc"><div class="empty__icon"><i class="fa-solid fa-user-doctor"></i></div><h2>Be the first to ask</h2><p>Answered questions appear here.</p></div>
        <?php endif; ?>
        <?php foreach ($list as $q): ?>
          <a class="qa-item" href="ask.php?q=<?= e($q['slug']) ?>">
            <span class="eyebrow"><?= e($topics[$q['condition_tag']] ?? 'General') ?></span>
            <h3><?= e($q['question']) ?></h3>
            <p><?= e(excerpt((string) $q['answer'], 160)) ?></p>
          </a>
        <?php endforeach; ?>
      </div>

      <aside class="news-side" id="ask">
        <form method="post" class="card-fc card-fc--pad">
          <?= csrf_field() ?>
          <h2 style="font-size:1.25rem"><i class="fa-solid fa-circle-question text-leaf me-2"></i>Ask your question</h2>
          <div class="field"><label for="topic">Topic</label>
            <select class="input-fc" id="topic" name="topic"><?php foreach ($topics as $k => $t): ?><option value="<?= e($k) ?>" <?= ($topic ?: ($_GET['condition'] ?? '')) === $k ? 'selected' : '' ?>><?= e($t) ?></option><?php endforeach; ?></select></div>
          <div class="field"><label for="question">Your question</label>
            <textarea class="textarea-fc" id="question" name="question" rows="5" required minlength="15" maxlength="2000" placeholder="e.g. Can I take magnesium with my blood pressure tablets?"></textarea></div>
          <div class="field"><label for="qname">First name <span class="text-muted-3 fw-normal">(never published)</span></label>
            <input class="input-fc" id="qname" name="name" value="<?= e(explode(' ', (string) ($viewer['name'] ?? ''))[0]) ?>"></div>
          <div class="field"><label for="qemail">Email <span class="text-muted-3 fw-normal">(to hear when it's answered)</span></label>
            <input class="input-fc" id="qemail" name="email" type="email" value="<?= e((string) ($viewer['email'] ?? '')) ?>"></div>
          <input type="text" name="website" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px" aria-hidden="true">
          <button class="btn-fc btn-fc--block" type="submit">Send question</button>
          <p class="field__hint mt-2 mb-0">General information only. In an emergency call 10177 or go to the nearest clinic.</p>
        </form>
      </aside>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
