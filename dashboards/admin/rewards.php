<?php
declare(strict_types=1);
$dash_role   = 'admin';
$dash_active = 'rewards';
$page_title  = 'Rewards — Phola admin';
require_once __DIR__ . '/../../includes/dash-head.php';

$flash = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf((string) ($_POST['csrf_token'] ?? ''))) {
    $uid = (int) ($_POST['user_id'] ?? 0);
    $pts = (int) ($_POST['points'] ?? 0);
    $why = clean_text((string) ($_POST['reason'] ?? '')) ?: 'Adjustment by Phola';
    if ($uid && $pts !== 0) {
        points_add($uid, $pts, $why);
        $flash = ($pts > 0 ? 'Added ' : 'Removed ') . abs($pts) . ' points.';
    }
}

$top = db()->query("SELECT u.id, u.name, u.email, u.phone, COALESCE(SUM(l.points), 0) pts,
        (SELECT COUNT(*) FROM users f WHERE f.referred_by = u.id) friends
    FROM users u LEFT JOIN points_ledger l ON l.user_id = u.id
    WHERE u.role IN ('customer','reseller') GROUP BY u.id ORDER BY pts DESC, friends DESC LIMIT 100")->fetchAll();
$recent = db()->query('SELECT l.*, u.name FROM points_ledger l JOIN users u ON u.id = l.user_id ORDER BY l.id DESC LIMIT 30')->fetchAll();
$totals = db()->query("SELECT COALESCE(SUM(CASE WHEN points > 0 THEN points END), 0) earned, COALESCE(-SUM(CASE WHEN points < 0 THEN points END), 0) spent,
    (SELECT COUNT(*) FROM users WHERE referred_by IS NOT NULL) referred FROM points_ledger")->fetch();
?>

<div class="dash-head">
  <div><h1>Rewards &amp; referrals</h1><p>Points are added automatically when you mark an order <b>Sold</b>. The rates are in <a class="text-leaf" href="settings.php">Settings → Rewards points</a>.</p></div>
</div>
<?php if ($flash): ?><div class="alert-fc alert-fc--ok"><i class="fa-solid fa-circle-check"></i><span><?= e($flash) ?></span></div><?php endif; ?>

<div class="row g-3 mb-3">
  <div class="col-md-4"><div class="reward-tile"><span>Points given</span><b><?= number_format((int) $totals['earned']) ?></b><small><?= format_price(points_value((int) $totals['earned'])) ?></small></div></div>
  <div class="col-md-4"><div class="reward-tile"><span>Points spent</span><b><?= number_format((int) $totals['spent']) ?></b><small><?= format_price(points_value((int) $totals['spent'])) ?> in discounts</small></div></div>
  <div class="col-md-4"><div class="reward-tile reward-tile--lime"><span>Customers referred</span><b><?= (int) $totals['referred'] ?></b><small>signed up with a friend's link</small></div></div>
</div>

<div class="row g-3">
  <div class="col-lg-7">
    <div class="card-fc card-fc--pad">
      <h2 style="font-size:1.05rem">Customers</h2>
      <table class="table-fc"><thead><tr><th>Customer</th><th class="text-end">Points</th><th class="text-end">Friends</th><th></th></tr></thead><tbody>
      <?php foreach ($top as $u): ?>
        <tr><td><?= e($u['name']) ?><div class="text-muted-3" style="font-size:.75rem"><?= e((string) ($u['email'] ?: Health::displayPhone((string) $u['phone']))) ?></div></td>
            <td class="text-end"><b><?= number_format((int) $u['pts']) ?></b></td><td class="text-end"><?= (int) $u['friends'] ?></td>
            <td class="text-end"><form method="post" class="d-flex gap-1 justify-content-end"><?= csrf_field() ?><input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
              <input class="input-fc" type="number" name="points" placeholder="±" style="width:80px;padding:.3rem .5rem"><button class="btn-fc btn-fc--sm btn-fc--light" type="submit" title="Adjust points"><i class="fa-solid fa-plus-minus"></i></button></form></td></tr>
      <?php endforeach; ?>
      </tbody></table>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="card-fc card-fc--pad">
      <h2 style="font-size:1.05rem">Latest activity</h2>
      <?php foreach ($recent as $l): ?>
        <div class="d-flex justify-content-between py-2" style="border-top:1px solid var(--line);font-size:.85rem">
          <span><b><?= e($l['name']) ?></b> · <?= e($l['reason']) ?><div class="text-muted-3" style="font-size:.72rem"><?= date('j M H:i', strtotime($l['created_at'])) ?></div></span>
          <b style="color:<?= $l['points'] >= 0 ? 'var(--leaf)' : 'var(--coral)' ?>"><?= $l['points'] >= 0 ? '+' : '' ?><?= (int) $l['points'] ?></b>
        </div>
      <?php endforeach; ?>
      <?php if (!$recent): ?><p class="text-muted-2 mb-0">No points yet.</p><?php endif; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/dash-foot.php'; ?>
