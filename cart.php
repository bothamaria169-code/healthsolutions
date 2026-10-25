<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$viewer   = current_user();
$items    = cart_items();
$subtotal = cart_subtotal();
$options  = delivery_options();

$freeAt    = (float) get_setting('free_shipping_threshold', '1000');
$toFree    = max(0.0, $freeAt - $subtotal);
$freePct   = $freeAt > 0 ? min(100, (int) round($subtotal / $freeAt * 100)) : 100;
$waDisplay = Health::displayPhone(whatsapp_number());

// "Add these too": best sellers that aren't in the basket yet.
$inBasket = array_map(fn($i) => (int) $i['product']['id'], $items);
$upsell   = array_slice(array_values(array_filter((new ProductRepository())->getBestsellers(12),
    fn($p) => !in_array((int) $p['id'], $inBasket, true) && $p['stock_status'] !== 'out_of_stock')), 0, 4);

$page_title       = 'Your basket — ' . SITE_NAME;
$meta_description = 'Review your basket, choose Courier Guy or SADC depot delivery and send your order to Phola on WhatsApp.';
$meta_robots      = 'noindex';
include __DIR__ . '/includes/header.php';
?>

<header class="page-head">
  <div class="container">
    <nav class="crumbs"><a href="<?= SITE_URL ?>/index.php">Home</a><i class="fa-solid fa-chevron-right"></i><span>Basket</span></nav>
    <h1>Your basket</h1>
    <p>Choose how you want it delivered, then send your order on WhatsApp. We confirm stock and the total before you pay.</p>
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
      <div class="free-bar card-fc mb-3">
        <?php if ($toFree > 0): ?>
          <span><i class="fa-solid fa-truck-fast"></i> Add <b class="money"><?= format_price_short($toFree) ?></b> more for <b>free Courier Guy delivery</b></span>
        <?php else: ?>
          <span><i class="fa-solid fa-gift"></i> <b>You've unlocked free delivery</b> anywhere in South Africa</span>
        <?php endif; ?>
        <div class="free-bar__track"><span style="width:<?= $freePct ?>%"></span></div>
      </div>

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
              <?php $first = true; foreach ($options as $key => $o):
                $fee = delivery_fee($key, $subtotal); ?>
              <label class="deliv-opt">
                <input type="radio" name="delivery" value="<?= e($key) ?>" <?= $first ? 'checked' : '' ?>
                       data-fee="<?= $fee === null ? '' : $fee ?>" data-field="<?= e($o['field']) ?>" data-region="<?= e($o['region']) ?>">
                <span class="deliv-opt__box">
                  <i class="fa-solid <?= e($o['icon']) ?>"></i>
                  <b><?= e($o['label']) ?></b>
                  <small><?= e($o['by']) ?></small>
                  <em><?= $fee === null ? 'Quoted' : ($fee > 0 ? format_price_short($fee) : 'Free') ?></em>
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
              <div class="col field">
                <label for="address" id="addressLabel"><?= e(reset($options)['field']) ?></label>
                <input class="input-fc" id="address" name="address" required maxlength="250" autocomplete="street-address">
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
            <div class="summary-row"><span>Delivery</span><b id="sumShip">—</b></div>
            <div class="summary-total"><span>Total</span><b class="money" id="sumTotal"><?= format_price($subtotal) ?></b></div>
            <p class="field__hint" id="sumNote"></p>

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
        <div class="ph-head"><div><span class="eyebrow">Often added</span><h2 style="font-size:1.5rem">Add these too<?= $toFree > 0 ? ' — and get free delivery' : '' ?></h2></div></div>
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
  const subtotal = <?= json_encode(round($subtotal, 2)) ?>;
  // Same shape as format_price() in PHP: R1 250.00
  const fmt = (n) => 'R' + n.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
  const update = () => {
    const opt = form.querySelector('input[name="delivery"]:checked');
    const quoted = opt.dataset.fee === '';
    const fee = quoted ? 0 : parseFloat(opt.dataset.fee);
    document.getElementById('addressLabel').textContent = opt.dataset.field;
    document.getElementById('countryField').hidden = opt.dataset.region !== 'sadc';
    document.getElementById('sumShip').textContent = quoted ? 'Quoted on WhatsApp' : (fee > 0 ? fmt(fee) : 'Free');
    document.getElementById('sumTotal').textContent = fmt(subtotal + fee) + (quoted ? ' + transport' : '');
    document.getElementById('sumNote').textContent = quoted ? <?= json_encode((string) get_setting('sadc_fee_note', 'Quoted on WhatsApp')) ?> : '';
  };
  form.addEventListener('change', update);
  update();
  // The basket empties once the order is sent; refresh so this tab doesn't look unsent.
  form.addEventListener('submit', () => setTimeout(() => location.reload(), 1500));
})();
</script>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
