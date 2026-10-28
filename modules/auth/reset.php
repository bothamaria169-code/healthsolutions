<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/functions.php';

$token = (string) ($_GET['token'] ?? $_POST['token'] ?? '');
$error = '';
$done  = false;

// The expiry is written with PHP's clock, so compare with PHP's clock too —
// MySQL's NOW() can run in a different timezone on the host.
$stmt = db()->prepare('SELECT id, name, email, phone, pin_hash FROM users WHERE reset_token = :t AND reset_expires > :now LIMIT 1');
$stmt->execute([':t' => $token, ':now' => date('Y-m-d H:i:s')]);
$user = $token !== '' ? $stmt->fetch() : false;

// Patients sign in with cellphone + PIN, so they get a new PIN, not a password.
$isPatient = $user && !empty($user['pin_hash']);

if ($user && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = (string) ($_POST['password'] ?? '');
    $confirm  = (string) ($_POST['confirm'] ?? '');
    if ($isPatient) { $password = trim($password); $confirm = trim($confirm); }

    if (!verify_csrf((string) ($_POST['csrf_token'] ?? ''))) {
        $error = 'Your session expired. Please try again.';
    } elseif ($isPatient && !Health::validPin($password)) {
        $error = 'Choose a 4-digit PIN that isn\'t easy to guess.';
    } elseif (!$isPatient && mb_strlen($password) < 8) {
        $error = 'Your password needs at least 8 characters.';
    } elseif ($password !== $confirm) {
        $error = $isPatient ? 'The two PINs do not match.' : 'The two passwords do not match.';
    } else {
        db_update('users', (int) $user['id'], ($isPatient
            ? ['pin_hash' => Health::hashPin($password), 'pin_failed' => 0, 'pin_locked_until' => null]
            : ['password' => password_hash($password, PASSWORD_BCRYPT, ['cost' => 12])])
            + ['reset_token' => null, 'reset_expires' => null]);
        $done = true;
    }
}

$what = $isPatient ? 'PIN' : 'password';
$page_title = 'Set a new ' . $what . ' — ' . SITE_NAME;
include __DIR__ . '/../../includes/header.php';
?>
<div class="section">
  <div class="container" style="max-width:480px">
    <div class="card-fc card-fc--pad">
      <?php if ($done): ?>
        <h1 style="font-size:1.6rem"><?= $isPatient ? 'PIN updated' : 'Password updated' ?></h1>
        <div class="alert-fc alert-fc--ok"><i class="fa-solid fa-circle-check"></i><span>
          <?= $isPatient ? 'Sign in with your cellphone number and your new PIN now.' : 'You can sign in with your new password now.' ?></span></div>
        <a href="<?= SITE_URL ?>/modules/auth/login.php<?= $isPatient ? '' : '?mode=staff' ?>" class="btn-fc btn-fc--block">Sign in</a>

      <?php elseif (!$user): ?>
        <h1 style="font-size:1.6rem">This link has expired</h1>
        <p class="text-muted-2">Reset links work once and last an hour. Request a fresh one.</p>
        <a href="<?= SITE_URL ?>/modules/auth/forgot.php?pin=1" class="btn-fc btn-fc--block">Request a new link</a>

      <?php else: ?>
        <h1 style="font-size:1.6rem">Set a new <?= $what ?></h1>
        <p class="text-muted-2">For <b><?= e($isPatient ? Health::displayPhone($user['phone']) : $user['email']) ?></b>.</p>
        <?php if ($error): ?>
          <div class="alert-fc alert-fc--danger"><i class="fa-solid fa-circle-exclamation"></i><span><?= e($error) ?></span></div>
        <?php endif; ?>
        <form method="post" novalidate>
          <?= csrf_field() ?>
          <input type="hidden" name="token" value="<?= e($token) ?>">
          <?php if ($isPatient): ?>
          <div class="field">
            <label for="password">New 4-digit PIN</label>
            <input class="input-fc input-pin" style="font-size:1.1rem;letter-spacing:.4em" type="password" id="password" name="password" inputmode="numeric" maxlength="4" pattern="\d{4}" required autofocus autocomplete="new-password">
          </div>
          <div class="field">
            <label for="confirm">New PIN again</label>
            <input class="input-fc input-pin" style="font-size:1.1rem;letter-spacing:.4em" type="password" id="confirm" name="confirm" inputmode="numeric" maxlength="4" pattern="\d{4}" required autocomplete="new-password">
          </div>
          <button class="btn-fc btn-fc--block" type="submit">Save new PIN</button>
          <?php else: ?>
          <div class="field">
            <label for="password">New password</label>
            <input class="input-fc" type="password" id="password" name="password" required minlength="8" autofocus autocomplete="new-password">
          </div>
          <div class="field">
            <label for="confirm">Confirm new password</label>
            <input class="input-fc" type="password" id="confirm" name="confirm" required minlength="8" autocomplete="new-password">
          </div>
          <button class="btn-fc btn-fc--block" type="submit">Save new password</button>
          <?php endif; ?>
        </form>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
