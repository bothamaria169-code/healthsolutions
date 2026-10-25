<?php
declare(strict_types=1);
$dash_role   = 'admin';
$dash_active = 'news';
$page_title  = 'Health news — Phola admin';
require_once __DIR__ . '/../../includes/dash-head.php';

$flash = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf((string) ($_POST['csrf_token'] ?? ''))) {
    $action = (string) ($_POST['action'] ?? '');
    $id     = (int) ($_POST['id'] ?? 0);
    if ($action === 'import') {
        $r = import_health_news();
        $flash = ['ok', $r['added'] . ' new headline(s) imported.' . ($r['errors'] ? ' Problems: ' . implode('; ', $r['errors']) : '')];
    } elseif ($action === 'add') {
        $url = trim((string) ($_POST['url'] ?? ''));
        $name = sanitize((string) ($_POST['name'] ?? ''));
        if ($name !== '' && filter_var($url, FILTER_VALIDATE_URL) && preg_match('~^https?://~i', $url)) {
            db_insert('rss_sources', ['name' => $name, 'url' => $url]);
            $flash = ['ok', 'Feed added. Press "Import now" to fetch it.'];
        } else {
            $flash = ['danger', 'Give the feed a name and a full https:// address.'];
        }
    } elseif ($action === 'toggle' && $id) {
        db()->prepare('UPDATE rss_sources SET is_active = 1 - is_active WHERE id = :id')->execute([':id' => $id]);
    } elseif ($action === 'delete' && $id) {
        db()->prepare('DELETE FROM health_news WHERE source_id = :id')->execute([':id' => $id]);
        db()->prepare('DELETE FROM rss_sources WHERE id = :id')->execute([':id' => $id]);
        $flash = ['ok', 'Feed and its headlines removed.'];
    } elseif ($action === 'hide' && $id) {
        db()->prepare('UPDATE health_news SET is_hidden = 1 - is_hidden WHERE id = :id')->execute([':id' => $id]);
    }
}

$sources = db()->query('SELECT s.*, (SELECT COUNT(*) FROM health_news n WHERE n.source_id = s.id) n FROM rss_sources s ORDER BY s.name')->fetchAll();
$latest  = db()->query('SELECT n.*, s.name source_name FROM health_news n LEFT JOIN rss_sources s ON s.id = n.source_id ORDER BY n.published_at DESC LIMIT 40')->fetchAll();
$lastRun = get_setting('rss_last_run', '');
?>

<div class="dash-head">
  <div><h1>Health news</h1><p>Headlines from these feeds appear on the <a class="text-leaf" href="<?= SITE_URL ?>/health-news.php" target="_blank">Health news</a> page. The cron job refreshes them every 3 hours<?= $lastRun ? ' — last run ' . e(date('j M, H:i', strtotime($lastRun))) : '' ?>.</p></div>
  <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="import">
    <button class="btn-fc" type="submit"><i class="fa-solid fa-rotate"></i> Import now</button></form>
</div>

<?php if ($flash): ?>
  <div class="alert-fc alert-fc--<?= $flash[0] ?>"><i class="fa-solid fa-circle-info"></i><span><?= e($flash[1]) ?></span></div>
<?php endif; ?>

<div class="row g-3">
  <div class="col-lg-7">
    <div class="card-fc card-fc--pad">
      <h2 style="font-size:1.05rem">Latest headlines</h2>
      <table class="table-fc">
        <tbody>
        <?php foreach ($latest as $n): ?>
          <tr style="<?= $n['is_hidden'] ? 'opacity:.45' : '' ?>">
            <td><a href="<?= e($n['url']) ?>" target="_blank" rel="noopener"><?= e($n['title']) ?></a>
              <div class="text-muted-3" style="font-size:.75rem"><?= e((string) $n['source_name']) ?> · <?= $n['published_at'] ? date('j M H:i', strtotime($n['published_at'])) : '' ?></div></td>
            <td class="text-end"><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="hide"><input type="hidden" name="id" value="<?= (int) $n['id'] ?>">
              <button class="btn-fc btn-fc--sm btn-fc--light" type="submit" title="<?= $n['is_hidden'] ? 'Show' : 'Hide' ?> on the site"><i class="fa-solid fa-eye<?= $n['is_hidden'] ? '' : '-slash' ?>"></i></button></form></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$latest): ?><tr><td class="text-muted-2">Nothing yet — press Import now.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="card-fc card-fc--pad mb-3">
      <h2 style="font-size:1.05rem">Feeds</h2>
      <?php foreach ($sources as $s): ?>
        <div class="d-flex align-items-center gap-2 py-2" style="border-top:1px solid var(--line)">
          <div style="flex:1;min-width:0"><b style="font-size:.9rem"><?= e($s['name']) ?></b>
            <div class="text-muted-3" style="font-size:.72rem;word-break:break-all"><?= e($s['url']) ?></div>
            <div style="font-size:.72rem" class="<?= $s['last_error'] ? 'text-danger' : 'text-muted-3' ?>"><?= (int) $s['n'] ?> headlines<?= $s['last_error'] ? ' · ' . e($s['last_error']) : '' ?></div></div>
          <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
            <button class="pill pill--<?= $s['is_active'] ? 'ok' : 'muted' ?>" style="border:0" type="submit"><?= $s['is_active'] ? 'On' : 'Off' ?></button></form>
          <form method="post" onsubmit="return confirm('Remove this feed and its headlines?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
            <button class="btn-fc btn-fc--sm btn-fc--light" style="color:var(--coral)" type="submit" aria-label="Remove"><i class="fa-solid fa-trash-can"></i></button></form>
        </div>
      <?php endforeach; ?>
    </div>
    <form method="post" class="card-fc card-fc--pad">
      <?= csrf_field() ?><input type="hidden" name="action" value="add">
      <h2 style="font-size:1.05rem">Add a feed</h2>
      <div class="field"><label for="fname">Name</label><input class="input-fc" id="fname" name="name" placeholder="e.g. News24 Health" required></div>
      <div class="field"><label for="furl">RSS address</label><input class="input-fc" id="furl" name="url" type="url" placeholder="https://…/feed/" required>
        <div class="field__hint">Most news sites have one — look for "RSS" in their footer.</div></div>
      <button class="btn-fc btn-fc--block" type="submit">Add feed</button>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/dash-foot.php'; ?>
