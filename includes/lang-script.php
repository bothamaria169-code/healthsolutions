<?php
// Language menu behaviour + the Google Translate loader. Included by footer.php and dash-foot.php.
?>
<script>
// Language menu: Google Translate reads the googtrans cookie, so set it (on this
// host and the bare domain) and reload. English clears it.
document.querySelectorAll('[data-lang-pick]').forEach((sel) => sel.addEventListener('change', () => {
  const v = sel.value, host = location.hostname.replace(/^www\./, ''), past = 'expires=Thu, 01 Jan 1970 00:00:00 GMT';
  ['', '; domain=' + host, '; domain=.' + host].forEach((d) => {
    document.cookie = 'googtrans=' + (v === 'en' ? '; ' + past : '/en/' + v) + '; path=/' + d;
  });
  location.reload();
}));
</script>
<?php if (current_language() !== 'en'): ?>
<div id="google_translate_element" style="display:none"></div>
<script>
function phTranslateInit() {
  new google.translate.TranslateElement({ pageLanguage: 'en', includedLanguages: <?= json_encode(implode(',', array_keys(SITE_LANGUAGES))) ?>, autoDisplay: false }, 'google_translate_element');
}
</script>
<script src="https://translate.google.com/translate_a/element.js?cb=phTranslateInit" async></script>
<?php endif; ?>
