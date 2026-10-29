<?php
declare(strict_types=1);
$dash_role   = 'admin';
$dash_active = 'categories';
$page_title  = 'Categories — Phola admin';
require_once __DIR__ . '/../../includes/dash-head.php';

$flash = $_SESSION['categories_flash'] ?? null;
unset($_SESSION['categories_flash']);
$error = null;

/** A slug no other category uses, so a repeated name can't hit the UNIQUE key. */
function category_unique_slug(string $name, int $exceptId = 0): string
{
    $base = slugify($name);
    $slug = $base;
    $stmt = db()->prepare('SELECT COUNT(*) FROM categories WHERE slug = :s AND id <> :id');
    for ($i = 2; ; $i++) {
        $stmt->execute([':s' => $slug, ':id' => $exceptId]);
        if (!(int) $stmt->fetchColumn()) {
            return $slug;
        }
        $slug = $base . '-' . $i;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    $cid    = (int) ($_POST['id'] ?? 0);

    if (!verify_csrf((string) ($_POST['csrf_token'] ?? ''))) {
        $error = 'Your session expired before the form was sent. Please try again.';
    } elseif ($action === 'save') {
        $data = [
            'name'       => mb_substr(clean_text($_POST['name'] ?? ''), 0, 120),
            'icon'       => mb_substr(clean_text($_POST['icon'] ?? ''), 0, 60) ?: null,
            'blurb'      => mb_substr(clean_text($_POST['blurb'] ?? ''), 0, 255) ?: null,
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
            'status'     => ($_POST['status'] ?? '') === 'inactive' ? 'inactive' : 'active',
        ];
        if ($data['name'] === '') {
            $error = 'Give the category a name.';
        } else {
            try {
                if ($cid) {
                    db_update('categories', $cid, $data);
                    $_SESSION['categories_flash'] = 'Category updated.';
                } else {
                    $data['slug'] = category_unique_slug($data['name']);
                    db_insert('categories', $data);
                    $_SESSION['categories_flash'] = 'Category added.';
                }
                redirect('dashboards/admin/categories.php');
            } catch (PDOException $e) {
                error_log('Category save failed: ' . $e->getMessage());
                $error = 'The category could not be saved: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'delete' && $cid) {
        // Products keep existing; they just fall out of the category.
        db()->prepare('UPDATE products SET category_id = NULL WHERE category_id = :id')->execute([':id' => $cid]);
        db()->prepare('DELETE FROM categories WHERE id = :id')->execute([':id' => $cid]);
        $_SESSION['categories_flash'] = 'Category deleted. Its products are now uncategorised.';
        redirect('dashboards/admin/categories.php');
    }
}

$rows = db()->query(
    "SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) n
     FROM categories c ORDER BY c.sort_order, c.name"
)->fetchAll();
$edit = null;
if (!empty($_GET['edit'])) {
    $stmt = db()->prepare('SELECT * FROM categories WHERE id = :id');
    $stmt->execute([':id' => (int) $_GET['edit']]);
    $edit = $stmt->fetch() ?: null;
}
?>
<div class="dash-head"><div><h1>Categories</h1><p>The aisles the shop is organised into.</p></div></div>

<?php if ($flash): ?><div class="alert-fc alert-fc--ok"><i class="fa-solid fa-circle-check"></i><span><?= e($flash) ?></span></div><?php endif; ?>
<?php if ($error): ?><div class="alert-fc alert-fc--danger"><i class="fa-solid fa-circle-exclamation"></i><span><?= e($error) ?></span></div><?php endif; ?>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="table-wrap">
      <table class="table-fc">
        <thead><tr><th>Category</th><th>Slug</th><th class="text-center">Products</th><th class="text-center">Order</th><th>Status</th><th style="width:1%"></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $c): ?>
          <tr>
            <td>
              <i class="fa-solid <?= e($c['icon'] ?: 'fa-tag') ?> me-2 text-leaf"></i><b><?= e($c['name']) ?></b>
              <?php if ($c['blurb']): ?><span class="d-block text-muted-3" style="font-size:.75rem"><?= e($c['blurb']) ?></span><?php endif; ?>
            </td>
            <td style="font-family:var(--font-mono);font-size:.76rem"><?= e($c['slug']) ?></td>
            <td class="text-center"><?= (int) $c['n'] ?></td>
            <td class="text-center"><?= (int) $c['sort_order'] ?></td>
            <td><span class="pill pill--<?= $c['status'] === 'active' ? 'ok' : 'muted' ?>"><?= e(ucfirst($c['status'])) ?></span></td>
            <td>
              <div class="d-flex gap-1">
                <a class="btn-fc btn-fc--sm btn-fc--light" href="?edit=<?= (int) $c['id'] ?>"><i class="fa-solid fa-pen"></i></a>
                <form method="post" onsubmit="return confirm('Delete this category? Its <?= (int) $c['n'] ?> product(s) stay but become uncategorised.')">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                  <button class="btn-fc btn-fc--sm btn-fc--light" style="color:var(--coral)"><i class="fa-solid fa-trash-can"></i></button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="col-lg-4">
    <form method="post" class="card-fc card-fc--pad">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>">
      <h2 style="font-size:1.05rem"><?= $edit ? 'Edit category' : 'Add a category' ?></h2>
      <div class="field"><label for="name">Name</label>
        <input class="input-fc" type="text" id="name" name="name" value="<?= e((string) ($edit['name'] ?? '')) ?>" required></div>
      <div class="field"><label for="icon">Font Awesome icon</label>
        <input class="input-fc" type="text" id="icon" name="icon" value="<?= e((string) ($edit['icon'] ?? '')) ?>" placeholder="fa-heart-pulse">
        <div class="field__hint">Class name only, e.g. <code>fa-spray-can-sparkles</code>.</div></div>
      <div class="field"><label for="blurb">One-line description</label>
        <input class="input-fc" type="text" id="blurb" name="blurb" value="<?= e((string) ($edit['blurb'] ?? '')) ?>"></div>
      <div class="row g-2">
        <div class="col-6 field"><label for="sort_order">Sort order</label>
          <input class="input-fc" type="number" id="sort_order" name="sort_order" value="<?= (int) ($edit['sort_order'] ?? 0) ?>"></div>
        <div class="col-6 field"><label for="status">Status</label>
          <select class="select-fc" id="status" name="status">
            <option value="active" <?= ($edit['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
            <option value="inactive" <?= ($edit['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
          </select></div>
      </div>
      <button class="btn-fc btn-fc--block" type="submit"><?= $edit ? 'Save changes' : 'Add category' ?></button>
      <?php if ($edit): ?><a href="categories.php" class="btn-fc btn-fc--light btn-fc--block mt-2">Cancel</a><?php endif; ?>
    </form>
  </div>
</div>
<?php require_once __DIR__ . '/../../includes/dash-foot.php'; ?>
