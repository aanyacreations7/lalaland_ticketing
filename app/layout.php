<?php
declare(strict_types=1);

/**
 * Shared page chrome.
 * @param array{nav?:string, body_class?:string, head?:string, description?:string, og_image?:string} $opt
 *   nav: 'public' (default), 'staff', 'admin' or 'none'
 */
function render_header(string $title, array $opt = []): void
{
    $nav = $opt['nav'] ?? 'public';
    start_session(); // before any output: pages below print CSRF fields
    $accent = accent_color();
    $user = in_array($nav, ['staff', 'admin'], true) ? current_user() : null;
    $eventName = setting('event_name');
    $fullTitle = $title === $eventName || $title === '' ? $eventName : $title . ' · ' . $eventName;
    header('Content-Type: text/html; charset=utf-8');
    ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($fullTitle) ?></title>
<?php if (!empty($opt['description'])): ?>
<meta name="description" content="<?= e($opt['description']) ?>">
<meta property="og:title" content="<?= e($eventName) ?>">
<meta property="og:description" content="<?= e($opt['description']) ?>">
<?php endif; ?>
<?php if (!empty($opt['og_image'])): ?>
<meta property="og:image" content="<?= e($opt['og_image']) ?>">
<?php endif; ?>
<?php if ($nav !== 'public'): ?>
<meta name="robots" content="noindex, nofollow">
<?php endif; ?>
<meta name="theme-color" content="<?= e($accent) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&family=Unbounded:wght@800;900&display=swap" rel="stylesheet">
<?= brand_fonts_css() ?>
<link rel="stylesheet" href="/assets/style.css?v=<?= e(asset_version('style.css')) ?>">
<style>:root{--accent:<?= e($accent) ?>}</style>
<?= $opt['head'] ?? '' ?>
</head>
<body class="<?= e($opt['body_class'] ?? '') ?>">
<?php if ($nav === 'public'): $logo = safe_url(setting('logo_url')) ?: brand_asset('wordmark-sm.webp'); $main = safe_url((string) config('main_site_url')); ?>
<header class="site-header">
  <div class="wrap site-header-in">
    <a class="site-logo" href="<?= e($main ?: '/') ?>">
      <?php if ($logo): ?><img src="<?= e($logo) ?>" alt="<?= e($eventName) ?>" width="180" height="44"><?php else: ?><span><?= e($eventName) ?></span><?php endif; ?>
    </a>
    <nav class="site-nav">
      <?php if ($main): foreach (main_site_links('nav') as [$label, $href]): ?><a href="<?= e($href) ?>"><?= e($label) ?></a><?php endforeach; endif; ?>
      <a class="pill" href="/#book">Buy tickets</a>
    </nav>
  </div>
</header>
<?php endif; ?>
<?php if ($nav === 'staff' || $nav === 'admin'): ?>
<header class="topbar">
  <a class="brand" href="<?= $nav === 'admin' ? '/admin/' : '/scan.php' ?>"><?= e($eventName) ?></a>
  <nav class="topnav">
    <?php if ($nav === 'admin'): ?>
      <a href="/admin/">Dashboard</a>
      <a href="/admin/bookings.php">Bookings</a>
      <a href="/admin/settings.php">Event</a>
      <a href="/admin/staff.php">Staff</a>
    <?php endif; ?>
    <a href="/scan.php">Scanner</a>
    <a href="/boxoffice.php">Box office</a>
    <?php if ($nav === 'staff' && $user && $user['role'] === 'admin'): ?><a href="/admin/">Admin</a><?php endif; ?>
    <?php if ($user): ?><a href="/logout.php" class="muted">Sign out</a><?php endif; ?>
  </nav>
</header>
<?php foreach (take_flashes() as $f): ?>
<div class="flash flash-<?= e($f['t']) ?>"><?= e($f['m']) ?></div>
<?php endforeach; ?>
<?php endif;
}

function render_footer(array $opt = []): void
{
    $main = safe_url((string) config('main_site_url'));
    ?>
<?php if (($opt['nav'] ?? 'public') === 'public'): ?>
<footer class="foot">
  <div class="wrap">
    <div class="foot-top">
      <h2>See you at <span>LaLa Land.</span></h2>
      <?= sticker('charminar', 'st-foot') ?>
    </div>
    <div class="foot-bot">
      <a class="foot-logo" href="<?= e($main ?: '/') ?>"><?php if ($wm = brand_asset('wordmark-sm.webp')): ?><img src="<?= e($wm) ?>" alt="LaLa Land" width="140" height="34"><?php else: ?>LaLa Land<?php endif; ?></a>
      <nav><?php if ($main): foreach (main_site_links('footer') as [$label, $href]): ?><a href="<?= e($href) ?>"><?= e($label) ?></a><?php endforeach; endif; ?></nav>
    </div>
    <p class="foot-legal">© <?= date('Y') ?> <?= e((string) config('organiser_name')) ?> · Tickets powered by Razorpay</p>
  </div>
</footer>
<?php endif; ?>
</body>
</html>
<?php
}

/** Public URL of a bundled brand image, or '' if it isn't there. */
function brand_asset(string $file): string
{
    return is_file(ROOT_DIR . '/assets/brand/' . $file) ? '/assets/brand/' . $file . '?v=' . asset_version('brand/' . $file) : '';
}

/** A decorative brand sticker, or nothing when the file is missing. */
function sticker(string $name, string $class = ''): string
{
    $src = brand_asset('s-' . $name . '.webp');
    return $src ? '<img class="sticker ' . e($class) . '" src="' . e($src) . '" alt="" aria-hidden="true" loading="lazy" decoding="async">' : '';
}

/**
 * @font-face rules for the licensed brand fonts, only when their files have
 * been uploaded to assets/fonts (they are not in the public repository).
 */
function brand_fonts_css(): string
{
    $faces = [
        ['Woodburger', ['woodburger.woff', 'woodburger.ttf', 'VLNL-WoodBurger-W00-Regular.ttf'], '400'],
        ['Antique Olive', ['antique-olive.woff', 'Antique-Olive-Regular.ttf', 'Antique Olive Regular.ttf'], '400'],
        ['Antique Olive', ['antique-olive-bold.woff', 'AntiqueOliveBold.ttf'], '700 900'],
        ['Antique Olive Compact', ['antique-olive-compact.woff', 'AQCT.ttf'], '400'],
    ];
    $css = '';
    foreach ($faces as [$family, $files, $weight]) {
        foreach ($files as $file) {
            if (is_file(ROOT_DIR . '/assets/fonts/' . $file)) {
                $fmt = str_ends_with($file, '.woff') ? 'woff' : 'truetype';
                $url = '/assets/fonts/' . rawurlencode($file);
                $css .= "@font-face{font-family:'$family';src:url('$url') format('$fmt');font-weight:$weight;font-style:normal;font-display:swap}";
                break;
            }
        }
    }
    return $css ? "<style>$css</style>\n" : '';
}

function asset_version(string $file): string
{
    $path = ROOT_DIR . '/assets/' . $file;
    return is_file($path) ? (string) filemtime($path) : '1';
}

/**
 * Hero headline in the main site's style: the two words before the last two
 * get a marker highlight, the last two are outlined. "It's always sunny in
 * LaLa Land" -> It's always <hl>sunny in</hl> <outline>LaLa Land</outline>
 */
function hero_title(string $text): string
{
    $words = preg_split('/\s+/u', trim($text)) ?: [];
    $n = count($words);
    if ($n < 3) {
        return '<span class="outline">' . e(trim($text)) . '</span>';
    }
    $outline = array_slice($words, -2);
    $hl = $n >= 5 ? array_slice($words, -4, 2) : [];
    $plain = array_slice($words, 0, $n - 2 - count($hl));
    $out = $plain ? e(implode(' ', $plain)) . ' ' : '';
    if ($hl) {
        $out .= '<span class="hl">' . e(implode(' ', $hl)) . '</span> ';
    }
    return $out . '<span class="outline">' . e(implode(' ', $outline)) . '</span>';
}

function first_paragraph(string $text): string
{
    $p = trim(preg_split('/\R/u', trim($text))[0] ?? '');
    return mb_strlen($p) > 220 ? mb_substr($p, 0, 217) . '…' : $p;
}

function icon(string $name): string
{
    $paths = [
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="3"/><path d="M3 10h18M8 3v4M16 3v4"/>',
        'pin'      => '<path d="M12 21s-7-6.2-7-11.5A7 7 0 0 1 19 9.5C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>',
    ];
    return '<svg class="ic" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($paths[$name] ?? '') . '</svg>';
}

/** Round "MUSIC ✦ ART ✦ FOOD ✦ SHOPPING" stamp with the event date (dd.mm). */
function date_badge(string $datetime): string
{
    $d = fmt_datetime($datetime, 'd.m');
    return '<div class="badge"><svg viewBox="0 0 200 200" aria-hidden="true"><defs><path id="bp" d="M100,100 m-72,0 a72,72 0 1,1 144,0 a72,72 0 1,1 -144,0"/></defs>'
        . '<circle cx="100" cy="100" r="96" class="b-bg"/><text class="b-ring"><textPath href="#bp" startOffset="0">MUSIC ✦ ART ✦ FOOD ✦ SHOPPING ✦</textPath></text></svg>'
        . '<span class="b-date">' . e($d) . '</span></div>';
}

/** Links back to the main site, matching its own header and footer. */
function main_site_links(string $where): array
{
    $m = rtrim(safe_url((string) config('main_site_url')), '/');
    if ($m === '') {
        return [];
    }
    $nav = [['About', "$m/about-us/"], ['Gallery', "$m/#llf-gallery"], ['Vendors', "$m/vendor-registration/"], ['Contact', "$m/contact/"]];
    if ($where === 'nav') {
        return $nav;
    }
    $s = settings();
    $links = [['About', "$m/about-us/"], ['Vendor registration', "$m/vendor-registration/"], ['Contact us', "$m/contact/"]];
    if ($map = safe_url($s['event_map_link'] ?? '')) {
        $links[] = ['Directions', $map];
    }
    $links[] = ['Instagram', 'https://www.instagram.com/lalalandflea'];
    $links[] = ['Facebook', 'https://www.facebook.com/share/1GSThWzuDL/'];
    return $links;
}
