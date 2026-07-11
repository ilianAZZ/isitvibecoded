<?php
/**
 * Router for the PHP built-in web server.
 *   php -S 0.0.0.0:8080 router.php
 *
 * - Serves real files (assets, images, css, js) directly.
 * - /api/meta , /api/og  → API endpoints.
 * - /, /how-it-works, /pricing, /about → their HTML pages.
 * - /<anything-else> → index.html with per-URL OG/meta tags injected
 *   server-side (e.g. /mutka.app or /github.com/owner/repo).
 */

$uri  = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = ltrim(rawurldecode($uri), '/');
$seg  = explode('/', $path)[0];

/* API endpoints */
if ($seg === 'api') {
  if ($path === 'api/meta') { require __DIR__ . '/api/meta.php'; return true; }
  if ($path === 'api/og')   { require __DIR__ . '/api/og.php';   return true; }
  http_response_code(404);
  header('Content-Type: application/json');
  echo json_encode(['ok' => false, 'error' => 'not found']);
  return true;
}

/* Let the built-in server serve any real file that exists (assets, favicon.ico, etc.) */
$candidate = __DIR__ . '/' . $path;
if ($path !== '' && is_file($candidate) && strpos(realpath($candidate) ?: '', __DIR__) === 0) {
  return false; // built-in server streams it with the correct MIME type
}

/* Explicit page routes (static) */
$pages = [
  ''             => 'index.html',
  'how-it-works' => 'how-it-works.html',
  'pricing'      => 'pricing.html',
  'about'        => 'about.html',
];

header('Content-Type: text/html; charset=utf-8');
/* Hardening headers (help E-E-A-T / page-experience signals, harmless to crawlers) */
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(), microphone=(), camera=(), interest-cohort=()');

if (array_key_exists($seg, $pages)) {
  header('X-Robots-Tag: index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1');
  readfile(__DIR__ . '/' . $pages[$seg]);
  return true;
}

/* ---- Deep link: /<domain>[/...] → index.html with dynamic social tags ---- */
require __DIR__ . '/api/lib.php';
$t = ivc_parse_target($path);

/* Only real domain-like targets (or GitHub) are analyzable. Everything else
   (e.g. /wp-login.php, /.env, /wp-admin, random junk) gets a genuine 404 +
   noindex so it never enters the index as thin/soft-404 content.
   A valid host is a dotted domain whose last label is a plausible TLD and is
   NOT a source/config file extension (.php, .env, .json, .css …). */
$badExt = '/\.(php\d?|env|asp|aspx|jsp|cgi|pl|py|rb|sh|bak|old|sql|ya?ml|toml|ini|cfg|conf|log|map|css|m?js|json|txt|xml|rss|ico|png|jpe?g|gif|svg|webp|bmp|woff2?|ttf|eot|htaccess|ds_store)$/i';
$domainRe = '/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/i';
$isTarget = $t && (
  $t['isGithub'] ||
  (preg_match($domainRe, $t['host']) && !preg_match($badExt, $t['host']))
);
if (!$isTarget) {
  http_response_code(404);
  header('X-Robots-Tag: noindex, follow');
  echo "<!doctype html><html lang=\"en\"><head><meta charset=\"UTF-8\">"
     . "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">"
     . "<meta name=\"robots\" content=\"noindex, follow\">"
     . "<title>404 - Not found · Is It Vibe Coded?</title>"
     . "<link rel=\"icon\" type=\"image/svg+xml\" href=\"/assets/favicon.svg\">"
     . "<link rel=\"stylesheet\" href=\"/assets/css/styles.css\"></head>"
     . "<body><main class=\"wrap\" style=\"text-align:center;padding:80px 20px\">"
     . "<h1>404 - nothing to analyze here</h1>"
     . "<p class=\"sub\">That path isn't a website or a GitHub repo. "
     . "Paste a real URL on the <a href=\"/\">home page</a>.</p>"
     . "<p><a class=\"analyze\" href=\"/\" style=\"display:inline-flex;text-decoration:none;padding:12px 24px;border-radius:12px;margin-top:16px\">Go home →</a></p>"
     . "</main></body></html>";
  return true;
}

/* Deep-link pages stay shareable (rich OG cards) but are kept OUT of the index:
   their body is client-rendered, so to a crawler they're thin/near-duplicate.
   noindex,follow → no thin-content noise in Search Console; links still followed. */
header('X-Robots-Tag: noindex, follow, max-image-preview:large');
$html = file_get_contents(__DIR__ . '/index.html');
$html = preg_replace(
  '/<meta name="(robots|googlebot)" content="[^"]*">/i',
  '<meta name="$1" content="noindex, follow, max-image-preview:large">',
  $html
);

if ($t) {
  $meta   = ivc_meta($t);
  $human  = ivc_is_human($t);
  $score  = ivc_score($t);
  $domain = $meta['domain'];

  $verdict = $human
    ? '100% human-written'
    : $score . '% AI vibe - likely vibe coded';
  $title = $domain . ' is ' . ($human ? '100% human-written' : $score . '% vibe coded') . ' · Is It Vibe Coded?';

  $snippet = $meta['description'] !== '' ? ' “' . mb_substr($meta['description'], 0, 120) . '”.' : '';
  $desc = $domain . ' scored ' . ($human ? '0% AI vibe' : $score . '% AI vibe') . ' - ' . $verdict . '.' . $snippet . ' Free, instant AI code detection.';

  $canonUrl = 'https://isitvibecoded.iazz.fr/' . implode('/', array_map('rawurlencode', explode('/', $t['display'])));
  $ogImg    = 'https://isitvibecoded.iazz.fr/api/og?url=' . rawurlencode($t['fetchArg']);

  $e = fn($s) => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
  $block  = "<!--DYN:START-->\n";
  $block .= '<title>' . $e($title) . "</title>\n";
  $block .= '<meta name="description" content="' . $e($desc) . "\">\n";
  $block .= '<link rel="canonical" href="' . $e($canonUrl) . "\">\n";
  $block .= '<meta property="og:title" content="' . $e($title) . "\">\n";
  $block .= '<meta property="og:description" content="' . $e($desc) . "\">\n";
  $block .= '<meta property="og:url" content="' . $e($canonUrl) . "\">\n";
  $block .= '<meta property="og:image" content="' . $e($ogImg) . "\">\n";
  $block .= '<meta property="og:image:alt" content="' . $e($domain . ' - ' . ($human ? '100% human' : $score . '% AI vibe')) . "\">\n";
  $block .= '<meta name="twitter:title" content="' . $e($title) . "\">\n";
  $block .= '<meta name="twitter:description" content="' . $e($desc) . "\">\n";
  $block .= '<meta name="twitter:image" content="' . $e($ogImg) . "\">\n";
  $block .= '<!--DYN:END-->';

  $html = preg_replace('/<!--DYN:START-->.*?<!--DYN:END-->/s', $block, $html, 1);
}

echo $html;
return true;
