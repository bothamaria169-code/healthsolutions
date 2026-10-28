<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/functions.php';

$sent  = false;
$error = '';
// Patients sign in with cellphone + PIN, so their reset sets a new PIN, not a password.
$pinMode = !empty($_GET['pin']) || !empty($_POST['pin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim((string) ($_POST['email'] ?? ''));
    $email = safe_email($login);
    $phone = $email ? null : Health::normalizePhone($login);

    if (!verify_csrf((string) ($_POST['csrf_token'] ?? ''))) {
        $error = 'Your session expired. Please try again.';
    } elseif (!$email && !$phone) {
        $error = $pinMode ? 'Enter your email address or cellphone number.' : 'Enter a valid email address.';
    } elseif (!rate_limit('forgot', 5, 900)) {
        $error = 'Too many requests. Try again in fifteen minutes.';
    } else {
        $stmt = db()->prepare($email
            ? 'SELECT id, name, email, pin_hash FROM users WHERE email = :v AND is_active = 1 LIMIT 1'
            : 'SELECT id, name, email, pin_hash FROM users WHERE phone = :v AND is_active = 1 LIMIT 1');
        $stmt->execute([':v' => $email ?: $phone]);

        if (($user = $stmt->fetch()) && !empty($user['email'])) {
            $token = bin2hex(random_bytes(32));
            db_update('users', (int) $user['id'], [
                'reset_token'   => $token,
                'reset_expires' => date('Y-m-d H:i:s', time() + 3600),
            ]);

            $isPatient = !empty($user['pin_hash']);
            $what = $isPatient ? 'PIN' : 'password';
            $link = SITE_URL . '/modules/auth/reset.php?token=' . $token;
            Mailer::send((string) $user['email'], 'Reset your Phola ' . $what, email_wrap(
                '<p>Hi ' . e((string) $user['name']) . ',</p>'
                . '<p>Use the button below to set a new ' . $what . '. The link works once and expires in an hour.</p>'
                . '<p><a href="' . $link . '" style="display:inline-block;background:#2E8B3E;color:#fff;padding:11px 22px;border-radius:999px;font-weight:700;">Set a new ' . $what . '</a></p>'
                . '<p style="font-size:13px;color:#7C8899;">If you did not ask for this, ignore the email — nothing changes.</p>'
            ));
        }
        // Always report success: whether an address is registered is not something
        // this form should be willing to tell an anonymous visitor.
        $sent = true;
    }
}

$page_title = ($pinMode ? 'Reset your PIN — ' : 'Reset your password — ') . SITE_NAME;
include __DIR__ . '/../../includes/header.php';
?>
<div class="section">
  <div class="container" style="max-width:480px">
    <div class="card-fc card-fc--pad">
      <h1 style="font-size:1.6rem"><?= $pinMode ? 'Reset your PIN' : 'Reset your password' ?></h1>

      <?php if ($sent): ?>
        <div class="alert-fc alert-fc--ok mt-3">
          <i class="fa-solid fa-envelope-circle-check"></i>
          <span>If that <?= $pinMode ? 'account has an email address' : 'address has an account' ?>, a reset link is on its way. It expires in an hour.</span>
        </div>
        <?php if ($pinMode): ?>
          <p class="text-muted-2" style="font-size:.88rem">No email on your account? <a href="<?= e(wa_link('Hi Phola, I forgot my patient portal PIN. Please reset it for this number.')) ?>" target="_blank" rel="noopener">WhatsApp us from your registered number</a> and we'll reset it.</p>
        <?php endif; ?>
        <a href="<?= SITE_URL ?>/modules/auth/login.php" class="btn-fc btn-fc--ghost btn-fc--block mt-2">Back to sign in</a>
      <?php else: ?>
        <p class="text-muted-2"><?= $pinMode
            ? 'Enter the email address or cellphone number on your health tracker. We will email you a link to choose a new PIN.'
            : 'Enter the email on your account and we will send you a link.' ?></p>
        <?php if ($error): ?>
          <div class="alert-fc alert-fc--danger"><i class="fa-solid fa-circle-exclamation"></i><span><?= e($error) ?></span></div>
        <?php endif; ?>
        <form method="post" novalidate>
          <?= csrf_field() ?>
          <?php if ($pinMode): ?><input type="hidden" name="pin" value="1"><?php endif; ?>
          <div class="field">
            <label for="email"><?= $pinMode ? 'Email or cellphone number' : 'Email address' ?></label>
            <input class="input-fc" type="<?= $pinMode ? 'text' : 'email' ?>" id="email" name="email" required autofocus>
          </div>
          <button class="btn-fc btn-fc--block" type="submit">Send the reset link</button>
        </form>
        <p class="text-center mt-3 mb-0" style="font-size:.85rem">
          <a href="<?= SITE_URL ?>/modules/auth/login.php<?= $pinMode ? '' : '?mode=staff' ?>" class="text-muted-2">Back to sign in</a>
        </p>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
