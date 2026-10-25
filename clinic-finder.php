<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

// Clinic & pharmacy finder: a Google map search around the visitor's location
// (only if they allow it) or a town they type. Nothing is stored.
$cities = ['Johannesburg', 'Pretoria', 'Durban', 'Cape Town', 'Soweto', 'Polokwane', 'Mbombela', 'Bloemfontein', 'Gqeberha', 'East London',
           'Harare', 'Bulawayo', 'Maputo', 'Gaborone', 'Maseru', 'Mbabane', 'Windhoek', 'Lusaka', 'Lilongwe', 'Blantyre'];

$page_title       = 'Find a clinic or pharmacy near you | ' . SITE_NAME;
$meta_description = 'Find clinics, pharmacies and hospitals near you in South Africa, Zimbabwe, Mozambique, Botswana and across SADC, plus South African emergency numbers.';
include __DIR__ . '/includes/header.php';
?>

<header class="page-head">
  <div class="container">
    <nav class="crumbs"><a href="<?= SITE_URL ?>/index.php">Home</a><i class="fa-solid fa-chevron-right"></i><span>Clinic finder</span></nav>
    <h1>Find a clinic or pharmacy near you</h1>
    <p>Search around your location or any town in South Africa and SADC. Your location is only used for the map and is never saved.</p>
  </div>
</header>

<div class="section section--tight">
  <div class="container">
    <div class="alert-fc alert-fc--danger mb-3"><i class="fa-solid fa-truck-medical"></i>
      <span><b>Emergency in South Africa?</b> Ambulance <a href="tel:10177"><b>10177</b></a> · from any cellphone <a href="tel:112"><b>112</b></a>. Outside South Africa, use your country's emergency number.</span></div>

    <div class="card-fc card-fc--pad mb-3">
      <form class="finder" id="finder">
        <div class="field mb-0"><label for="what">Looking for</label>
          <select class="input-fc" id="what"><option value="clinic">Clinic</option><option value="pharmacy">Pharmacy</option><option value="hospital">Hospital</option><option value="HIV testing clinic">HIV testing</option><option value="doctor">Doctor (GP)</option></select></div>
        <div class="field mb-0" style="flex:2"><label for="where">Town or area</label>
          <input class="input-fc" id="where" list="cityList" placeholder="e.g. Soweto, Harare, Gaborone">
          <datalist id="cityList"><?php foreach ($cities as $c): ?><option value="<?= e($c) ?>"></option><?php endforeach; ?></datalist></div>
        <button class="btn-fc" type="submit"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
        <button class="btn-fc btn-fc--ghost" type="button" id="nearMe"><i class="fa-solid fa-location-crosshairs"></i> Near me</button>
      </form>
    </div>

    <div class="finder-map"><iframe id="map" title="Map of nearby clinics" loading="lazy" referrerpolicy="no-referrer-when-downgrade"
      src="https://www.google.com/maps?q=<?= rawurlencode('clinic Johannesburg') ?>&output=embed"></iframe></div>
    <p class="field__hint mt-2">Map results come from Google Maps. Phone the clinic before you go to check opening hours.</p>

    <div class="hub-grid mt-4">
      <a class="hub-tile" href="<?= SITE_URL ?>/ask.php"><i class="fa-solid fa-user-doctor"></i><b>Ask a pharmacist</b><span>A question about supplements or your routine? Ask our team for free.</span></a>
      <a class="hub-tile" href="<?= SITE_URL ?>/health-tools.php"><i class="fa-solid fa-calculator"></i><b>Check your numbers</b><span>BMI, blood sugar and blood pressure calculators.</span></a>
      <a class="hub-tile" href="<?= SITE_URL ?>/events.php"><i class="fa-solid fa-calendar-days"></i><b>Health talks</b><span>Free WhatsApp and online talks with our team.</span></a>
      <a class="hub-tile" href="<?= SITE_URL ?>/condition.php"><i class="fa-solid fa-book-medical"></i><b>Condition guides</b><span>Diabetes, blood pressure, HIV, TB and more.</span></a>
    </div>
  </div>
</div>

<script>
(() => {
  const map = document.getElementById('map');
  const show = (q) => { map.src = 'https://www.google.com/maps?q=' + encodeURIComponent(q) + '&output=embed'; };
  document.getElementById('finder').addEventListener('submit', (e) => {
    e.preventDefault();
    const where = document.getElementById('where').value.trim();
    show(document.getElementById('what').value + (where ? ' near ' + where : ' near me'));
  });
  document.getElementById('nearMe').addEventListener('click', function () {
    if (!navigator.geolocation) { alert('Your browser cannot share your location — type your town instead.'); return; }
    this.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Finding you…';
    navigator.geolocation.getCurrentPosition((p) => {
      show(document.getElementById('what').value + ' near ' + p.coords.latitude.toFixed(4) + ',' + p.coords.longitude.toFixed(4));
      this.innerHTML = '<i class="fa-solid fa-location-crosshairs"></i> Near me';
    }, () => { this.innerHTML = '<i class="fa-solid fa-location-crosshairs"></i> Near me'; alert('Location not shared — type your town instead.'); }, { timeout: 10000 });
  });
})();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
