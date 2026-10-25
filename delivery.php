<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$options = delivery_options();
$depots  = require __DIR__ . '/includes/content/sadc-depots.php';
$waAsk   = fn(string $msg) => e(wa_link($msg));

$page_title       = 'Delivery — The Courier Guy, SADC bus depots & DHL | ' . SITE_NAME;
$meta_description = 'How Phola delivers: The Courier Guy door-to-door, Pudo lockers, pick-up points and kiosks in South Africa; cross-border buses and taxis to depots in Zimbabwe, Mozambique, Botswana and the rest of SADC; DHL Express worldwide.';
include __DIR__ . '/includes/header.php';
?>

<header class="page-head">
  <div class="container">
    <nav class="crumbs"><a href="<?= SITE_URL ?>/index.php">Home</a><i class="fa-solid fa-chevron-right"></i><span>Delivery</span></nav>
    <h1>How delivery works</h1>
    <p>Three ways to get your order: The Courier Guy anywhere in South Africa, a cross-border bus or taxi to your depot in SADC, or DHL Express to the rest of the world. Choose one in your basket — we confirm the delivery cost with you on WhatsApp before you pay.</p>
  </div>
</header>

<nav class="ph-rail" aria-label="Delivery methods" style="position:sticky;top:var(--header-h);z-index:5">
  <div class="container ph-rail__row">
    <a href="#courier-guy" class="ph-need"><span><?php if ($logo = delivery_logo('tcg_door')): ?><img src="<?= e($logo) ?>" alt=""><?php else: ?><i class="fa-solid fa-truck-fast"></i><?php endif; ?></span><b>The Courier Guy — South Africa</b></a>
    <a href="#sadc" class="ph-need"><span><i class="fa-solid fa-bus"></i></span><b>SADC bus &amp; taxi depots</b></a>
    <a href="#dhl" class="ph-need"><span><?php if ($logo = delivery_logo('dhl_intl')): ?><img src="<?= e($logo) ?>" alt=""><?php else: ?><i class="fa-solid fa-plane-departure"></i><?php endif; ?></span><b>DHL — worldwide</b></a>
    <a href="#faq" class="ph-need"><span><i class="fa-solid fa-circle-question"></i></span><b>Questions</b></a>
  </div>
</nav>

<div class="section">
  <div class="container">

    <!-- ── The Courier Guy ─────────────────────────────────────────── -->
    <section class="dv-sec" id="courier-guy">
      <div class="ph-head"><div><span class="eyebrow">South Africa</span><h2 class="dv-brand"><?php if ($logo = delivery_logo('tcg_door')): ?><img src="<?= e($logo) ?>" alt="The Courier Guy"><?php endif; ?>The Courier Guy</h2>
        <p class="lede mb-0">We send every South African order from a <b>Pudo smart locker</b> near us, and The Courier Guy delivers it the way you choose: to your <b>door</b>, to a <b>locker</b>, a <b>pick-up point</b> or a <b>kiosk</b> near you.</p></div></div>

      <div class="l2x" aria-label="How a parcel travels">
        <span class="l2x__from"><i class="fa-solid fa-box-archive"></i><b>Phola's locker</b><small>We drop your parcel</small></span>
        <i class="fa-solid fa-arrow-right l2x__arrow"></i>
        <span class="l2x__to">
          <span><i class="fa-solid fa-house-chimney"></i> Your door</span>
          <span><i class="fa-solid fa-box-archive"></i> A locker</span>
          <span><i class="fa-solid fa-store"></i> A pick-up point</span>
          <span><i class="fa-solid fa-shop"></i> A kiosk</span>
        </span>
      </div>

      <div class="deliv-grid">
        <?php foreach ($options as $k => $o): if ($o['region'] !== 'za') continue; ?>
        <div class="deliv-card">
          <i class="fa-solid <?= e($o['icon']) ?>"></i>
          <h3><?= e($o['label']) ?></h3>
          <p><?= e($o['blurb']) ?></p>
          <small><i class="fa-regular fa-clock me-1"></i><?= e($o['time']) ?></small>
        </div>
        <?php endforeach; ?>
      </div>

      <h3 class="dv-h3">How it works</h3>
      <ol class="dv-steps">
        <li><b>Choose your option in the basket</b> — locker to door, locker to locker, locker to pick-up point or locker to kiosk. For a locker, point or kiosk, type its name or code; find the nearest one on the <a class="text-leaf fw-bold" href="https://www.thecourierguy.co.za" target="_blank" rel="noopener">Courier Guy website</a> or the Pudo app.</li>
        <li><b>We confirm on WhatsApp.</b> We check stock, confirm the delivery cost and send payment details.</li>
        <li><b>We pack your order and drop it in our Pudo locker</b> the same or next working day once payment reflects, and send you the <b>waybill / tracking number</b> on WhatsApp.</li>
        <li><b>Track it</b> on thecourierguy.co.za with your waybill number. The Courier Guy also sends updates by SMS or email.</li>
        <li><b>Receive it.</b> Locker to door: the courier hands it over at your address. Locker to locker: you get a <b>PIN or OTP</b> by SMS to open your locker — collect within the time shown. Locker to pick-up point or kiosk: show the SMS and your ID.</li>
      </ol>
      <p class="dv-note"><i class="fa-solid fa-circle-info"></i> Usually 2–4 working days to main centres and up to 5 working days to outlying areas. Make sure your cellphone number is correct — that's where the collection PIN goes.</p>
    </section>

    <!-- ── SADC ────────────────────────────────────────────────────── -->
    <section class="dv-sec" id="sadc">
      <div class="ph-head"><div><span class="eyebrow">Zimbabwe, Mozambique, Botswana &amp; the rest of SADC</span><h2>Cross-border bus &amp; taxi to your depot</h2>
        <p class="lede mb-0">We send your parcel with a trusted long-distance bus or taxi from a South African terminal to the depot nearest you, and you collect it there.</p></div></div>

      <ol class="dv-steps">
        <li><b>Choose "Bus / taxi depot" in the basket</b>, pick your country and type your nearest depot or town.</li>
        <li><b>We quote on WhatsApp</b> — the price depends on the route and the parcel size — and confirm which operator and departure point we will use.</li>
        <li><b>We hand the parcel to the operator</b> and send you a photo of the parcel, the operator's name, the receipt / reference and the expected arrival.</li>
        <li><b>Collect at the depot</b> with your ID (or passport) and the reference we sent. Some operators phone the receiver on arrival.</li>
      </ol>

      <h3 class="dv-h3">Well-known routes from South Africa</h3>
      <div class="depot-grid">
        <?php foreach ($depots as $country => [$flag, $routes]): ?>
        <div class="depot-card">
          <h4><span aria-hidden="true"><?= $flag ?></span> <?= e($country) ?></h4>
          <ul>
            <?php foreach ($routes as [$from, $to]): ?>
              <li><span><?= e($from) ?></span><i class="fa-solid fa-arrow-right"></i><b><?= e($to) ?></b></li>
            <?php endforeach; ?>
          </ul>
        </div>
        <?php endforeach; ?>
      </div>
      <p class="dv-note"><i class="fa-solid fa-circle-info"></i> Your town isn't listed? Most towns on a main road have a bus or taxi stop. Tell us where you are and we'll find the closest route.
        <a class="ph-link" href="<?= $waAsk("Hi Phola, which bus depot is closest to me? I'm in ") ?>" target="_blank" rel="noopener">Ask on WhatsApp <i class="fa-brands fa-whatsapp"></i></a></p>
      <p class="dv-note"><i class="fa-solid fa-passport"></i> Supplements usually cross borders without trouble in personal quantities, but each country has its own customs rules. Any duty charged at the border is paid by the receiver.</p>
    </section>

    <!-- ── DHL ─────────────────────────────────────────────────────── -->
    <section class="dv-sec" id="dhl">
      <div class="ph-head"><div><span class="eyebrow">Rest of the world</span><h2 class="dv-brand"><?php if ($logo = delivery_logo('dhl_intl')): ?><img src="<?= e($logo) ?>" alt="DHL"><?php endif; ?>DHL Express</h2>
        <p class="lede mb-0">For customers abroad — the UK, Europe, the USA, Australia, the rest of Africa — we ship door-to-door with DHL Express, tracked the whole way.</p></div></div>

      <ol class="dv-steps">
        <li><b>Choose "DHL — International" in the basket</b> and enter your country and full address, including the postal code.</li>
        <li><b>We quote on WhatsApp.</b> The DHL rate depends on the country and the parcel weight.</li>
        <li><b>We book DHL</b> after payment and send you the <b>DHL waybill number</b>. Track it at dhl.com or in the DHL app, where you can also choose a delivery day or a DHL Service Point.</li>
        <li><b>Customs.</b> Your country may charge import VAT or duty. DHL contacts you to pay it before delivery — this is paid by the receiver, not included in our quote.</li>
        <li><b>Delivery</b> in about 3–7 working days, depending on the country and customs.</li>
      </ol>
      <p class="dv-note"><i class="fa-solid fa-triangle-exclamation"></i> Some countries limit how many supplements you may import or need a doctor's letter. Please check your country's import rules before ordering; we can't refund parcels held or destroyed by customs.</p>
    </section>

    <!-- ── FAQ ─────────────────────────────────────────────────────── -->
    <section class="dv-sec" id="faq">
      <div class="ph-head"><div><span class="eyebrow">Questions</span><h2>Delivery questions</h2></div></div>
      <div class="dv-faq">
        <details><summary>Why is there no delivery price on the website?</summary><p>The cost depends on where you are, the option you choose and the size of the parcel. We confirm it on WhatsApp with your order, so you always know the full total before you pay.</p></details>
        <details><summary>When will my order leave?</summary><p>The same or next working day after your payment reflects. Orders paid on Friday afternoon or over the weekend leave on Monday.</p></details>
        <details><summary>Can someone else collect for me?</summary><p>Yes. Give us their name and cellphone number when you order. For lockers and kiosks they need the PIN from the SMS; at bus depots they need their ID and the reference.</p></details>
        <details><summary>My parcel arrived damaged.</summary><p>Send us a photo on WhatsApp within 48 hours and we'll replace it.</p></details>
        <details><summary>Do you deliver medication that needs a prescription?</summary><p>No. Phola sells supplements and wellness products only.</p></details>
      </div>
    </section>

    <div class="ph-band mt-4">
      <div>
        <span class="ph-kicker"><i class="fa-solid fa-basket-shopping"></i> Ready?</span>
        <h2>Fill your basket and choose delivery</h2>
        <p>South Africa, SADC or anywhere in the world — we'll get it to you.</p>
      </div>
      <div class="ph-band__cta">
        <a href="<?= SITE_URL ?>/shop.php" class="btn-fc btn-fc--lg ph-btn-lime"><i class="fa-solid fa-basket-shopping"></i> Start shopping</a>
        <a href="<?= $waAsk("Hi Phola, I have a question about delivery.") ?>" target="_blank" rel="noopener" class="btn-fc btn-fc--lg btn-fc--on-dark"><i class="fa-brands fa-whatsapp"></i> Ask us</a>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
