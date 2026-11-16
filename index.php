<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';

// Not installed yet? Send the owner to the installer instead of an error.
try {
    q_val('SELECT 1 FROM tk_settings LIMIT 1');
} catch (PDOException) {
    redirect('/install.php');
}

$s = settings();
$isGate = ($_GET['from'] ?? '') === 'gate';
$source = $isGate ? 'gate' : 'online';
$salesOpen = $isGate ? $s['gate_sales_open'] === '1' : $s['sales_open'] === '1';
$left = tickets_left();
$soldOut = $left <= 0;
$price = price_paise($source);
$maxQty = max(0, min(max_per_booking(), $left));
$banner = safe_url($s['banner_url']);
$mapSrc = extract_map_src($s['event_map_embed']);
$mapLink = safe_url($s['event_map_link']);
$extraLabel = trim($s['extra_field_label']);
$extraRequired = $s['extra_field_required'] === '1';
$lowStock = !$soldOut && $left <= max(10, (int) (capacity() * 0.1));

render_header($s['event_name'], [
    'description' => $s['event_tagline'] ?: fmt_datetime($s['event_datetime']) . ($s['event_venue'] ? ' · ' . $s['event_venue'] : ''),
    'og_image'    => $banner ?: (brand_asset('og-image.jpg') ? url(brand_asset('og-image.jpg')) : ''),
    'body_class'  => $isGate ? 'page-event is-gate' : 'page-event',
]);
?>
<main class="event">
  <?php if (!$isGate): ?>
  <?php $wordmark = brand_asset('wordmark.webp'); ?>
  <section class="hero<?= $banner ? ' has-banner' : '' ?>">
    <?php if ($banner): ?><img class="hero-img" src="<?= e($banner) ?>" alt=""><?php endif; ?>
    <?= sticker('charminar', 'st-hero-a') ?>
    <?= sticker('cocktail', 'st-hero-b') ?>
    <?= sticker('glass', 'st-hero-c') ?>
    <div class="hero-text wrap">
      <?php if ($wordmark): ?>
        <img class="hero-wordmark" src="<?= e($wordmark) ?>" alt="LaLa Land" width="1400" height="515" fetchpriority="high">
        <h1 class="tag tag-hero"><?= e($s['event_name']) ?></h1>
      <?php else: ?>
        <h1 class="display"><?= display_text($s['event_name']) ?></h1>
      <?php endif; ?>
      <?php if ($s['event_tagline']): ?><p class="tagline"><?= display_text($s['event_tagline']) ?></p><?php endif; ?>
      <ul class="hero-chips">
        <li><?= e(fmt_datetime($s['event_datetime'], 'D, j M · g:i A')) ?></li>
        <?php if ($s['event_venue']): ?><li><?= e($s['event_venue']) ?></li><?php endif; ?>
      </ul>
      <?php if ($salesOpen && !$soldOut): ?><p class="hero-cta"><a class="btn btn-primary btn-big" href="#book">Get tickets · <?= e(money($price)) ?></a></p><?php endif; ?>
    </div>
  </section>
  <?php else: ?>
  <section class="gate-head">
    <div class="wrap">
      <?php if (brand_asset('wordmark.webp')): ?><img class="gate-wordmark" src="<?= e(brand_asset('wordmark.webp')) ?>" alt="LaLa Land" width="1400" height="515"><?php endif; ?>
      <p class="tag">Buy at the gate</p>
      <h1 class="display small"><?= display_text($s['event_name']) ?></h1>
    </div>
  </section>
  <?php endif; ?>

  <div class="wrap event-grid">
    <?php if (!$isGate): ?>
    <section class="event-info">
      <dl class="facts">
        <div><dt>When</dt><dd><?= e(fmt_datetime($s['event_datetime'])) ?></dd></div>
        <?php if ($s['event_venue']): ?>
        <div><dt>Where</dt><dd><?= e($s['event_venue']) ?><?php if ($mapLink): ?> · <a href="<?= e($mapLink) ?>" target="_blank" rel="noopener">Directions</a><?php endif; ?></dd></div>
        <?php endif; ?>
        <div><dt>Price</dt><dd><?= e(money($price)) ?> <span class="muted">per person</span></dd></div>
      </dl>
      <?php if ($s['event_description']): ?>
      <h2 class="brand-title"><?= display_text("What's on") ?></h2>
      <?= sticker('sunny-hyd', 'st-info') ?>
      <div class="prose"><?= nl2br(e($s['event_description'])) ?></div>
      <?php endif; ?>
      <?php if ($mapSrc): ?>
      <div class="map"><iframe src="<?= e($mapSrc) ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="Venue map" allowfullscreen></iframe></div>
      <?php endif; ?>
    </section>
    <?php endif; ?>

    <aside class="book" id="book">
      <div class="card book-card">
        <?php if (!$salesOpen): ?>
          <h2>Sales closed</h2>
          <p class="muted"><?= $isGate ? 'Gate sales are not open right now. Please ask at the box office.' : 'Ticket sales are not open right now. Please check back soon.' ?></p>
        <?php elseif ($soldOut): ?>
          <h2>Sold out</h2>
          <p class="muted">All tickets have been sold. Seats held by unfinished payments are released after <?= (int) config('hold_minutes', 15) ?> minutes, so check back shortly.</p>
        <?php else: ?>
          <div class="book-head">
            <h2 class="brand-title" style="margin:0"><?= display_text('Tickets') ?></h2>
            <div class="burst"><span><?= e(money($price)) ?></span><small>each</small></div>
          </div>
          <?php if ($lowStock): ?><p class="stock">Only <?= $left ?> left</p><?php endif; ?>

          <form id="order-form" novalidate data-price="<?= $price ?>" data-source="<?= e($source) ?>">
            <?= csrf_field() ?>
            <div class="hp" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

            <label class="field">
              <span>Full name</span>
              <input name="name" required maxlength="120" autocomplete="name">
            </label>
            <label class="field">
              <span>Phone (WhatsApp)</span>
              <input name="phone" required inputmode="tel" autocomplete="tel" placeholder="98765 43210">
            </label>
            <label class="field">
              <span>Email<?= $isGate ? ' <em>(optional)</em>' : '' ?></span>
              <input name="email" type="email" <?= $isGate ? '' : 'required' ?> maxlength="190" autocomplete="email" inputmode="email">
            </label>
            <?php if ($extraLabel !== '' && !$isGate): ?>
            <label class="field">
              <span><?= e($extraLabel) ?><?= $extraRequired ? '' : ' <em>(optional)</em>' ?></span>
              <input name="extra_value" maxlength="255" <?= $extraRequired ? 'required' : '' ?>>
            </label>
            <?php endif; ?>
            <div class="field">
              <span>Tickets</span>
              <div class="stepper" data-max="<?= $maxQty ?>">
                <button type="button" data-step="-1" aria-label="One less">−</button>
                <input name="quantity" type="number" value="1" min="1" max="<?= $maxQty ?>" inputmode="numeric" aria-label="Number of tickets">
                <button type="button" data-step="1" aria-label="One more">+</button>
              </div>
            </div>
            <?php if (!$isGate && $s['terms']): ?>
            <label class="check">
              <input type="checkbox" name="terms" value="1" required>
              <span>I agree to the <a href="#terms">terms</a></span>
            </label>
            <?php else: ?>
            <input type="hidden" name="terms" value="1">
            <?php endif; ?>

            <p class="form-error" role="alert" hidden></p>
            <button class="btn btn-primary btn-block" type="submit">
              <span class="btn-label">Pay <span data-total><?= e(money($price)) ?></span></span>
            </button>
            <p class="fineprint">Secure payment by Razorpay · UPI, cards, netbanking. Your ticket arrives on WhatsApp<?= $isGate ? '' : ' and email' ?>.</p>
          </form>
        <?php endif; ?>
      </div>
    </aside>
  </div>

  <?php if (!$isGate && $s['terms']): ?>
  <section class="wrap terms" id="terms">
    <h3>Terms</h3>
    <div class="prose small"><?= nl2br(e($s['terms'])) ?></div>
  </section>
  <?php endif; ?>
</main>
<?php if ($salesOpen && !$soldOut): ?>
<script src="https://checkout.razorpay.com/v1/checkout.js" defer></script>
<script src="/assets/checkout.js?v=<?= e(asset_version('checkout.js')) ?>" defer></script>
<?php endif; ?>
<?php render_footer();
