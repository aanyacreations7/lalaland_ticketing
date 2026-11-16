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
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&family=Archivo+Black&display=swap" rel="stylesheet">
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
    <a class="header-cta" href="/#book">Get tickets</a>
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
<footer class="site-footer">
  <div class="wrap site-footer-in">
    <span>© <?= date('Y') ?> <?= e((string) config('organiser_name')) ?> · It’s always sunny in LaLa Land</span>
    <?php if ($main): ?><a href="<?= e($main) ?>">Back to website →</a><?php endif; ?>
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
        ['Antique Olive Compact', 'antique-olive-compact.woff', 400],
        ['Antique Olive', 'antique-olive.woff', 400],
        ['Antique Olive', 'antique-olive-bold.woff', 700],
    ];
    $css = '';
    foreach ($faces as [$family, $file, $weight]) {
        if (is_file(ROOT_DIR . '/assets/fonts/' . $file)) {
            $css .= "@font-face{font-family:'$family';src:url('/assets/fonts/$file') format('woff');font-weight:$weight;font-style:normal;font-display:swap}";
        }
    }
    return $css ? "<style>$css</style>\n" : '';
}

function asset_version(string $file): string
{
    $path = ROOT_DIR . '/assets/' . $file;
    return is_file($path) ? (string) filemtime($path) : '1';
}
