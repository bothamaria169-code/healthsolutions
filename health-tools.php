<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

// Free health calculators. Everything is worked out in the browser — nothing a
// visitor types here is sent to the server or stored.
$tools = [
    'bmi'     => ['BMI calculator', 'fa-weight-scale', 'Find out if your weight is in the healthy range for your height.'],
    'sugar'   => ['Blood sugar checker', 'fa-droplet', 'Convert mmol/L and mg/dL and see what your reading means.'],
    'bp'      => ['Blood pressure checker', 'fa-heart-pulse', 'See which range your blood pressure reading falls in.'],
    'water'   => ['Daily water needs', 'fa-glass-water', 'How much water your body needs each day.'],
    'pack'    => ['How long will my pack last?', 'fa-calendar-check', 'Work out when your tablets or capsules run out.'],
];

$page_title       = 'Free health calculators — BMI, blood sugar, blood pressure | ' . SITE_NAME;
$meta_description = 'Free online health calculators: BMI, blood sugar mmol/L to mg/dL converter, blood pressure checker, daily water needs and a pill-pack run-out date calculator.';
$json_ld = ['@type' => 'WebPage', 'name' => 'Free health calculators', 'url' => SITE_URL . '/health-tools.php'];
include __DIR__ . '/includes/header.php';
?>

<header class="page-head">
  <div class="container">
    <nav class="crumbs"><a href="<?= SITE_URL ?>/index.php">Home</a><i class="fa-solid fa-chevron-right"></i><span>Health tools</span></nav>
    <h1>Free health calculators</h1>
    <p>Quick checks you can do at home. Nothing you type here is saved or sent anywhere — and none of it replaces a check-up at your clinic.</p>
  </div>
</header>

<nav class="ph-rail" aria-label="Calculators" style="position:sticky;top:var(--header-h);z-index:5">
  <div class="container ph-rail__row">
    <?php foreach ($tools as $id => [$name, $icon]): ?>
      <a href="#<?= $id ?>" class="ph-need"><span><i class="fa-solid <?= $icon ?>"></i></span><b><?= e($name) ?></b></a>
    <?php endforeach; ?>
  </div>
</nav>

<div class="section">
  <div class="container tools">

    <!-- BMI -->
    <section class="tool" id="bmi">
      <div class="tool__intro"><span class="tool__icon"><i class="fa-solid fa-weight-scale"></i></span>
        <div><h2>BMI calculator</h2><p>Body Mass Index compares your weight with your height. It is a quick screening number — muscle, age and body shape also matter.</p></div></div>
      <form class="tool__form" data-tool="bmi">
        <div class="field"><label>Weight (kg)</label><input class="input-fc" type="number" name="kg" min="20" max="300" step="0.1" required placeholder="72"></div>
        <div class="field"><label>Height (cm)</label><input class="input-fc" type="number" name="cm" min="100" max="230" step="0.5" required placeholder="165"></div>
        <button class="btn-fc" type="submit">Calculate</button>
      </form>
      <div class="tool__out" aria-live="polite"></div>
      <p class="tool__ref">Adults: under 18.5 underweight · 18.5–24.9 healthy · 25–29.9 overweight · 30 and over obese.</p>
    </section>

    <!-- Blood sugar -->
    <section class="tool" id="sugar">
      <div class="tool__intro"><span class="tool__icon"><i class="fa-solid fa-droplet"></i></span>
        <div><h2>Blood sugar checker &amp; converter</h2><p>South African meters show mmol/L; many overseas leaflets use mg/dL. Enter either and choose when you measured.</p></div></div>
      <form class="tool__form" data-tool="sugar">
        <div class="field"><label>Reading</label><input class="input-fc" type="number" name="val" min="1" max="700" step="0.1" required placeholder="6.2"></div>
        <div class="field"><label>Unit</label><select class="input-fc" name="unit"><option value="mmol">mmol/L</option><option value="mg">mg/dL</option></select></div>
        <div class="field"><label>When</label><select class="input-fc" name="when"><option value="fasting">Fasting (before breakfast)</option><option value="after">2 hours after a meal</option><option value="random">Any time</option></select></div>
        <button class="btn-fc" type="submit">Check</button>
      </form>
      <div class="tool__out" aria-live="polite"></div>
      <p class="tool__ref">Fasting: 4.0–5.5 normal · 5.6–6.9 raised (pre-diabetes range) · 7.0 or more diabetes range. Below 4.0 is low — eat or drink something sugary now.</p>
    </section>

    <!-- Blood pressure -->
    <section class="tool" id="bp">
      <div class="tool__intro"><span class="tool__icon"><i class="fa-solid fa-heart-pulse"></i></span>
        <div><h2>Blood pressure checker</h2><p>Sit quietly for five minutes, arm supported at heart height, then enter the top (systolic) and bottom (diastolic) numbers.</p></div></div>
      <form class="tool__form" data-tool="bp">
        <div class="field"><label>Top number (systolic)</label><input class="input-fc" type="number" name="sys" min="60" max="260" required placeholder="128"></div>
        <div class="field"><label>Bottom number (diastolic)</label><input class="input-fc" type="number" name="dia" min="30" max="160" required placeholder="82"></div>
        <button class="btn-fc" type="submit">Check</button>
      </form>
      <div class="tool__out" aria-live="polite"></div>
      <p class="tool__ref">Below 120/80 optimal · up to 139/89 normal to high-normal · 140/90 or more high · 180/110 or more very high — see a clinic today.</p>
    </section>

    <!-- Water -->
    <section class="tool" id="water">
      <div class="tool__intro"><span class="tool__icon"><i class="fa-solid fa-glass-water"></i></span>
        <div><h2>Daily water needs</h2><p>A starting point based on your weight and how active you are. Hot days and exercise mean more. People with kidney or heart failure should follow their clinic's fluid limit.</p></div></div>
      <form class="tool__form" data-tool="water">
        <div class="field"><label>Weight (kg)</label><input class="input-fc" type="number" name="kg" min="20" max="300" required placeholder="70"></div>
        <div class="field"><label>Activity</label><select class="input-fc" name="act"><option value="0">Mostly sitting</option><option value="350">Active (walking, standing work)</option><option value="700">Very active / hot weather</option></select></div>
        <button class="btn-fc" type="submit">Calculate</button>
      </form>
      <div class="tool__out" aria-live="polite"></div>
    </section>

    <!-- Pack -->
    <section class="tool" id="pack">
      <div class="tool__intro"><span class="tool__icon"><i class="fa-solid fa-calendar-check"></i></span>
        <div><h2>How long will my pack last?</h2><p>Know your refill date before you run out — then let the free Phola tracker remind you.</p></div></div>
      <form class="tool__form" data-tool="pack">
        <div class="field"><label>Tablets / capsules in the pack</label><input class="input-fc" type="number" name="units" min="1" max="2000" required placeholder="60"></div>
        <div class="field"><label>Per dose</label><input class="input-fc" type="number" name="per" min="0.25" step="0.25" value="1" required></div>
        <div class="field"><label>Doses a day</label><input class="input-fc" type="number" name="times" min="1" max="8" value="1" required></div>
        <div class="field"><label>Starting on</label><input class="input-fc" type="date" name="start" value="<?= date('Y-m-d') ?>" required></div>
        <button class="btn-fc" type="submit">Calculate</button>
      </form>
      <div class="tool__out" aria-live="polite"></div>
    </section>

    <div class="ph-band">
      <div>
        <span class="ph-kicker"><i class="fa-solid fa-chart-line"></i> Free account</span>
        <h2>Keep your numbers in one place</h2>
        <p>Log your blood pressure, sugar and weight in the free Phola tracker and see your progress on a chart — plus dose reminders and a daily wellness quote.</p>
      </div>
      <div class="ph-band__cta"><a href="<?= SITE_URL ?>/modules/auth/register.php" class="btn-fc btn-fc--lg ph-btn-lime">Create my free account</a></div>
    </div>
  </div>
</div>

<script>
(() => {
  const SITE = <?= json_encode(SITE_URL) ?>;
  const link = (href, text) => `<a class="ph-link" href="${SITE}/${href}">${text} <i class="fa-solid fa-arrow-right"></i></a>`;
  const box = (tone, big, text, links = '') =>
    `<div class="tool__res tool__res--${tone}"><b>${big}</b><p>${text}</p>${links ? `<div class="tool__links">${links}</div>` : ''}</div>`;
  const fmtDate = (d) => d.toLocaleDateString('en-ZA', { weekday: 'short', day: 'numeric', month: 'long', year: 'numeric' });

  const tools = {
    bmi(f) {
      const kg = +f.kg.value, m = +f.cm.value / 100, bmi = kg / (m * m), v = bmi.toFixed(1);
      const lo = (18.5 * m * m).toFixed(0), hi = (24.9 * m * m).toFixed(0);
      const range = `A healthy weight for your height is about <b>${lo}–${hi} kg</b>.`;
      if (bmi < 18.5) return box('warn', `BMI ${v} — underweight`, `${range} Speak to your clinic if you are losing weight without trying.`, link('diet-plans.php#tb', 'Eating to regain weight'));
      if (bmi < 25)   return box('ok', `BMI ${v} — healthy`, `${range} Keep it up with regular movement and balanced meals.`);
      if (bmi < 30)   return box('warn', `BMI ${v} — overweight`, `${range} Losing even 5% of your weight lowers blood pressure and sugar.`, link('shop.php?category=weight-management', 'Weight management') + link('diet-plans.php#diabetes', 'Diet plan'));
      return box('bad', `BMI ${v} — obese range`, `${range} Ask your clinic to check your blood pressure and sugar.`, link('shop.php?category=weight-management', 'Weight management') + link('diet-plans.php#heart', 'Heart-healthy eating'));
    },
    sugar(f) {
      let v = +f.val.value; const unit = f.unit.value, when = f.when.value;
      const mmol = unit === 'mg' ? v / 18 : v, mg = unit === 'mg' ? v : v * 18;
      const both = `${mmol.toFixed(1)} mmol/L = ${Math.round(mg)} mg/dL`;
      const plan = link('diet-plans.php#diabetes', 'Diabetes diet plan') + link('shop.php?category=blood-sugar-support', 'Blood sugar support');
      if (mmol < 4) return box('bad', `${both} — low`, 'Take something sugary now (half a glass of juice or 3 sweets), re-check in 15 minutes and get help if you feel confused or faint.');
      if (when === 'fasting') {
        if (mmol < 5.6) return box('ok', `${both} — normal fasting`, 'Your fasting sugar is in the healthy range.');
        if (mmol < 7)   return box('warn', `${both} — raised`, 'This is in the pre-diabetes range. Ask your clinic for an HbA1c test; diet and exercise can bring it back down.', plan);
        return box('bad', `${both} — diabetes range`, 'A fasting reading of 7.0 or more on two different days means diabetes. If you are not diagnosed, see a clinic soon; if you are, tell your clinic about high readings.', plan);
      }
      if (mmol < 7.8)  return box('ok', `${both} — normal`, 'In the normal range after eating.');
      if (mmol < 11.1) return box('warn', `${both} — raised`, 'Higher than normal after a meal. Check your fasting sugar and talk to your clinic.', plan);
      return box('bad', `${both} — high`, 'A reading of 11.1 or more is in the diabetes range. Above 15 with thirst, vomiting or confusion needs a clinic today.', plan);
    },
    bp(f) {
      const s = +f.sys.value, d = +f.dia.value, r = `${s}/${d} mmHg`;
      const help = link('diet-plans.php#hypertension', 'Blood pressure diet plan') + link('shop.php?category=heart-circulation', 'Heart & circulation');
      if (s >= 180 || d >= 110) return box('bad', `${r} — very high`, 'Rest for 5 minutes and measure again. If it is still this high — or you have a headache, chest pain or weakness — go to a clinic or call 10177 now.');
      if (s >= 140 || d >= 90)  return box('bad', `${r} — high`, 'High blood pressure. Re-check on a few different days and see your clinic; if you take tablets, take them every day.', help);
      if (s >= 120 || d >= 80)  return box('warn', `${r} — normal to high-normal`, 'Fine for now, but keep an eye on it: less salt and more movement keep it from climbing.', help);
      if (s < 90 || d < 60)     return box('warn', `${r} — low`, 'Low readings are often harmless, but tell your clinic if you feel dizzy or faint.');
      return box('ok', `${r} — optimal`, 'Your blood pressure is in the ideal range.');
    },
    water(f) {
      const ml = +f.kg.value * 33 + +f.act.value, l = (ml / 1000).toFixed(1), glasses = Math.round(ml / 250);
      return box('ok', `About ${l} litres a day`, `That is roughly <b>${glasses} glasses</b> of 250 ml, including tea, rooibos and water in food. Drink more on hot days.`);
    },
    pack(f) {
      const units = +f.units.value, perDay = +f.per.value * +f.times.value, days = Math.floor(units / perDay);
      const end = new Date(f.start.value + 'T00:00:00'); end.setDate(end.getDate() + days - 1);
      const refill = new Date(end); refill.setDate(refill.getDate() - 5);
      return box('ok', `Lasts ${days} days — until ${fmtDate(end)}`, `Reorder by <b>${fmtDate(refill)}</b> so you never miss a day.`,
        link('modules/auth/register.php', 'Get refill reminders free') + link('shop.php', 'Reorder from Phola'));
    },
  };

  document.querySelectorAll('[data-tool]').forEach((form) => form.addEventListener('submit', (e) => {
    e.preventDefault();
    const out = form.parentElement.querySelector('.tool__out');
    out.innerHTML = tools[form.dataset.tool](form.elements);
    if (window.gtag) gtag('event', 'use_calculator', { tool: form.dataset.tool });
  }));
})();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
