<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$data   = require __DIR__ . '/includes/content/exercise-plans.php';
$groups = $data['groups'];
$conds  = $data['conditions'];

$page_title       = 'Exercise plans by age — free weekly plans | ' . SITE_NAME;
$meta_description = 'Free weekly exercise plans for children, young adults, adults over 40 and seniors over 65 — home moves with no equipment, plus safe-exercise tips for diabetes, high blood pressure, HIV, TB and arthritis.';
include __DIR__ . '/includes/header.php';
?>

<header class="page-head">
  <div class="container">
    <nav class="crumbs"><a href="<?= SITE_URL ?>/index.php">Home</a><i class="fa-solid fa-chevron-right"></i><span>Exercise plans</span></nav>
    <h1>Exercise plans for every age</h1>
    <p>A simple week of movement for your age group, with moves you can do at home — no gym, just a chair, a wall and two water bottles. Pair it with a <a class="text-leaf fw-bold" href="<?= SITE_URL ?>/diet-plans.php">diet plan</a> for the best results.</p>
  </div>
</header>

<nav class="ph-rail" aria-label="Age groups" style="position:sticky;top:var(--header-h);z-index:5">
  <div class="container ph-rail__row">
    <?php foreach ($groups as $key => $g): ?>
      <a href="#<?= e($key) ?>" class="ph-need"><span style="color:<?= e($g['colour']) ?>"><i class="fa-solid <?= e($g['icon']) ?>"></i></span><b><?= e($g['name']) ?> · <?= e($g['ages']) ?></b></a>
    <?php endforeach; ?>
    <a href="#conditions" class="ph-need"><span><i class="fa-solid fa-notes-medical"></i></span><b>With a health condition</b></a>
  </div>
</nav>

<div class="section">
  <div class="container">

    <form class="age-finder card-fc card-fc--pad mb-4" id="ageFinder">
      <i class="fa-solid fa-cake-candles"></i>
      <label for="age"><b>How old are you?</b> We'll take you to your plan.</label>
      <input class="input-fc" type="number" id="age" min="5" max="110" placeholder="Age" required>
      <button class="btn-fc" type="submit">Show my plan</button>
    </form>

    <div class="alert-fc alert-fc--warn mb-4"><i class="fa-solid fa-triangle-exclamation"></i>
      <span>New to exercise, over 40 with a health condition, or pregnant? Check with your clinic first. <b>Stop straight away</b> if you feel chest pain, severe breathlessness, dizziness or sudden pain — and call 10177 if it doesn't pass.</span></div>

    <?php foreach ($groups as $key => $g): ?>
    <section class="diet ex" id="<?= e($key) ?>" style="--c:<?= e($g['colour']) ?>">
      <div class="diet__head">
        <span class="diet__icon"><i class="fa-solid <?= e($g['icon']) ?>"></i></span>
        <div><h2><?= e($g['name']) ?> <small class="text-muted-3" style="font-size:.6em;font-weight:500"><?= e($g['ages']) ?></small></h2><p><?= e($g['goal']) ?></p></div>
      </div>

      <div class="ex-targets">
        <?php foreach ($g['targets'] as $t): ?><span><i class="fa-solid fa-bullseye"></i><?= e($t) ?></span><?php endforeach; ?>
      </div>

      <div class="ex-grid">
        <div>
          <div class="d-flex justify-content-between align-items-baseline mb-2">
            <h3 class="dv-h3 m-0">This week</h3>
            <span class="ex-count" data-count="<?= e($key) ?>"></span>
          </div>
          <ol class="ex-week">
            <?php foreach ($g['week'] as $day => [$title, $detail]): ?>
            <li>
              <label>
                <input type="checkbox" data-week="<?= e($key) ?>" data-day="<?= e($day) ?>" aria-label="Done: <?= e($day . ' ' . $title) ?>">
                <span class="ex-day"><?= e($day) ?></span>
                <span><b><?= e($title) ?></b><small><?= e($detail) ?></small></span>
              </label>
            </li>
            <?php endforeach; ?>
          </ol>
          <p class="field__hint">Tick off each day as you go — your ticks stay on this device and reset every Monday.</p>
        </div>
        <div>
          <h3 class="dv-h3 mt-0 mb-2">Home moves</h3>
          <div class="ex-moves">
            <?php foreach ($g['moves'] as $i => [$move, $how]): ?>
              <div><span><?= $i + 1 ?></span><b><?= e($move) ?></b><small><?= e($how) ?></small></div>
            <?php endforeach; ?>
          </div>
          <p class="diet__tip"><i class="fa-solid fa-lightbulb"></i> <b>Tip:</b> <?= e($g['tip']) ?></p>
        </div>
      </div>
    </section>
    <?php endforeach; ?>

    <section id="conditions" class="dv-sec mt-4" style="border:0">
      <div class="ph-head"><div><span class="eyebrow">Living with a condition</span><h2>Exercising safely with a health condition</h2>
        <p class="lede mb-0">Movement helps almost every chronic condition — these are the adjustments that keep it safe.</p></div></div>
      <div class="ex-conds">
        <?php foreach ($conds as $c): ?>
        <div class="ex-cond">
          <h3><i class="fa-solid <?= e($c['icon']) ?>"></i> <?= e($c['name']) ?></h3>
          <p><b class="text-leaf">Do:</b> <?= e($c['do']) ?></p>
          <p><b style="color:var(--coral)">Take care:</b> <?= e($c['avoid']) ?></p>
        </div>
        <?php endforeach; ?>
      </div>
    </section>

    <div class="hub-grid mt-4">
      <a class="hub-tile" href="<?= SITE_URL ?>/diet-plans.php"><i class="fa-solid fa-carrot"></i><b>Diet plans</b><span>Eating plans for chronic conditions, with a sample day.</span></a>
      <a class="hub-tile" href="<?= SITE_URL ?>/health-tools.php#bmi"><i class="fa-solid fa-weight-scale"></i><b>Check your BMI</b><span>See where your weight is now, and track it as you go.</span></a>
      <a class="hub-tile" href="<?= SITE_URL ?>/health-tools.php#water"><i class="fa-solid fa-glass-water"></i><b>Water needs</b><span>How much to drink on active days.</span></a>
      <a class="hub-tile" href="<?= SITE_URL ?>/modules/auth/register.php"><i class="fa-solid fa-chart-line"></i><b>Track your progress</b><span>Log weight, blood pressure and sugar in your free tracker.</span></a>
    </div>
  </div>
</div>

<script>
(() => {
  // Which group am I in?
  document.getElementById('ageFinder').addEventListener('submit', (e) => {
    e.preventDefault();
    const a = +document.getElementById('age').value;
    const id = a < 18 ? 'kids' : a < 40 ? 'young' : a < 65 ? 'adults' : 'seniors';
    document.getElementById(id).scrollIntoView({ behavior: 'smooth' });
  });

  // Weekly ticks: kept in this browser only, reset each Monday.
  const monday = (() => { const d = new Date(); d.setHours(0, 0, 0, 0); d.setDate(d.getDate() - ((d.getDay() + 6) % 7)); return d.toISOString().slice(0, 10); })();
  const KEY = 'phola-exercise-' + monday;
  let ticks = {};
  try { ticks = JSON.parse(localStorage.getItem(KEY) || '{}'); } catch (e) {}
  const save = () => { try { localStorage.setItem(KEY, JSON.stringify(ticks)); } catch (e) {} };
  const count = () => document.querySelectorAll('[data-count]').forEach((el) => {
    const n = document.querySelectorAll(`[data-week="${el.dataset.count}"]:checked`).length;
    el.textContent = n ? `${n} of 7 days done${n >= 5 ? ' — brilliant! 🎉' : ''}` : '';
  });
  document.querySelectorAll('[data-week]').forEach((box) => {
    const k = box.dataset.week + ':' + box.dataset.day;
    box.checked = !!ticks[k];
    box.addEventListener('change', () => { ticks[k] = box.checked; save(); count(); });
  });
  count();
})();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
