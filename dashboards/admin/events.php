<?php
declare(strict_types=1);
$dash_role   = 'admin';
$dash_active = 'events';
$page_title  = 'Health talks — Phola admin';
require_once __DIR__ . '/../../includes/dash-head.php';

$flash = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf((string) ($_POST['csrf_token'] ?? ''))) {
    $id = (int) ($_POST['id'] ?? 0);
    if (($_POST['action'] ?? '') === 'save') {
        $when = strtotime((string) ($_POST['starts_at'] ?? ''));
        $link = trim((string) ($_POST['join_link'] ?? ''));
        $data = [
            'title'       => clean_text((string) ($_POST['title'] ?? '')),
            'starts_at'   => $when ? date('Y-m-d H:i:s', $when) : null,
            'channel'     => clean_text((string) ($_POST['channel'] ?? 'WhatsApp group')) ?: 'WhatsApp group',
            'join_link'   => preg_match('~^https://~i', $link) ? $link : null,
            'description' => trim(strip_tags((string) ($_POST['description'] ?? ''))) ?: null,
            'status'      => ($_POST['status'] ?? '') === 'draft' ? 'draft' : 'published',
        ];
        if ($data['title'] === '' || !$data['starts_at']) {
            $flash = ['danger', 'Add a title and a date and time.'];
        } elseif ($id) {
            db_update('events', $id, $data); $flash = ['ok', 'Talk updated.'];
        } else {
            db_insert('events', $data); $flash = ['ok', 'Talk added — it is on the Health talks page now.'];
        }
    } elseif (($_POST['action'] ?? '') === 'delete' && $id) {
        db()->prepare('DELETE FROM event_rsvps WHERE event_id = :id')->execute([':id' => $id]);
        db()->prepare('DELETE FROM events WHERE id = :id')->execute([':id' => $id]);
        $flash = ['ok', 'Talk and its bookings deleted.'];
    }
}

$rows = db()->query('SELECT e.*, (SELECT COUNT(*) FROM event_rsvps r WHERE r.event_id = e.id) booked FROM events e ORDER BY starts_at DESC LIMIT 50')->fetchAll();
$edit = null; $rsvps = [];
if (!empty($_GET['id'])) {
    $s = db()->prepare('SELECT * FROM events WHERE id = :id'); $s->execute([':id' => (int) $_GET['id']]); $edit = $s->fetch() ?: null;
    if ($edit) { $s = db()->prepare('SELECT * FROM event_rsvps WHERE event_id = :id ORDER BY id'); $s->execute([':id' => $edit['id']]); $rsvps = $s->fetchAll(); }
}
?>

<div class="dash-head">
  <div><h1>Health talks</h1><p>Free WhatsApp-group talks, webinars or in-person days. People book on the <a class="text-leaf" href="<?= SITE_URL ?>/events.php" target="_blank">Health talks</a> page; their details appear here.</p></div>
</div>
<?php if ($flash): ?><div class="alert-fc alert-fc--<?= $flash[0] ?>"><i class="fa-solid fa-circle-info"></i><span><?= e($flash[1]) ?></span></div><?php endif; ?>

<div class="row g-3">
  <div class="col-lg-7">
    <div class="card-fc card-fc--pad mb-3">
      <table class="table-fc"><thead><tr><th>Talk</th><th>When</th><th class="text-end">Booked</th></tr></thead><tbody>
        <?php foreach ($rows as $r): ?>
          <tr><td><a class="text-leaf fw-bold" href="?id=<?= (int) $r['id'] ?>"><?= e($r['title']) ?></a> <?= $r['status'] === 'draft' ? '<span class="pill pill--muted">Draft</span>' : '' ?><div class="text-muted-3" style="font-size:.75rem"><?= e($r['channel']) ?></div></td>
              <td><?= date('j M Y H:i', strtotime($r['starts_at'])) ?></td><td class="text-end"><?= (int) $r['booked'] ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="3" class="text-muted-2">No talks yet. Ideas: "Diabetes Q&amp;A night", "Eating well on a budget", "Living with HIV: your questions".</td></tr><?php endif; ?>
      </tbody></table>
    </div>
    <?php if ($edit): ?>
    <div class="card-fc card-fc--pad">
      <h2 style="font-size:1.05rem">Bookings for <?= e($edit['title']) ?> (<?= count($rsvps) ?>)</h2>
      <?php if ($rsvps): $all = implode(', ', array_filter(array_column($rsvps, 'phone'))); ?>
        <table class="table-fc"><tbody>
        <?php foreach ($rsvps as $r): ?>
          <tr><td><?= e($r['name']) ?></td><td><?= e(Health::displayPhone((string) $r['phone'])) ?></td><td><?= e((string) $r['email']) ?></td>
              <td class="text-end"><?php if ($r['phone']): ?><a class="btn-fc btn-fc--whatsapp btn-fc--sm" target="_blank" rel="noopener" href="<?= e(wa_link('Hi ' . explode(' ', $r['name'])[0] . ', a reminder about the Phola health talk "' . $edit['title'] . '" on ' . date('l j F \a\t H:i', strtotime($edit['starts_at'])) . '.' . ($edit['join_link'] ? ' Join here: ' . $edit['join_link'] : ''), (string) $r['phone'])) ?>"><i class="fa-brands fa-whatsapp"></i></a><?php endif; ?></td></tr>
        <?php endforeach; ?>
        </tbody></table>
        <?php if ($all): ?><p class="field__hint">All numbers (paste into a WhatsApp group or broadcast): <code><?= e($all) ?></code></p><?php endif; ?>
      <?php else: ?><p class="text-muted-2 mb-0">No bookings yet.</p><?php endif; ?>
    </div>
    <?php endif; ?>
  </div>

  <div class="col-lg-5">
    <form method="post" class="card-fc card-fc--pad">
      <?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>">
      <h2 style="font-size:1.05rem"><?= $edit ? 'Edit talk' : 'Add a talk' ?></h2>
      <div class="field"><label>Title</label><input class="input-fc" name="title" value="<?= e((string) ($edit['title'] ?? '')) ?>" placeholder="Diabetes Q&A night" required></div>
      <div class="row g-2">
        <div class="col-7 field"><label>Date and time</label><input class="input-fc" type="datetime-local" name="starts_at" value="<?= $edit ? date('Y-m-d\TH:i', strtotime($edit['starts_at'])) : '' ?>" required></div>
        <div class="col-5 field"><label>Where</label>
          <select class="select-fc" name="channel"><?php foreach (['WhatsApp group', 'Zoom', 'Google Meet', 'Facebook Live', 'In person'] as $c): ?><option <?= ($edit['channel'] ?? '') === $c ? 'selected' : '' ?>><?= $c ?></option><?php endforeach; ?></select></div>
      </div>
      <div class="field"><label>Join link <span class="text-muted-3 fw-normal">(WhatsApp group invite, Zoom link…)</span></label><input class="input-fc" name="join_link" type="url" value="<?= e((string) ($edit['join_link'] ?? '')) ?>" placeholder="https://chat.whatsapp.com/…"></div>
      <div class="field"><label>What it's about</label><textarea class="textarea-fc" name="description" rows="4"><?= e((string) ($edit['description'] ?? '')) ?></textarea></div>
      <div class="field"><label>Status</label><select class="select-fc" name="status"><option value="published">Live</option><option value="draft" <?= ($edit['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option></select></div>
      <button class="btn-fc btn-fc--block" type="submit"><?= $edit ? 'Save changes' : 'Add talk' ?></button>
      <?php if ($edit): ?>
        <a class="btn-fc btn-fc--light btn-fc--block mt-2" href="events.php">New talk</a>
        <button class="btn-fc btn-fc--light btn-fc--block mt-2" style="color:var(--coral)" name="action" value="delete" type="submit" onclick="return confirm('Delete this talk and its bookings?')">Delete</button>
      <?php endif; ?>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/dash-foot.php'; ?>
