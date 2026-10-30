<?php $extra_js = $extra_js ?? []; ?>
  </div><!-- /.dash-main -->
</div><!-- /.dash -->

<?php if ($dash_role === 'admin'):
  // "Add" lights up on a blank product form; "Products" on the list and on edits.
  $tab_now = basename($_SERVER['SCRIPT_NAME']) === 'product-form.php' && empty($_GET['id']) ? 'add' : $dash_active; ?>
<nav class="dash-tabs" aria-label="Quick links">
  <?php foreach ([
    ['dashboard', 'index.php',        'fa-solid fa-gauge-high',    'Home'],
    ['products',  'products.php',     'fa-solid fa-boxes-stacked', 'Products'],
    ['add',       'product-form.php', 'fa-solid fa-plus',          'Add'],
    ['enquiries', 'enquiries.php',    'fa-brands fa-whatsapp',     'Enquiries'],
  ] as [$slug, $href, $icon, $label]): ?>
    <a href="<?= $href ?>" class="<?= $slug === 'add' ? 'dash-tabs__add' : '' ?><?= $tab_now === $slug ? ' is-active' : '' ?>">
      <i class="<?= $icon ?>"></i><span><?= $label ?></span>
    </a>
  <?php endforeach; ?>
  <button type="button" id="dashTabMenu"><i class="fa-solid fa-bars"></i><span>Menu</span></button>
</nav>
<?php endif; ?>

<div class="toast-wrap" id="toastWrap" aria-live="polite"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  const SITE_URL = <?= json_encode(SITE_URL) ?>;
  const FC_USER  = <?= json_encode(['id' => (int) $user['id'], 'reseller' => $isRes]) ?>;
</script>
<script src="<?= asset('assets/js/app.js') ?>"></script>
<script src="<?= asset('assets/js/cart.js') ?>"></script>
<?php foreach ($extra_js as $src): ?>
<script src="<?= e($src) ?>"></script>
<?php endforeach; ?>
<?php include __DIR__ . '/lang-script.php'; ?>
</body>
</html>
