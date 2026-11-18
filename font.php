<?php
declare(strict_types=1);
/*
 * Serves the main site's brand fonts from this site's own origin.
 *
 * Browsers block fonts loaded from another domain (CORS), so the ticket site
 * can't point at lalalandflea.com directly. The first request downloads the
 * font from the main site (server to server, no CORS involved), saves it in
 * assets/fonts, and every later request is a plain cached file. The fonts are
 * licensed to La La Land and are never stored in the public repository.
 */
require __DIR__ . '/app/bootstrap.php';

$key = (string) ($_GET['f'] ?? '');
$fonts = brand_font_sources();
if (!isset($fonts[$key])) {
    http_response_code(404);
    exit;
}
[$remotePath, $local] = $fonts[$key];
$dir = ROOT_DIR . '/assets/fonts';
$file = $dir . '/' . $local;

if (!is_file($file)) {
    $main = rtrim(safe_url((string) config('main_site_url')), '/');
    $failMarker = $dir . '/.' . $key . '.failed';
    // Don't hammer the main site if it's unreachable: retry at most hourly.
    if ($main === '' || (is_file($failMarker) && filemtime($failMarker) > time() - 3600)) {
        http_response_code(404);
        exit;
    }
    $data = fetch_font($main . $remotePath);
    if ($data === null) {
        @touch($failMarker);
        app_log('warning', 'Brand font download failed', ['font' => $key, 'url' => $main . $remotePath]);
        http_response_code(404);
        exit;
    }
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $tmp = $file . '.' . bin2hex(random_bytes(4)) . '.tmp';
    if (@file_put_contents($tmp, $data) !== false) {
        @rename($tmp, $file);
    }
    @unlink($failMarker);
    if (!is_file($file)) {
        serve_font($data, $local); // disk not writable: serve from memory
    }
}
serve_font((string) file_get_contents($file), $local, (string) filemtime($file));

function fetch_font(string $url): ?string
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 3,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (LaLaLand-Tickets font sync)',
        CURLOPT_HTTPHEADER     => ['Accept: font/ttf, font/woff2, font/woff, application/octet-stream, */*'],
    ]);
    $data = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    if (!is_string($data) || $status !== 200 || strlen($data) < 2048 || strlen($data) > 5_000_000) {
        return null;
    }
    // Must really be a font (TrueType, OpenType, WOFF or WOFF2), not an HTML error page.
    $magic = substr($data, 0, 4);
    return in_array($magic, ["\x00\x01\x00\x00", 'OTTO', 'true', 'wOFF', 'wOF2'], true) ? $data : null;
}

function serve_font(string $data, string $name, string $etag = ''): never
{
    $types = ['ttf' => 'font/ttf', 'otf' => 'font/otf', 'woff' => 'font/woff', 'woff2' => 'font/woff2'];
    header('Content-Type: ' . ($types[strtolower(pathinfo($name, PATHINFO_EXTENSION))] ?? 'application/octet-stream'));
    header('Cache-Control: public, max-age=31536000, immutable');
    header('Content-Length: ' . strlen($data));
    if ($etag !== '') {
        header('ETag: "' . md5($etag . $name) . '"');
    }
    echo $data;
    exit;
}
