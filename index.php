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
  <section class="hero dots">
    <div class="wrap hero-grid">
      <div class="hero-copy">
        <p class="pill-tag"><?= e($s['event_name']) ?> ✦</p>
        <h1 class="hero-title"><?= hero_title($s['event_tagline'] ?: $s['event_name']) ?></h1>
        <?php if ($lede = first_paragraph($s['event_description'])): ?><p class="lede"><?= e($lede) ?></p><?php endif; ?>
        <div class="chips">
          <span class="chip"><?= icon('calendar') ?><?= e(fmt_datetime($s['event_datetime'], 'j F Y')) ?></span>
          <?php if ($s['event_venue']): ?><span class="chip"><?= icon('pin') ?><?= e($s['event_venue']) ?></span><?php endif; ?>
        </div>
        <div class="ctas">
          <?php if ($salesOpen && !$soldOut): ?><a class="btn btn-tomato" href="#book">Buy tickets →</a><?php endif; ?>
          <?php if ($mapLink): ?><a class="btn btn-white" href="<?= e($mapLink) ?>" target="_blank" rel="noopener">Directions</a><?php endif; ?>
        </div>
      </div>
      <div class="hero-art" aria-hidden="true">
        <?php $photo2 = safe_url($s['photo_2_url']); ?>
        <?php if ($banner): ?><figure class="polaroid p1"><span class="tape"></span><img src="<?= e($banner) ?>" alt="" loading="eager"><figcaption>the city, one weekend</figcaption></figure><?php endif; ?>
        <?php if ($photo2): ?><figure class="polaroid p2"><span class="tape tape-o"></span><img src="<?= e($photo2) ?>" alt=""><figcaption>good vibes only</figcaption></figure><?php endif; ?>
        <?php if (!$banner && !$photo2 && ($logo = brand_asset('logo-sticker.webp'))): ?><img class="hero-logo" src="<?= e($logo) ?>" alt=""><?php endif; ?>
        <?= sticker('charminar', 'st-a') ?>
        <?= sticker('cat', 'st-b') ?>
        <?= date_badge($s['event_datetime']) ?>
      </div>
    </div>
  </section>
  <div class="tapes" aria-hidden="true">
    <div class="tape-row t1"><div><?= str_repeat('SHOP ✺ EAT ✺ DANCE ✺ MAKE MEMORIES ✺ ', 6) ?></div></div>
    <div class="tape-row t2"><div><?= str_repeat('SHOP ✺ EAT ✺ DANCE ✺ MAKE MEMORIES ✺ ', 6) ?></div></div>
  </div>
  <?php else: ?>
  <section class="gate-head dots">
    <div class="wrap">
      <p class="pill-tag">Buy at the gate ✦</p>
      <h1 class="hero-title small"><?= hero_title($s['event_name']) ?></h1>
      <div class="chips"><span class="chip"><?= icon('calendar') ?><?= e(fmt_datetime($s['event_datetime'], 'j F Y')) ?></span></div>
    </div>
  </section>
  <?php endif; ?>

  <div class="event-body dots"><div class="wrap event-grid">
    <?php if (!$isGate): ?>
    <section class="event-info">
      <p class="eyebrow">What is LaLa Land?</p>
      <h2 class="sec-title">Hyderabad’s <span class="hl">happiest</span> flea.</h2>
      <?php $rest = trim((string) (preg_split('/\R/u', trim($s['event_description']), 2)[1] ?? '')); if ($rest !== ''): ?><div class="prose"><?= nl2br(e($rest)) ?></div><?php endif; ?>
      <div class="stats">
        <div class="stat-card s-sun"><strong><?= e(fmt_datetime($s['event_datetime'], 'j M')) ?></strong><span><?= e(fmt_datetime($s['event_datetime'], 'l · g:i A')) ?></span></div>
        <?php if ($s['event_venue']): $vw = preg_split('/[\s,]+/u', trim($s['event_venue']), 2); ?><div class="stat-card s-peri"><strong><?= e($vw[0]) ?></strong><span><?= e(trim($vw[1] ?? '', ' ,')) ?><?php if ($mapLink): ?><br><a href="<?= e($mapLink) ?>" target="_blank" rel="noopener">Get directions →</a><?php endif; ?></span></div><?php endif; ?>
        <div class="stat-card s-cream"><strong><?= e(money($price)) ?></strong><span>per person</span></div>
      </div>
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
          <div class="stub">
            <span class="stub-meta">Admit one · <?= e(fmt_datetime($s['event_datetime'], 'd.m.Y')) ?></span>
            <span class="stub-title">Grab your ticket</span>
            <span class="stub-price"><?= e(money($price)) ?> <small>per person</small></span>
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
  </div></div>

  <?php if (!$isGate && $s['terms']): ?>
  <section class="wrap terms" id="terms">
    <h3>The fine print</h3>
    <div class="prose small"><?= nl2br(e($s['terms'])) ?></div>
  </section>
  <?php endif; ?>
</main>
<?php if ($salesOpen && !$soldOut): ?>
<script src="https://checkout.razorpay.com/v1/checkout.js" defer></script>
<script src="/assets/checkout.js?v=<?= e(asset_version('checkout.js')) ?>" defer></script>
<?php endif; ?>
<?php render_footer();
