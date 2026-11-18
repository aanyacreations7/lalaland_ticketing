<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';

/* Printable A4 poster with the gate-sales QR (links to /?from=gate). */

require_role('admin');
$s = settings();
$gateUrl = url('/?from=gate');
[$qrMime, $qrBytes] = qr_image($gateUrl, 16);
$qr = base64_encode($qrBytes);
$price = price_paise('gate');
$accent = accent_color();
header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>Gate poster · <?= e($s['event_name']) ?></title>
<?= brand_fonts_css() ?>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&family=Unbounded:wght@800;900&display=swap" rel="stylesheet">
<style>
  @page { size: A4; margin: 0; }
  * { box-sizing: border-box; }
  :root { --ink:#1e1e1e; --sun:#ffd600; --cream:#fffacf; --accent:<?= e($accent) ?>; --marigold:#ffc01d; --peri:#8585ff;
    --display:'Woodburger','Unbounded','Antique Olive Compact',sans-serif; --body:'Antique Olive','Poppins',sans-serif; }
  html, body { margin: 0; background: #ccc; font-family: var(--body); color: var(--ink); }
  .sp { font-family: var(--body); }
  .sheet { position: relative; overflow: hidden; width: 210mm; height: 297mm; margin: 10mm auto; background: var(--sun); padding: 14mm 14mm 12mm;
    display: flex; flex-direction: column; align-items: center; text-align: center; }
  .wordmark { width: 150mm; height: auto; margin-top: 2mm; }
  .tag { display: inline-block; font: 400 15pt/1 var(--display); text-transform: uppercase; color: #d8261e; background: var(--cream);
    border: .7mm solid var(--ink); padding: 2.5mm 4mm 2mm; transform: rotate(-2deg); box-shadow: 1mm 1mm 0 var(--ink); margin-top: 3mm; }
  .cta { font: 400 40pt/1 var(--display); text-transform: uppercase; color: var(--cream); margin: 7mm 0 6mm;
    text-shadow: 1.2mm 1.2mm 0 var(--ink), -.5mm -.5mm 0 var(--ink), .5mm -.5mm 0 var(--ink), -.5mm .5mm 0 var(--ink), .5mm .5mm 0 var(--ink); }
  .qrbox { background: #fff; border: 1mm solid var(--ink); border-radius: 6mm; padding: 4mm; box-shadow: 2.5mm 2.5mm 0 var(--ink); position: relative; z-index: 2; }
  .qr { display: block; width: 100mm; height: 100mm; image-rendering: pixelated; }
  .steps { display: flex; gap: 6mm; margin-top: 8mm; font-size: 13pt; font-weight: 800; }
  .steps span { display: inline-flex; align-items: center; gap: 2.5mm; background: var(--cream); border: .6mm solid var(--ink); border-radius: 99mm; padding: 1.8mm 4mm 1.8mm 1.8mm; }
  .steps b { display: inline-grid; place-items: center; width: 8mm; height: 8mm; border-radius: 50%; background: var(--accent); color: var(--cream); font-size: 11pt; }
  .price { margin-top: auto; font: 400 22pt var(--display); text-transform: uppercase; }
  .price small { display: block; font: 700 9pt var(--body); text-transform: none; margin-top: 1mm; word-break: break-all; }
  .st { position: absolute; z-index: 1; height: auto; }
  .st1 { width: 44mm; left: -7mm; bottom: -3mm; transform: rotate(-8deg); }
  .st2 { width: 32mm; right: -6mm; top: 14mm; transform: rotate(10deg); }
  .st3 { width: 26mm; right: 2mm; bottom: -2mm; transform: rotate(-6deg); }
  .toolbar { text-align: center; margin: 16px; font-family: Poppins, sans-serif; }
  .toolbar button { font: 700 15px Poppins, sans-serif; padding: 12px 24px; border-radius: 999px; border: 2px solid #1e1e1e; background: #ff502b; color: #fff; cursor: pointer; }
  @media print { html, body { background: #fff; } .sheet { margin: 0; zoom: 1 !important; } .toolbar { display: none; } }
</style>
</head>
<body>
<div class="toolbar">
  <?php if ($s['gate_sales_open'] !== '1'): ?><p style="color:#c0392b;font-weight:600">Gate sales are switched off. Turn them on in Event settings before the event.</p><?php endif; ?>
  <button onclick="window.print()">Print poster</button>
</div>
<div class="sheet">
  <?php if ($wm = brand_asset('wordmark.webp')): ?><img class="wordmark" src="<?= e($wm) ?>" alt="LaLa Land"><?php else: ?><h1 class="cta"><?= display_text($s['event_name']) ?></h1><?php endif; ?>
  <p class="tag"><?= e($s['event_name']) ?> · <?= e(fmt_datetime($s['event_datetime'], 'j M')) ?></p>
  <div class="cta"><?= display_text('Scan to buy tickets') ?></div>
  <div class="qrbox"><img class="qr" src="data:<?= e($qrMime) ?>;base64,<?= $qr ?>" alt="QR code to buy tickets"></div>
  <div class="steps"><span><b>1</b> Scan</span><span><b>2</b> Pay with UPI</span><span><b>3</b> Show QR at entry</span></div>
  <div class="price"><?= e(money($price)) ?> per person<small><?= e($gateUrl) ?></small></div>
  <?php foreach ([['charminar','st1'],['cocktail','st2'],['glass','st3']] as [$n, $c]): if ($src = brand_asset('s-' . $n . '.webp')): ?><img class="st <?= $c ?>" src="<?= e($src) ?>" alt=""><?php endif; endforeach; ?>
</div>
<script>
// On a phone screen, shrink the A4 preview to fit. Printing is unaffected.
(function () {
  var sheet = document.querySelector('.sheet');
  function fit() { sheet.style.zoom = ''; var z = Math.min(1, (document.documentElement.clientWidth - 16) / sheet.offsetWidth); if (z < 1) sheet.style.zoom = z; }
  fit(); window.addEventListener('resize', fit);
})();
</script>
</body>
</html>
