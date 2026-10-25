<?php
declare(strict_types=1);
$dash_role   = 'admin';
$dash_active = 'mailbox';
$page_title  = 'Mailbox — Phola admin';
require_once __DIR__ . '/../../includes/dash-head.php';

/** A reply in the Phola email shell: their words, our signature, the quoted original. */
function mailbox_html(string $text, string $quoted = ''): string
{
    $body = '<p style="margin:0 0 14px">' . nl2br(e(trim($text)), false) . '</p>'
        . '<p style="margin:22px 0 0;color:#5B6B60">Kind regards,<br><b style="color:#0F3D24">The Phola Health Solutions team</b><br>'
        . 'WhatsApp ' . e(Health::displayPhone(whatsapp_number())) . ' · <a href="' . e(SITE_URL) . '" style="color:#2E8B3E">' . e(SITE_DOMAIN) . '</a></p>';
    if (trim($quoted) !== '') {
        $body .= '<div style="margin-top:26px;padding:12px 16px;border-left:3px solid #DDE5DE;color:#7C8899;font-size:13px">'
            . nl2br(e(mb_substr(trim($quoted), 0, 3000)), false) . '</div>';
    }
    return email_wrap($body);
}

$flash = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf((string) ($_POST['csrf_token'] ?? ''))) {
    $to      = trim((string) ($_POST['to'] ?? ''));
    $subject = clean_text((string) ($_POST['subject'] ?? ''));
    $text    = (string) ($_POST['body'] ?? '');
    if (!safe_email($to) || $subject === '' || trim($text) === '') {
        $flash = ['danger', 'Add an email address, a subject and your message.'];
    } else {
        $headers = array_filter(['In-Reply-To' => (string) ($_POST['in_reply_to'] ?? ''),
                                 'References'  => trim((string) ($_POST['references'] ?? '') . ' ' . (string) ($_POST['in_reply_to'] ?? ''))]);
        $ok = Mailer::send($to, $subject, mailbox_html($text, (string) ($_POST['quoted'] ?? '')), trim($text), SMTP_USER, SITE_NAME, $headers);
        $flash = $ok ? ['ok', 'Sent to ' . $to . '.'] : ['danger', 'The email could not be sent. Check the mailbox password in config/secrets.php.'];
    }
}

// Email address alone from "Name <a@b.c>".
$addr = fn(string $s) => preg_match('/<([^>]+)>/', $s, $m) ? $m[1] : trim($s);

$list = [];
$msg  = null;
$error = '';
if (!Imap::ready()) {
    $error = 'The mailbox password is not set (smtp_pass in config/secrets.php).';
} else {
    $imap = new Imap();
    if ($imap->connect()) {
        $list = $imap->latest(40);
        if (!empty($_GET['uid'])) {
            $msg = $imap->message((int) $_GET['uid']);
        }
        $imap->close();
    } else {
        $error = $imap->error;
    }
}
$compose = $msg ? [
    'to'      => $addr($msg['reply_to'] ?: $msg['from']),
    'subject' => preg_match('/^re:/i', $msg['subject']) ? $msg['subject'] : 'Re: ' . $msg['subject'],
    'quoted'  => "On {$msg['date']}, {$msg['from']} wrote:\n" . ($msg['text'] ?: trim(html_entity_decode(strip_tags((string) preg_replace('/<(style|script)[^>]*>.*?<\/\1>/is', '', $msg['html']))))),
] : ['to' => (string) ($_GET['to'] ?? ''), 'subject' => (string) ($_GET['subject'] ?? ''), 'quoted' => (string) ($_GET['quote'] ?? '')];
?>

<div class="dash-head">
  <div><h1>Mailbox</h1><p>The <b><?= e(SMTP_USER) ?></b> inbox. Replies go out in the Phola email design with your signature.</p></div>
  <a class="btn-fc btn-fc--sm" href="mailbox.php?new=1"><i class="fa-solid fa-pen"></i> New email</a>
</div>

<?php if ($flash): ?>
  <div class="alert-fc alert-fc--<?= $flash[0] ?>"><i class="fa-solid fa-circle-<?= $flash[0] === 'ok' ? 'check' : 'exclamation' ?>"></i><span><?= e($flash[1]) ?></span></div>
<?php endif; ?>
<?php if ($error): ?>
  <div class="alert-fc alert-fc--danger"><i class="fa-solid fa-circle-exclamation"></i><span><?= e($error) ?></span></div>
<?php endif; ?>

<div class="row g-3">
  <div class="col-lg-4">
    <div class="card-fc" style="max-height:78vh;overflow:auto">
      <?php if (!$list && !$error): ?><p class="text-muted-2 p-3 mb-0">The inbox is empty.</p><?php endif; ?>
      <?php foreach ($list as $m): ?>
        <a href="?uid=<?= $m['uid'] ?>" class="d-block p-3" style="border-bottom:1px solid var(--line);text-decoration:none;color:inherit;<?= ($msg['uid'] ?? 0) === $m['uid'] ? 'background:var(--leaf-wash)' : '' ?>">
          <div class="d-flex justify-content-between gap-2" style="font-size:.84rem">
            <b style="<?= $m['seen'] ? 'font-weight:600;color:var(--text-2)' : 'color:var(--ink)' ?>"><?= e(mb_strimwidth(preg_replace('/\s*<.*$/', '', $m['from']) ?: $m['from'], 0, 34, '…')) ?></b>
            <span class="text-muted-3" style="white-space:nowrap"><?= $m['date'] ? e(date('j M', strtotime($m['date']) ?: time())) : '' ?></span>
          </div>
          <div style="font-size:.86rem;<?= $m['seen'] ? 'color:var(--text-2)' : 'font-weight:700' ?>"><?= e(mb_strimwidth($m['subject'], 0, 60, '…')) ?></div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="col-lg-8">
    <?php if ($msg): ?>
      <div class="card-fc card-fc--pad mb-3">
        <h2 style="font-size:1.15rem"><?= e($msg['subject']) ?></h2>
        <div class="text-muted-2 mb-3" style="font-size:.84rem">From <b><?= e($msg['from']) ?></b> · <?= e($msg['date']) ?></div>
        <?php if ($msg['html'] !== ''): ?>
          <iframe sandbox="" srcdoc="<?= e($msg['html']) ?>" style="width:100%;height:480px;border:1px solid var(--line);border-radius:10px;background:#fff"></iframe>
        <?php else: ?>
          <div style="white-space:pre-wrap;font-size:.92rem"><?= e($msg['text']) ?></div>
        <?php endif; ?>
        <?php if ($msg['files']): ?><div class="mt-2" style="font-size:.82rem"><i class="fa-solid fa-paperclip"></i> <?= e(implode(', ', $msg['files'])) ?> <span class="text-muted-3">(open attachments in webmail)</span></div><?php endif; ?>
      </div>
    <?php endif; ?>

    <?php if ($msg || !empty($_GET['new']) || !empty($_GET['to'])): ?>
      <form method="post" class="card-fc card-fc--pad">
        <?= csrf_field() ?>
        <input type="hidden" name="in_reply_to" value="<?= e($msg['message_id'] ?? '') ?>">
        <input type="hidden" name="references" value="<?= e($msg['references'] ?? '') ?>">
        <input type="hidden" name="quoted" value="<?= e($compose['quoted']) ?>">
        <h2 style="font-size:1.05rem"><?= $msg ? 'Reply' : 'New email' ?></h2>
        <div class="row g-2">
          <div class="col-md-6 field"><label>To</label><input class="input-fc" type="email" name="to" value="<?= e($compose['to']) ?>" required></div>
          <div class="col-md-6 field"><label>Subject</label><input class="input-fc" name="subject" value="<?= e($compose['subject']) ?>" required></div>
        </div>
        <div class="field"><label>Message</label><textarea class="textarea-fc" name="body" rows="8" required placeholder="Hi …"></textarea>
          <div class="field__hint">Sent in the Phola email design with the team signature<?= $compose['quoted'] !== '' ? ' and their message quoted underneath' : '' ?>.</div></div>
        <button class="btn-fc" type="submit"><i class="fa-solid fa-paper-plane"></i> Send</button>
      </form>
    <?php elseif (!$error): ?>
      <div class="card-fc card-fc--pad text-muted-2">Choose an email on the left to read and reply.</div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/dash-foot.php'; ?>
