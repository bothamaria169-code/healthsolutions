<?php
declare(strict_types=1);
$dash_role   = 'customer';
$dash_active = 'rewards';
$page_title  = 'Rewards & referrals — Phola';
require_once __DIR__ . '/../../includes/dash-head.php';

$uid       = (int) $user['id'];
$balance   = points_balance($uid);
$available = points_available($uid);
$link      = referral_link($uid);
$code      = referral_code($uid);
$refPts    = (int) get_setting('referral_points', '200');
$perR10    = (int) get_setting('points_per_r10', '1');
$minRedeem = (int) get_setting('min_redeem_points', '100');

$ledger = db()->prepare('SELECT * FROM points_ledger WHERE user_id = :u ORDER BY id DESC LIMIT 50');
$ledger->execute([':u' => $uid]);
$ledger = $ledger->fetchAll();

$friends = db()->prepare('SELECT name, created_at, (SELECT COUNT(*) FROM enquiries e WHERE e.user_id = u.id AND e.sold_processed = 1) orders FROM users u WHERE referred_by = :u ORDER BY id DESC');
$friends->execute([':u' => $uid]);
$friends = $friends->fetchAll();

$shareText = "I get my health products and supplements from Phola Health Solutions — delivered across SA and SADC. Sign up free with my link: " . $link;
?>

<div class="dash-head">
  <div><h1>Rewards &amp; referrals</h1><p>Earn points on every order and when friends you invite place their first order. Spend them on your next basket.</p></div>
</div>

<div class="row g-3 mb-3">
  <div class="col-md-4"><div class="reward-tile"><span>Your points</span><b><?= number_format($balance) ?></b><small>worth <?= format_price(points_value($balance)) ?></small></div></div>
  <div class="col-md-4"><div class="reward-tile reward-tile--lime"><span>Available to spend</span><b><?= number_format($available) ?></b><small><?= $available >= $minRedeem ? 'Tick "Use my points" in your basket' : 'Spend from ' . $minRedeem . ' points' ?></small></div></div>
  <div class="col-md-4"><div class="reward-tile"><span>Friends invited</span><b><?= count($friends) ?></b><small><?= $refPts ?> points for each first order</small></div></div>
</div>

<div class="row g-3">
  <div class="col-lg-7">
    <div class="card-fc card-fc--pad mb-3">
      <h2 style="font-size:1.15rem"><i class="fa-solid fa-user-plus text-leaf me-2"></i>Invite friends &amp; family</h2>
      <p class="text-muted-2" style="font-size:.9rem">Share your link. Your friend gets <b><?= (int) get_setting('signup_points', '50') ?> welcome points</b> when they sign up, and you get <b><?= $refPts ?> points</b> (<?= format_price(points_value($refPts)) ?>) when they place their first order.</p>
      <div class="ref-box">
        <input class="input-fc" id="refLink" value="<?= e($link) ?>" readonly aria-label="Your referral link">
        <button class="btn-fc" type="button" onclick="navigator.clipboard.writeText(document.getElementById('refLink').value);this.innerHTML='<i class=\'fa-solid fa-check\'></i> Copied'"><i class="fa-solid fa-copy"></i> Copy</button>
      </div>
      <div class="share-bar">
        <span>Share</span>
        <a href="https://wa.me/?text=<?= rawurlencode($shareText) ?>" target="_blank" rel="noopener" aria-label="Share on WhatsApp" style="--c:#25D366"><i class="fa-brands fa-whatsapp"></i></a>
        <a href="https://www.facebook.com/sharer/sharer.php?u=<?= rawurlencode($link) ?>" target="_blank" rel="noopener" aria-label="Share on Facebook" style="--c:#1877F2"><i class="fa-brands fa-facebook-f"></i></a>
        <a href="https://twitter.com/intent/tweet?text=<?= rawurlencode($shareText) ?>" target="_blank" rel="noopener" aria-label="Share on X" style="--c:#000"><i class="fa-brands fa-x-twitter"></i></a>
        <a href="sms:?&body=<?= rawurlencode($shareText) ?>" aria-label="Share by SMS" style="--c:var(--leaf)"><i class="fa-solid fa-comment-sms"></i></a>
      </div>
      <p class="field__hint mb-0">Your code: <b><?= e($code) ?></b></p>
    </div>

    <div class="card-fc card-fc--pad">
      <h2 style="font-size:1.15rem">Points history</h2>
      <?php if (!$ledger): ?><p class="text-muted-2 mb-0">No points yet — your first order earns <?= $perR10 ?> point for every R10.</p><?php endif; ?>
      <table class="table-fc">
        <tbody>
        <?php foreach ($ledger as $l): ?>
          <tr><td><?= e($l['reason']) ?><div class="text-muted-3" style="font-size:.75rem"><?= date('j M Y', strtotime($l['created_at'])) ?></div></td>
              <td class="text-end"><b style="color:<?= $l['points'] >= 0 ? 'var(--leaf)' : 'var(--coral)' ?>"><?= $l['points'] >= 0 ? '+' : '' ?><?= (int) $l['points'] ?></b></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card-fc card-fc--pad mb-3" style="background:var(--leaf-wash);border-color:transparent">
      <h2 style="font-size:1.05rem">How points work</h2>
      <ul style="font-size:.88rem;color:var(--text-2);padding-left:1.1rem;line-height:1.9;margin:0">
        <li><b><?= $perR10 ?> point for every R10</b> you spend on products</li>
        <li><b><?= (int) get_setting('signup_points', '50') ?> points</b> welcome bonus</li>
        <li><b><?= $refPts ?> points</b> for each friend's first order</li>
        <li>Every point is worth <b><?= format_price(points_value(1)) ?></b> — spend from <?= $minRedeem ?> points</li>
        <li>Points are added once your order is paid</li>
      </ul>
    </div>
    <div class="card-fc card-fc--pad">
      <h2 style="font-size:1.05rem">Friends you invited</h2>
      <?php if (!$friends): ?><p class="text-muted-2 mb-0" style="font-size:.9rem">Nobody yet — share your link above.</p><?php endif; ?>
      <?php foreach ($friends as $f): ?>
        <div class="d-flex justify-content-between py-2" style="border-top:1px solid var(--line);font-size:.9rem">
          <span><?= e(explode(' ', (string) $f['name'])[0]) ?> <span class="text-muted-3">· joined <?= date('j M', strtotime($f['created_at'])) ?></span></span>
          <span class="pill pill--<?= $f['orders'] ? 'ok' : 'muted' ?>"><?= $f['orders'] ? 'Ordered ✓' : 'No order yet' ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/dash-foot.php'; ?>
