<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$email = e((string) get_setting('contact_email', SITE_EMAIL));
$wa    = e(Health::displayPhone(whatsapp_number()));
$waUrl = e(wa_link('Hi Phola, please delete my account and data.'));

$policy = [
    'key'     => 'deletion',
    'title'   => 'Delete your data',
    'lede'    => 'How to close your Phola account and have everything we hold about you deleted, including data from Facebook or Google sign-in.',
    'updated' => '25 October 2026',
    'summary' => [
        'Ask by <b>email</b> or <b>WhatsApp</b> — no form to fill in.',
        'We delete your account, health tracker records and sign-in links within <b>30 days</b>.',
        'We confirm by email or WhatsApp once it is done.',
    ],
    'sections' => [
        [
            'id' => 'how', 'icon' => 'fa-trash-can', 'title' => 'How to ask',
            'html' => <<<HTML
<ol>
  <li>Email <a href="mailto:{$email}?subject=Delete%20my%20data">{$email}</a> with the subject <b>Delete my data</b>, or WhatsApp us on <a href="{$waUrl}" target="_blank" rel="noopener">{$wa}</a>.</li>
  <li>Tell us the email address or phone number on your Phola account.</li>
  <li>We check it is really you (we reply to that email or number) and delete your data within <b>30 days</b>.</li>
</ol>
HTML,
        ],
        [
            'id' => 'facebook', 'icon' => 'fa-share-nodes', 'title' => 'Facebook and Google sign-in',
            'html' => <<<HTML
<p>If you signed in with Facebook or Google, we only stored your name, email address and the account ID they gave us.
Asking us to delete your data removes all of it.</p>
<p>You can also remove Phola from your Facebook account yourself: Facebook → <b>Settings &amp; privacy</b> → <b>Settings</b> →
<b>Apps and websites</b> → choose <b>Phola Health Solutions</b> → <b>Remove</b>.</p>
HTML,
        ],
        [
            'id' => 'kept', 'icon' => 'fa-scale-balanced', 'title' => 'What we must keep',
            'html' => <<<HTML
<p>The law makes us keep invoices and payment records for up to five years (tax law). Those are kept but no longer linked
to an account you can sign in to. Everything else is deleted. See our <a href="privacy.php">Privacy policy</a> for more.</p>
HTML,
        ],
    ],
];

include __DIR__ . '/includes/policy-page.php';
