<?php
/**
 * Language menu. Picking a language sets Google Translate's own `googtrans`
 * cookie and reloads; the translate script is only loaded once a language
 * other than English has been chosen (see the footer), so English visitors
 * never load anything from Google.
 * Optional: $lang_id (unique id when the menu appears twice on a page).
 */
$lang_id  = $lang_id ?? 'langPick';
$lang_now = current_language();
?>
<label class="lang-pick notranslate" for="<?= e($lang_id) ?>" title="Change language">
  <i class="fa-solid fa-language" aria-hidden="true"></i>
  <select id="<?= e($lang_id) ?>" data-lang-pick aria-label="Language">
    <?php foreach (SITE_LANGUAGES as $code => $label): ?>
      <option value="<?= e($code) ?>" <?= $code === $lang_now ? 'selected' : '' ?>><?= e($label) ?></option>
    <?php endforeach; ?>
  </select>
</label>
