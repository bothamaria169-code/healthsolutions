<?php
declare(strict_types=1);
$dash_role   = 'admin';
$dash_active = 'testimonials';
$page_title  = 'Testimonials — Phola admin';
require_once __DIR__ . '/../../includes/dash-head.php';

$flash = null;

/** Saves one customer review image or MP4 video and returns its site path. */
function store_testimonial_media(array $file): array
{
    $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($error === UPLOAD_ERR_NO_FILE) {
        return ['path' => null, 'kind' => null, 'error' => null];
    }

    $check = validate_upload($file, ['image/jpeg', 'image/png', 'image/webp', 'video/mp4'], 50 * 1024 * 1024);
    if (!$check['ok']) {
        $uploadErrors = [
            UPLOAD_ERR_INI_SIZE   => 'The file exceeds the server upload limit. Try a smaller file.',
            UPLOAD_ERR_FORM_SIZE  => 'The selected file is too large.',
            UPLOAD_ERR_PARTIAL    => 'The file only uploaded partially. Please try again.',
            UPLOAD_ERR_NO_TMP_DIR => 'The server upload folder is unavailable.',
            UPLOAD_ERR_CANT_WRITE => 'The server could not save the uploaded file.',
        ];
        return ['path' => null, 'kind' => null, 'error' => $uploadErrors[$error] ?? $check['error']];
    }

    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'video/mp4'  => 'mp4',
    ];
    $mime = $check['mime'];
    if (str_starts_with($mime, 'image/') && (int) $file['size'] > MAX_FILE_SIZE) {
        return ['path' => null, 'kind' => null, 'error' => 'Images must be 8 MB or smaller.'];
    }

    $directory = UPLOAD_PATH . 'testimonials/';
    if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
        return ['path' => null, 'kind' => null, 'error' => 'The server could not create the testimonials upload folder.'];
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
    if (!move_uploaded_file($file['tmp_name'], $directory . $filename)) {
        return ['path' => null, 'kind' => null, 'error' => 'The uploaded file could not be saved. Please try again.'];
    }

    return [
        'path' => 'uploads/testimonials/' . $filename,
        'kind' => $mime === 'video/mp4' ? 'video' : 'image',
        'error' => null,
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf((string) ($_POST['csrf_token'] ?? ''))) {
    $tid    = (int) ($_POST['id'] ?? 0);
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'save') {
        $data = [
            'name'       => clean_text((string) $_POST['name']),
            'role'       => clean_text((string) ($_POST['role'] ?? '')) ?: null,
            'location'   => clean_text((string) ($_POST['location'] ?? '')) ?: null,
            'quote'      => clean_text((string) ($_POST['quote'] ?? '')),
            'rating'     => max(1, min(5, (int) ($_POST['rating'] ?? 5))),
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
            'status'     => ($_POST['status'] ?? '') === 'draft' ? 'draft' : 'published',
            // A longer "patient story" (optional) shows on the Patient stories page.
            'story'         => trim(strip_tags((string) ($_POST['story'] ?? ''))) ?: null,
            'video_url'     => (function (string $url): ?string {
                if (preg_match('~^https://(www\.)?(youtube\.com|youtu\.be|facebook\.com|fb\.watch|tiktok\.com)/~i', $url)
                    || preg_match('~^uploads/testimonials/[a-f0-9]{32}\.mp4$~', $url)) {
                    return $url;
                }
                return null;
            })(trim((string) ($_POST['video_url'] ?? ''))),
            'condition_tag' => array_key_exists($_POST['condition_tag'] ?? '', condition_hubs()) ? (string) $_POST['condition_tag'] : null,
        ];
        $media = store_testimonial_media($_FILES['media'] ?? []);
        if ($media['kind'] === 'image') {
            $data['screenshot'] = $media['path'];
        } elseif ($media['kind'] === 'video') {
            $data['video_url'] = $media['path'];
        }
        // Optional photo of the customer (or a screenshot of their WhatsApp message).
        if (!empty($_FILES['avatar']['name'])) {
            $check = validate_upload($_FILES['avatar'], ['image/jpeg', 'image/png', 'image/webp']);
            if ($check['ok']) {
                $dir = UPLOAD_PATH . 'testimonials/';
                if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
                $file = secure_filename($_FILES['avatar']['name']);
                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $dir . $file)) {
                    $data['avatar'] = 'uploads/testimonials/' . $file;
                }
            }
        } elseif (!empty($_POST['remove_avatar'])) {
            $data['avatar'] = null;
        }
        if ($media['kind'] !== 'image' && !empty($_POST['remove_screenshot'])) {
            $data['screenshot'] = null;
        }
        if ($media['kind'] !== 'video' && !empty($_POST['remove_video'])) {
            $data['video_url'] = null;
        }
        $existing = null;
        if ($tid) {
            $stmt = db()->prepare('SELECT screenshot, video_url FROM testimonials WHERE id = :id');
            $stmt->execute([':id' => $tid]);
            $existing = $stmt->fetch() ?: null;
        }
        if ($media['kind'] !== 'video' && empty($_POST['remove_video'])
            && trim((string) ($_POST['video_url'] ?? '')) === ''
            && str_starts_with((string) ($existing['video_url'] ?? ''), 'uploads/testimonials/')) {
            $data['video_url'] = $existing['video_url'];
        }
        $hasImage = !empty($data['screenshot']) || (!array_key_exists('screenshot', $data) && !empty($existing['screenshot']));
        $hasVideo = !empty($data['video_url']) || (!array_key_exists('video_url', $data) && !empty($existing['video_url']));
        if ($media['error'] !== null) {
            $flash = ['danger', $media['error']];
        } elseif ($data['name'] === '' || ($data['quote'] === '' && !$hasImage && !$hasVideo)) {
            $flash = ['danger', 'Add a name, and a quote or a review image/video.'];
        } elseif ($tid) {
            db_update('testimonials', $tid, $data);
            $flash = ['ok', 'Testimonial updated.'];
        } else {
            db_insert('testimonials', $data);
            $flash = ['ok', 'Testimonial added — it shows on the home page straight away.'];
        }
    } elseif ($action === 'delete' && $tid) {
        db()->prepare('DELETE FROM testimonials WHERE id = :id')->execute([':id' => $tid]);
        $flash = ['ok', 'Testimonial deleted.'];
    }
}

$rows = db()->query('SELECT * FROM testimonials ORDER BY sort_order, id DESC')->fetchAll();
$edit = null;
if (!empty($_GET['edit'])) {
    $stmt = db()->prepare('SELECT * FROM testimonials WHERE id = :id');
    $stmt->execute([':id' => (int) $_GET['edit']]);
    $edit = $stmt->fetch() ?: null;
}
?>

<div class="dash-head">
  <div><h1>Testimonials &amp; patient stories</h1><p>Upload a customer review image or MP4 video, or add a quote. Published reviews appear on the home page and <a class="text-leaf" href="<?= SITE_URL ?>/testimonials.php" target="_blank">Testimonials</a> page. Longer stories and videos also appear on <a class="text-leaf" href="<?= SITE_URL ?>/stories.php" target="_blank">Patient stories</a>.</p></div>
</div>

<?php if ($flash): ?>
  <div class="alert-fc alert-fc--<?= $flash[0] ?>"><i class="fa-solid fa-circle-<?= $flash[0] === 'ok' ? 'check' : 'exclamation' ?>"></i><span><?= e($flash[1]) ?></span></div>
<?php endif; ?>

<div class="row g-3">
  <div class="col-lg-8">
    <?php if (!$rows): ?><div class="card-fc card-fc--pad text-muted-2">No testimonials yet — add the first one on the right.</div><?php endif; ?>
    <div class="row g-3">
    <?php foreach ($rows as $t): ?>
      <div class="col-md-6">
        <div class="card-fc card-fc--pad h-100 d-flex flex-column">
          <div class="d-flex align-items-center gap-2 mb-2">
            <?php if ($t['avatar']): ?><img src="<?= e(product_image($t['avatar'])) ?>" alt="" style="width:40px;height:40px;border-radius:50%;object-fit:cover"><?php endif; ?>
            <div><b><?= e($t['name']) ?></b><div class="text-muted-3" style="font-size:.78rem"><?= e(trim(($t['role'] ?? '') . ' · ' . ($t['location'] ?? ''), ' ·')) ?></div></div>
            <span class="pill pill--<?= $t['status'] === 'published' ? 'ok' : 'muted' ?> ms-auto"><?= $t['status'] === 'published' ? 'Live' : 'Draft' ?></span>
          </div>
          <?= stars_html((float) $t['rating']) ?>
          <?php if (!empty($t['screenshot'])): ?><img src="<?= e(product_image($t['screenshot'])) ?>" alt="" class="mt-2" style="max-height:220px;width:auto;max-width:100%;border-radius:10px;border:1px solid var(--line);align-self:flex-start"><?php endif; ?>
          <?php if (!empty($t['video_url']) && str_starts_with($t['video_url'], 'uploads/testimonials/')): ?><video controls preload="metadata" class="mt-2" style="max-width:100%;border-radius:10px"><source src="<?= e(product_image($t['video_url'])) ?>" type="video/mp4"></video><?php endif; ?>
          <?php if ($t['quote'] !== ''): ?><p style="font-size:.88rem;color:var(--text-2)" class="mt-2">&ldquo;<?= e(excerpt($t['quote'], 180)) ?>&rdquo;</p><?php endif; ?>
          <div class="d-flex gap-1 mt-auto">
            <a class="btn-fc btn-fc--sm btn-fc--light" href="?edit=<?= (int) $t['id'] ?>"><i class="fa-solid fa-pen"></i> Edit</a>
            <form method="post" onsubmit="return confirm('Delete this testimonial?')">
              <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $t['id'] ?>">
              <button class="btn-fc btn-fc--sm btn-fc--light" style="color:var(--coral)" type="submit" aria-label="Delete"><i class="fa-solid fa-trash-can"></i></button>
            </form>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
    </div>
  </div>

  <div class="col-lg-4">
    <form method="post" enctype="multipart/form-data" class="card-fc card-fc--pad">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>">
      <h2 style="font-size:1.05rem"><?= $edit ? 'Edit testimonial' : 'Add a testimonial' ?></h2>

      <div class="field"><label for="name">Customer name</label>
        <input class="input-fc" id="name" name="name" value="<?= e((string) ($edit['name'] ?? '')) ?>" placeholder="e.g. Thandi M." required></div>
      <div class="row g-2">
        <div class="col-6 field"><label for="role">Who they are</label>
          <input class="input-fc" id="role" name="role" value="<?= e((string) ($edit['role'] ?? 'Customer')) ?>"></div>
        <div class="col-6 field"><label for="location">Town</label>
          <input class="input-fc" id="location" name="location" value="<?= e((string) ($edit['location'] ?? '')) ?>" placeholder="Soweto"></div>
      </div>
      <div class="field"><label for="media">Review image or video <span class="text-muted-3 fw-normal">(image up to 8 MB, MP4 up to 50 MB)</span></label>
        <input class="input-fc" type="file" id="media" name="media" accept="image/jpeg,image/png,image/webp,video/mp4">
        <?php if (!empty($edit['screenshot'])): ?>
          <img src="<?= e(product_image($edit['screenshot'])) ?>" alt="" class="mt-2" style="max-height:160px;border-radius:8px;border:1px solid var(--line)">
          <label class="mt-1 d-block" style="font-size:.82rem"><input type="checkbox" name="remove_screenshot" value="1"> Remove screenshot</label>
        <?php endif; ?>
        <?php if (!empty($edit['video_url'])): ?>
          <?php if (str_starts_with($edit['video_url'], 'uploads/testimonials/')): ?>
            <video controls preload="metadata" class="mt-2" style="max-width:100%;border-radius:8px"><source src="<?= e(product_image($edit['video_url'])) ?>" type="video/mp4"></video>
          <?php else: ?>
            <div class="field__hint">Current video link: <a href="<?= e($edit['video_url']) ?>" target="_blank" rel="noopener">Preview video</a></div>
          <?php endif; ?>
          <label class="mt-1 d-block" style="font-size:.82rem"><input type="checkbox" name="remove_video" value="1"> Remove current video</label>
        <?php endif; ?>
        <div class="field__hint">Upload a review image or an MP4 video. Images appear on the home page; videos appear on the testimonials and patient stories pages.</div></div>
      <div class="field"><label for="quote">What they said <span class="text-muted-3 fw-normal">(optional with a screenshot)</span></label>
        <textarea class="textarea-fc" id="quote" name="quote" rows="5"><?= e((string) ($edit['quote'] ?? '')) ?></textarea>
        <div class="field__hint">Only publish real messages, with the customer's permission. Use first name and initial.</div></div>
      <div class="field"><label for="story">Their story <span class="text-muted-3 fw-normal">(optional, longer)</span></label>
        <textarea class="textarea-fc" id="story" name="story" rows="6" placeholder="How they found Phola, what changed, in their words."><?= e((string) ($edit['story'] ?? '')) ?></textarea></div>
      <div class="row g-2">
        <div class="col-7 field"><label for="video_url">Video link <span class="text-muted-3 fw-normal">(YouTube, Facebook or TikTok)</span></label>
          <input class="input-fc" id="video_url" name="video_url" type="url" value="<?= str_starts_with((string) ($edit['video_url'] ?? ''), 'uploads/testimonials/') ? '' : e((string) ($edit['video_url'] ?? '')) ?>" placeholder="https://youtu.be/…"></div>
        <div class="col-5 field"><label for="condition_tag">Condition</label>
          <select class="select-fc" id="condition_tag" name="condition_tag"><option value="">—</option>
            <?php foreach (condition_hubs() as $k => $h): ?><option value="<?= e($k) ?>" <?= ($edit['condition_tag'] ?? '') === $k ? 'selected' : '' ?>><?= e($h['name']) ?></option><?php endforeach; ?></select></div>
      </div>
      <div class="field"><label for="avatar">Photo <span class="text-muted-3 fw-normal">(optional)</span></label>
        <input class="input-fc" type="file" id="avatar" name="avatar" accept="image/jpeg,image/png,image/webp">
        <?php if (!empty($edit['avatar'])): ?><label class="mt-1" style="font-size:.82rem"><input type="checkbox" name="remove_avatar" value="1"> Remove current photo</label><?php endif; ?></div>
      <div class="row g-2">
        <div class="col-4 field"><label for="rating">Stars</label>
          <select class="select-fc" id="rating" name="rating"><?php for ($i = 5; $i >= 1; $i--): ?><option <?= (int) ($edit['rating'] ?? 5) === $i ? 'selected' : '' ?>><?= $i ?></option><?php endfor; ?></select></div>
        <div class="col-4 field"><label for="sort_order">Order</label>
          <input class="input-fc" type="number" id="sort_order" name="sort_order" value="<?= (int) ($edit['sort_order'] ?? 0) ?>"></div>
        <div class="col-4 field"><label for="status">Status</label>
          <select class="select-fc" id="status" name="status">
            <option value="published" <?= ($edit['status'] ?? 'published') === 'published' ? 'selected' : '' ?>>Live</option>
            <option value="draft" <?= ($edit['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option>
          </select></div>
      </div>
      <button class="btn-fc btn-fc--block" type="submit"><?= $edit ? 'Save changes' : 'Add testimonial' ?></button>
      <?php if ($edit): ?><a href="testimonials.php" class="btn-fc btn-fc--light btn-fc--block mt-2">Cancel</a><?php endif; ?>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/dash-foot.php'; ?>
