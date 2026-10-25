<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$viewer   = current_user();
$items    = cart_items();
$subtotal = cart_subtotal();
$options  = delivery_options();

$depots    = sadc_depot_suggestions();
$waDisplay = Health::displayPhone(whatsapp_number());

// "Add these too": best sellers that aren't in the basket yet.
$inBasket = array_map(fn($i) => (int) $i['product']['id'], $items);
$upsell   = array_slice(array_values(array_filter((new ProductRepository())->getBestsellers(12),
    fn($p) => !in_array((int) $p['id'], $inBasket, true) && $p['stock_status'] !== 'out_of_stock')), 0, 4);

$page_title       = 'Your basket — ' . SITE_NAME;
$meta_description = 'Review your basket, choose The Courier Guy, an SADC bus depot or DHL, and send your order to Phola on WhatsApp.';
$meta_robots      = 'noindex';
include __DIR__ . '/includes/header.php';
?>

<header class="page-head">
  <div class="container">
    <nav class="crumbs"><a href="<?= SITE_URL ?>/index.php">Home</a><i class="fa-solid fa-chevron-right"></i><span>Basket</span></nav>
    <h1>Your basket</h1>
    <p>Choose how you want it delivered, then send your order on WhatsApp. We confirm stock and the delivery cost before you pay.</p>
  </div>
</header>

<div class="section section--tight">
  <div class="container">

    <?php if (!$items): ?>
      <div class="empty card-fc">
        <div class="empty__icon"><i class="fa-solid fa-basket-shopping"></i></div>
        <h2>Your basket is empty</h2>
        <p class="mb-4">Tap <i class="fa-solid fa-plus"></i> on any product to add it here.</p>
        <a href="<?= SITE_URL ?>/shop.php" class="btn-fc btn-fc--lg">Browse products</a>
      </div>

    <?php else: ?>
      <div class="cart-layout">
        <div>
          <!-- ── Lines ───────────────────────────────────────────── -->
          <div class="card-fc" style="padding:0 1.4rem">
            <?php foreach ($items as $item):
              $p = $item['product']; ?>
            <div class="cart-line">
              <a class="cart-line__img" href="<?= SITE_URL ?>/modules/shop/product.php?slug=<?= e($p['slug']) ?>">
                <img src="<?= e(product_image($item['image'] ?: ($p['header_image'] ?? null))) ?>" alt="<?= e($p['name']) ?>"
                     onerror="this.onerror=null;this.src='<?= SITE_URL ?>/assets/img/placeholder.svg'">
              </a>
              <div>
                <a href="<?= SITE_URL ?>/modules/shop/product.php?slug=<?= e($p['slug']) ?>"><div class="cart-line__name"><?= e($p['name']) ?></div></a>
                <div class="cart-line__meta">
                  <?= e((string) $p['brand_name']) ?>
                  <?php if ($item['variant_text']): ?> · <?= e($item['variant_text']) ?><?php endif; ?>
                  <?php if ($p['pack_size']): ?> · <?= e($p['pack_size']) ?><?php endif; ?>
                </div>
                <div class="d-flex align-items-center gap-3 flex-wrap">
                  <div class="qty">
                    <button type="button" data-step="down" aria-label="Decrease"><i class="fa-solid fa-minus"></i></button>
                    <input type="number" value="<?= (int) $item['qty'] ?>" min="1" max="999"
                           data-cart-qty="<?= e($item['key']) ?>" aria-label="Quantity for <?= e($p['name']) ?>">
                    <button type="button" data-step="up" aria-label="Increase"><i class="fa-solid fa-plus"></i></button>
                  </div>
                  <span class="text-muted-3" style="font-size:.82rem"><?= format_price($item['unit_price']) ?> each</span>
                  <button class="btn-fc btn-fc--sm" type="button" data-cart-remove="<?= e($item['key']) ?>"
                          style="background:none;color:var(--text-3);padding:.3rem .5rem">
                    <i class="fa-solid fa-trash-can"></i> Remove
                  </button>
                </div>
              </div>
              <div class="cart-line__right"><span class="money" style="font-size:1.1rem"><?= format_price($item['line_total']) ?></span></div>
            </div>
            <?php endforeach; ?>
          </div>

          <!-- ── Delivery ────────────────────────────────────────── -->
          <form class="card-fc card-fc--pad mt-3" method="post" action="<?= SITE_URL ?>/enquire.php" target="_blank" id="orderForm">
            <?= csrf_field() ?>
            <h2 style="font-size:1.15rem"><i class="fa-solid fa-truck-fast text-leaf me-2"></i>How should we deliver?</h2>

            <div class="deliv-pick">
              <?php $first = true; foreach ($options as $key => $o): ?>
              <label class="deliv-opt">
                <input type="radio" name="delivery" value="<?= e($key) ?>" <?= $first ? 'checked' : '' ?>
                       data-field="<?= e($o['field']) ?>" data-region="<?= e($o['region']) ?>">
                <span class="deliv-opt__box">
                  <i class="fa-solid <?= e($o['icon']) ?>"></i>
                  <b><?= e($o['label']) ?></b>
                  <small><?= e($o['by']) ?></small>
                  <em><?= e($o['time']) ?></em>
                </span>
              </label>
              <?php $first = false; endforeach; ?>
            </div>

            <div class="row g-2 mt-1">
              <div class="col-md-5 field" id="countryField" hidden>
                <label for="country">Country</label>
                <select class="input-fc" id="country" name="country">
                  <?php foreach (SADC_COUNTRIES as $c): ?><option><?= e($c) ?></option><?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-5 field" id="countryIntlField" hidden>
                <label for="country_intl">Country</label>
                <input class="input-fc" id="country_intl" name="country_intl" maxlength="60" placeholder="e.g. United Kingdom" autocomplete="country-name">
              </div>
              <div class="col field">
                <label for="address" id="addressLabel"><?= e(reset($options)['field']) ?></label>
                <input class="input-fc" id="address" name="address" required maxlength="250" autocomplete="street-address" list="">
                <datalist id="depotList">
                  <?php foreach ($depots as $to => $country): ?><option value="<?= e($to) ?>" data-country="<?= e($country) ?>"></option><?php endforeach; ?>
                </datalist>
                <div class="field__hint" id="addressHint"></div>
              </div>
            </div>

            <div class="row g-2">
              <div class="col-md-6 field">
                <label for="enqName">Your name</label>
                <input class="input-fc" id="enqName" name="name" value="<?= e((string) ($viewer['name'] ?? '')) ?>" autocomplete="name" required>
              </div>
              <div class="col-md-6 field">
                <label for="enqPhone">Cellphone</label>
                <input class="input-fc" id="enqPhone" name="phone" type="tel" inputmode="tel" placeholder="082 123 4567"
                       value="<?= e($viewer ? Health::displayPhone($viewer['phone'] ?? '') : '') ?>" autocomplete="tel" required>
              </div>
            </div>
          </form>
        </div>

        <!-- ── Summary ───────────────────────────────────────────── -->
        <div class="summary">
          <div class="card-fc card-fc--pad">
            <h2 style="font-size:1.15rem">Order summary</h2>
            <div class="summary-row"><span><?= cart_count() ?> item<?= cart_count() === 1 ? '' : 's' ?></span><b><?= format_price($subtotal) ?></b></div>
            <div class="summary-row"><span>Delivery</span><b id="sumShip">Confirmed on WhatsApp</b></div>
            <div class="summary-total"><span>Products total</span><b class="money"><?= format_price($subtotal) ?></b></div>
            <p class="field__hint">We confirm the delivery cost with your order before you pay. <a class="text-leaf fw-bold" href="<?= SITE_URL ?>/delivery.php" target="_blank">How delivery works</a></p>

            <button class="btn-fc btn-fc--whatsapp btn-fc--block btn-fc--lg" type="submit" form="orderForm">
              <i class="fa-brands fa-whatsapp"></i> Place order on WhatsApp
            </button>
            <a href="<?= SITE_URL ?>/shop.php" class="btn-fc btn-fc--light btn-fc--block mt-2">Keep shopping</a>
            <ul class="trust-mini">
              <li><i class="fa-solid fa-lock"></i> No card details on the website</li>
              <li><i class="fa-solid fa-user-check"></i> A real person confirms every order</li>
              <li><i class="fa-solid fa-rotate-left"></i> 7-day returns on unopened items</li>
            </ul>
          </div>
        </div>
      </div>

      <?php if ($upsell): ?>
      <section class="mt-5">
        <div class="ph-head"><div><span class="eyebrow">Often added</span><h2 style="font-size:1.5rem">Add these too</h2></div></div>
        <div class="grid-products">
          <?php foreach ($upsell as $product): include __DIR__ . '/includes/product-card.php'; endforeach; ?>
        </div>
      </section>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>

<?php if ($items): ?>
<script>
(() => {
  const form = document.getElementById('orderForm');
  const $ = (id) => document.getElementById(id);
  const hints = {
    za: 'Find your nearest locker, pick-up point or kiosk on thecourierguy.co.za or the Pudo app.',
    sadc: 'Start typing to pick a well-known depot, e.g. Harare Roadport or Gaborone main bus rank.',
    intl: 'Include the postal code. Import VAT or duty in your country is paid to DHL by the receiver.',
  };
  const update = () => {
    const opt = form.querySelector('input[name="delivery"]:checked');
    const region = opt.dataset.region;
    $('addressLabel').textContent = opt.dataset.field;
    $('countryField').hidden = region !== 'sadc';
    $('countryIntlField').hidden = region !== 'intl';
    $('country_intl').required = region === 'intl';
    $('address').setAttribute('list', region === 'sadc' ? 'depotList' : '');
    $('addressHint').textContent = opt.value === 'tcg_door' ? '' : hints[region];
    $('sumShip').textContent = { za: 'The Courier Guy — confirmed on WhatsApp', sadc: 'Bus / taxi — quoted on WhatsApp', intl: 'DHL Express — quoted on WhatsApp' }[region];
  };
  // Picking a suggested depot also picks its country.
  $('address').addEventListener('change', () => {
    const o = [...$('depotList').options].find((x) => x.value === $('address').value);
    if (o) $('country').value = o.dataset.country;
  });
  form.addEventListener('change', update);
  update();
  // The basket empties once the order is sent; refresh so this tab doesn't look unsent.
  form.addEventListener('submit', () => setTimeout(() => location.reload(), 1500));
})();
</script>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
