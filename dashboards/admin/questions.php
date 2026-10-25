<?php
declare(strict_types=1);
$dash_role   = 'admin';
$dash_active = 'questions';
$page_title  = 'Ask a pharmacist — Phola admin';
require_once __DIR__ . '/../../includes/dash-head.php';

$topics = ['general' => 'General health'] + array_map(fn($h) => $h['name'], condition_hubs());
$flash  = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf((string) ($_POST['csrf_token'] ?? ''))) {
    $id = (int) ($_POST['id'] ?? 0);
    $action = (string) ($_POST['action'] ?? '');
    $st = db()->prepare('SELECT * FROM questions WHERE id = :id');
    $st->execute([':id' => $id]);
    $q = $st->fetch();

    if ($q && $action === 'answer') {
        $question = mb_substr(trim(strip_tags((string) ($_POST['question'] ?? $q['question']))), 0, 2000);
        $answer   = trim(strip_tags((string) ($_POST['answer'] ?? '')));
        $publish  = !empty($_POST['publish']) && $answer !== '';
        $data = [
            'question' => $question, 'answer' => $answer ?: null,
            'condition_tag' => array_key_exists($_POST['topic'] ?? '', $topics) ? $_POST['topic'] : $q['condition_tag'],
            'status' => $publish ? 'published' : ($q['status'] === 'published' ? 'hidden' : $q['status']),
        ];
        if ($publish && !$q['slug']) {
            $data['slug'] = mb_substr(slugify(excerpt($question, 80)), 0, 180) . '-' . $id;
        }
        if ($answer !== '' && !$q['answered_at']) {
            $data['answered_at'] = date('Y-m-d H:i:s');
        }
        db_update('questions', $id, $data);
        if ($publish && $q['status'] !== 'published' && $q['email']) {
            $link = SITE_URL . '/ask.php?q=' . rawurlencode($data['slug'] ?? (string) $q['slug']);
            Mailer::send((string) $q['email'], 'Your question has been answered — Phola',
                email_wrap('<p>Hi ' . e((string) ($q['name'] ?: 'there')) . ', our team has answered your question:</p><p><i>' . e($question) . '</i></p>'
                    . '<p><a href="' . e($link) . '" style="display:inline-block;background:#2E8B3E;color:#fff;padding:11px 22px;border-radius:999px;font-weight:700;">Read the answer</a></p>'));
        }
        $flash = $publish ? 'Answer published.' : 'Saved.';
    } elseif ($q && $action === 'delete') {
        db()->prepare('DELETE FROM questions WHERE id = :id')->execute([':id' => $id]);
        $flash = 'Question deleted.';
    }
}

$filter = in_array($_GET['status'] ?? '', ['pending', 'published', 'hidden'], true) ? $_GET['status'] : 'pending';
$st = db()->prepare('SELECT * FROM questions WHERE status = :s ORDER BY created_at DESC LIMIT 100');
$st->execute([':s' => $filter]);
$rows = $st->fetchAll();
$counts = [];
foreach (db()->query('SELECT status, COUNT(*) n FROM questions GROUP BY status') as $r) { $counts[$r['status']] = (int) $r['n']; }
?>

<div class="dash-head">
  <div><h1>Ask a pharmacist</h1><p>Questions from the public. Answer, tick <b>Publish</b>, and it appears on the Ask page and the matching condition page — without the asker's name. The asker is emailed the link.</p></div>
  <a class="btn-fc btn-fc--ghost btn-fc--sm" href="<?= SITE_URL ?>/ask.php" target="_blank"><i class="fa-solid fa-eye"></i> View page</a>
</div>
<?php if ($flash): ?><div class="alert-fc alert-fc--ok"><i class="fa-solid fa-circle-check"></i><span><?= e($flash) ?></span></div><?php endif; ?>

<div class="d-flex gap-2 mb-3">
  <?php foreach (['pending' => 'Waiting', 'published' => 'Published', 'hidden' => 'Hidden'] as $k => $l): ?>
    <a class="chip <?= $filter === $k ? 'is-on' : '' ?>" href="?status=<?= $k ?>"><?= $l ?> <?= $counts[$k] ?? 0 ?></a>
  <?php endforeach; ?>
</div>

<?php if (!$rows): ?><div class="card-fc card-fc--pad text-muted-2">Nothing here.</div><?php endif; ?>
<?php foreach ($rows as $q): ?>
<form method="post" class="card-fc card-fc--pad mb-3">
  <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $q['id'] ?>">
  <div class="d-flex justify-content-between flex-wrap gap-2 mb-2" style="font-size:.8rem">
    <span class="text-muted-3"><?= e((string) ($q['name'] ?: 'Anonymous')) ?><?= $q['email'] ? ' · ' . e($q['email']) : '' ?> · <?= date('j M Y H:i', strtotime($q['created_at'])) ?><?= $q['status'] === 'published' ? ' · ' . (int) $q['views'] . ' views' : '' ?></span>
    <select class="select-fc" name="topic" style="width:auto;padding:.2rem .6rem"><?php foreach ($topics as $k => $t): ?><option value="<?= e($k) ?>" <?= $q['condition_tag'] === $k ? 'selected' : '' ?>><?= e($t) ?></option><?php endforeach; ?></select>
  </div>
  <div class="field"><label>Question <span class="text-muted-3 fw-normal">(tidy it up before publishing — remove names or personal details)</span></label>
    <textarea class="textarea-fc" name="question" rows="2"><?= e($q['question']) ?></textarea></div>
  <div class="field"><label>Answer</label>
    <textarea class="textarea-fc" name="answer" rows="5" placeholder="Plain language. Never tell anyone to stop prescribed medication; suggest they speak to their clinic."><?= e((string) $q['answer']) ?></textarea></div>
  <div class="d-flex gap-2 align-items-center flex-wrap">
    <label style="font-size:.88rem"><input type="checkbox" name="publish" value="1" <?= $q['status'] === 'published' ? 'checked' : '' ?>> Publish</label>
    <button class="btn-fc btn-fc--sm" name="action" value="answer" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save</button>
    <button class="btn-fc btn-fc--sm btn-fc--light" style="color:var(--coral)" name="action" value="delete" type="submit" onclick="return confirm('Delete this question?')"><i class="fa-solid fa-trash-can"></i></button>
    <?php if ($q['slug'] && $q['status'] === 'published'): ?><a class="ph-link ms-auto" href="<?= SITE_URL ?>/ask.php?q=<?= e($q['slug']) ?>" target="_blank">View</a><?php endif; ?>
  </div>
</form>
<?php endforeach; ?>

<?php require_once __DIR__ . '/../../includes/dash-foot.php'; ?>
