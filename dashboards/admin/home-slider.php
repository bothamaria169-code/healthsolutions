<?php
declare(strict_types=1);
$dash_role   = 'admin';
$dash_active = 'home-slider';
$page_title  = 'Home slider — Phola admin';
require_once __DIR__ . '/../../includes/dash-head.php';

$repo  = new ProductRepository();
$flash = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf((string) ($_POST['csrf_token'] ?? ''))) {
    $sid    = (int) ($_POST['id'] ?? 0);
    $action = (string) ($_POST['action'] ?? '');
    $data   = [
        'product_id' => (int) ($_POST['product_id'] ?? 0),
        'sort_order' => (int) ($_POST['sort_order'] ?? 0),
        'status'     => ($_POST['status'] ?? '') === 'draft' ? 'draft' : 'published',
    ];
    // A wide photo just for the slider (optional): otherwise the product's own header image is used.
    if (!empty($_FILES['image']['name'])) {
        $data['image'] = save_public_image($_FILES['image'], 'slides');
        if ($data['image'] === null) {
            unset($data['image']);
            $flash = ['danger', 'That photo could not be used. Use a JPG, PNG or WebP under 8 MB.'];
        }
    } elseif (!empty($_POST['remove_image'])) {
        $data['image'] = null;
    }

    if ($action === 'delete' && $sid) {
        db()->prepare('DELETE FROM home_slides WHERE id = :id')->execute([':id' => $sid]);
        $flash = ['ok', 'Slide removed.'];
    } elseif (in_array($action, ['add', 'save'], true) && !$flash) {
        if (!$repo->getById($data['product_id'])) {
            $flash = ['danger', 'Choose a product for the slide.'];
        } elseif ($action === 'save' && $sid) {
            db_update('home_slides', $sid, $data);
            $flash = ['ok', 'Slide saved.'];
        } else {
            if ($data['sort_order'] === 0) {
                $data['sort_order'] = 1 + (int) db()->query('SELECT COALESCE(MAX(sort_order), 0) FROM home_slides')->fetchColumn();
            }
            db_insert('home_slides', $data);
            $flash = ['ok', 'Slide added — it shows on the home page straight away.'];
        }
    }
}

$products = db()->query("SELECT id, name, header_image, featured_image FROM products WHERE status = 'published' ORDER BY name")->fetchAll();
$byId     = array_column($products, null, 'id');
$rows     = db()->query('SELECT * FROM home_slides ORDER BY sort_order, id')->fetchAll();
$fallback = $rows ? [] : array_filter($repo->getFeatured(8), fn($p) => !empty($p['header_image']));

$productSelect = function (int $selected) use ($products): string {
    $html = '<option value="">Choose a product…</option>';
    foreach ($products as $p) {
        $html .= '<option value="' . (int) $p['id'] . '"' . ((int) $p['id'] === $selected ? ' selected' : '') . '>' . e($p['name']) . '</option>';
    }
    return $html;
};
$slideImage = fn(array $s) => product_image($s['image'] ?: ($byId[$s['product_id']]['header_image'] ?? null) ?: ($byId[$s['product_id']]['featured_image'] ?? null));
?>

<div class="dash-head">
  <div><h1>Home slider</h1><p>The big photo slider at the top of the <a class="text-leaf" href="<?= SITE_URL ?>/" target="_blank">home page</a>. Pick products from your catalogue, put them in order and, if you like, give a slide its own photo.</p></div>
</div>

<?php if ($flash): ?>
  <div class="alert-fc alert-fc--<?= $flash[0] ?>"><i class="fa-solid fa-circle-<?= $flash[0] === 'ok' ? 'check' : 'exclamation' ?>"></i><span><?= e($flash[1]) ?></span></div>
<?php endif; ?>

<div class="row g-3">
  <div class="col-lg-8">
    <?php if (!$rows): ?>
      <div class="card-fc card-fc--pad mb-3 text-muted-2" style="font-size:.9rem">
        No slides picked yet, so the slider shows your <b>featured</b> products that have a header image<?= $fallback ? ': ' . e(implode(', ', array_column($fallback, 'name'))) : '' ?>.
        Add a slide on the right to choose them yourself.
      </div>
    <?php endif; ?>

    <?php foreach ($rows as $s): ?>
      <form method="post" enctype="multipart/form-data" class="card-fc card-fc--pad mb-3">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
        <div class="row g-3 align-items-start">
          <div class="col-md-5">
            <img src="<?= e($slideImage($s)) ?>" alt="" style="width:100%;aspect-ratio:16/9;object-fit:cover;border-radius:10px;border:1px solid var(--line)">
            <?php if (!isset($byId[$s['product_id']])): ?><div class="mt-1" style="font-size:.78rem;color:var(--coral)">This product is not published, so the slide is skipped.</div><?php endif; ?>
          </div>
          <div class="col-md-7">
            <div class="field"><label>Product</label>
              <select class="select-fc" name="product_id" required><?= $productSelect((int) $s['product_id']) ?></select></div>
            <div class="field"><label>Slide photo <span class="text-muted-3 fw-normal">(optional, wide 16:9)</span></label>
              <input class="input-fc" type="file" name="image" accept="image/jpeg,image/png,image/webp">
              <?php if ($s['image']): ?><label class="mt-1" style="font-size:.82rem"><input type="checkbox" name="remove_image" value="1"> Use the product's own header image instead</label><?php endif; ?></div>
            <div class="row g-2">
              <div class="col-5 field"><label>Order</label><input class="input-fc" type="number" name="sort_order" value="<?= (int) $s['sort_order'] ?>"></div>
              <div class="col-7 field"><label>Status</label><select class="select-fc" name="status">
                <option value="published" <?= $s['status'] === 'published' ? 'selected' : '' ?>>Live</option>
                <option value="draft" <?= $s['status'] === 'draft' ? 'selected' : '' ?>>Hidden</option></select></div>
            </div>
            <div class="d-flex gap-1">
              <button class="btn-fc btn-fc--sm" name="action" value="save" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save</button>
              <a class="btn-fc btn-fc--sm btn-fc--light" href="product-form.php?id=<?= (int) $s['product_id'] ?>">Edit product</a>
              <button class="btn-fc btn-fc--sm btn-fc--light ms-auto" style="color:var(--coral)" name="action" value="delete" type="submit" formnovalidate onclick="return confirm('Remove this slide?')" aria-label="Remove"><i class="fa-solid fa-trash-can"></i></button>
            </div>
          </div>
        </div>
      </form>
    <?php endforeach; ?>
  </div>

  <div class="col-lg-4">
    <form method="post" enctype="multipart/form-data" class="card-fc card-fc--pad">
      <?= csrf_field() ?><input type="hidden" name="action" value="add">
      <h2 style="font-size:1.05rem">Add a slide</h2>
      <div class="field"><label for="product_id">Product</label>
        <select class="select-fc" id="product_id" name="product_id" required><?= $productSelect(0) ?></select>
        <div class="field__hint">The slide links to this product and shows its name and price.</div></div>
      <div class="field"><label for="image">Slide photo <span class="text-muted-3 fw-normal">(optional)</span></label>
        <input class="input-fc" type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp">
        <div class="field__hint">Wide landscape, about 1600 × 900. Leave empty to use the product's header image.</div></div>
      <div class="row g-2">
        <div class="col-5 field"><label for="sort_order">Order</label><input class="input-fc" type="number" id="sort_order" name="sort_order" value="0"></div>
        <div class="col-7 field"><label for="status">Status</label><select class="select-fc" id="status" name="status"><option value="published">Live</option><option value="draft">Hidden</option></select></div>
      </div>
      <button class="btn-fc btn-fc--block" type="submit"><i class="fa-solid fa-plus"></i> Add slide</button>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/dash-foot.php'; ?>
