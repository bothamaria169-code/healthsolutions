<?php
require_once __DIR__ . '/functions.php';

$current_user   = current_user();
$cart_count     = cart_count();
$wishlist_count = $current_user ? count(wishlist_ids((int) $current_user['id'])) : 0;
$is_reseller    = Pricing::isReseller($current_user);

$page_title       = $page_title       ?? SITE_NAME . ' — ' . SITE_TAGLINE;
$meta_description = $meta_description ?? 'Shop South African wellness, fragrance, cleaning and pest-control brands in one place. Real stock, honest prices, free courier delivery over R1,500.';
$body_class       = $body_class       ?? '';
$og_image         = $og_image         ?? SITE_URL . '/assets/img/stock/shieldwell.jpg';
$extra_css        = $extra_css        ?? [];
$json_ld          = $json_ld          ?? [];
// REQUEST_URI already starts with any sub-folder, so join it to the scheme + host
// only (SITE_URL would add the folder twice when the site lives in one).
$canonical        = $canonical        ?? preg_replace('~^(https?://[^/]+).*$~', '$1', SITE_URL) . ($_SERVER['REQUEST_URI'] ?? '/');

// Newsletter sign-up gate: 'locked', 'popup' or null. Locked also freezes page scroll
// from the first paint, before any script has run.
$nl_gate = NewsletterService::gateFor($current_user);
if ($nl_gate === 'locked') {
    $body_class = trim($body_class . ' is-gated');
}
?>
<!doctype html>
<html lang="en-ZA">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($page_title) ?></title>
<meta name="description" content="<?= e($meta_description) ?>">
<?php if (!empty($meta_robots)): ?><meta name="robots" content="<?= e($meta_robots) ?>"><?php endif; ?>
<meta name="theme-color" content="#0F3D24">
<link rel="canonical" href="<?= e($canonical) ?>">

<meta property="og:site_name" content="<?= e(SITE_NAME) ?>">
<meta property="og:title" content="<?= e($page_title) ?>">
<meta property="og:description" content="<?= e($meta_description) ?>">
<meta property="og:image" content="<?= e($og_image) ?>">
<meta property="og:type" content="website">
<meta property="og:locale" content="en_ZA">
<meta name="twitter:card" content="summary_large_image">

<link rel="icon" type="image/png" href="<?= SITE_URL ?>/assets/img/logo/phola-mark.png">
<link rel="apple-touch-icon" href="<?= SITE_URL ?>/assets/img/logo/phola-mark.png">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<link rel="stylesheet" href="<?= asset('assets/css/theme.css') ?>">
<link rel="stylesheet" href="<?= asset('assets/css/components.css') ?>">
<link rel="stylesheet" href="<?= asset('assets/css/layout.css') ?>">
<link rel="stylesheet" href="<?= asset('assets/css/pages.css') ?>">
<link rel="stylesheet" href="<?= asset('assets/css/responsive.css') ?>">
<link rel="stylesheet" href="<?= asset('assets/css/phola.css') ?>">
<link rel="stylesheet" href="<?= asset('assets/css/phola-store.css') ?>">
<link rel="stylesheet" href="<?= asset('assets/css/phola-pages.css') ?>">
<link rel="stylesheet" href="<?= asset('assets/css/phola-engage.css') ?>">
<?php foreach ($extra_css as $href): ?>
<link rel="stylesheet" href="<?= e($href) ?>">
<?php endforeach; ?>

<?php
$ga4   = preg_replace('/[^A-Z0-9-]/', '', strtoupper((string) get_setting('ga4_id', ''))) ?? '';
$pixel = preg_replace('/\D/', '', (string) get_setting('meta_pixel_id', '')) ?? '';
$gsv   = (string) get_setting('google_site_verification', '');
?>
<?php if ($gsv !== ''): ?><meta name="google-site-verification" content="<?= e($gsv) ?>">
<?php endif; ?>
<?php if ($ga4 !== ''): ?>
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= e($ga4) ?>"></script>
<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','<?= e($ga4) ?>');</script>
<?php endif; ?>
<?php if ($pixel !== ''): ?>
<script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init','<?= e($pixel) ?>');fbq('track','PageView');</script>
<?php endif; ?>
<script>
// Count every WhatsApp enquiry as a lead in Analytics and the Pixel.
document.addEventListener('click', function (ev) {
  var a = ev.target.closest && ev.target.closest('a[href*="wa.me"],a[href*="enquire.php"]');
  if (!a) return;
  if (window.gtag) gtag('event', 'generate_lead', { method: 'whatsapp' });
  if (window.fbq) fbq('track', 'Lead');
});
</script>

<script type="application/ld+json"><?= json_encode(array_merge([
    '@context' => 'https://schema.org',
    '@type'    => 'Organization',
    'name'     => SITE_NAME,
    'url'      => SITE_URL,
    'logo'     => SITE_URL . '/assets/img/logo/phola-mark.png',
    'slogan'   => SITE_TAGLINE,
    'address'  => [
        '@type'          => 'PostalAddress',
        'streetAddress'  => get_setting('contact_address', ''),
        'addressCountry' => 'ZA',
    ],
    'contactPoint' => [
        '@type'       => 'ContactPoint',
        'telephone'   => get_setting('contact_phone', ''),
        'email'       => get_setting('contact_email', SITE_EMAIL),
        'contactType' => 'customer service',
        'areaServed'  => 'ZA',
    ],
], $json_ld), JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?></script>
</head>
<body class="has-tabbar <?= e($body_class) ?>">
<?php if ($nl_gate) { include __DIR__ . '/newsletter-gate.php'; } ?>
<a class="skip-link" href="#main">Skip to content</a>

<?php include __DIR__ . '/navbar.php'; ?>

<main id="main">
