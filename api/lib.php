<?php
/**
 * Shared library: target parsing, deterministic vibe scoring (ported 1:1
 * from assets/js/engine.js so server-rendered cards match the client),
 * and remote metadata fetching (incl. GitHub API).
 */

/* ---- CONFIG (keep in sync with engine.js) ---- */
const HUMAN_WHITELIST = [
  'isitvibecoded.iazz.fr','iazz.fr',
  'ilianazz.com','mutka.app','headofscience.fr',
];
const HUMAN_GITHUB_OWNERS = ['ilianazz'];

/* ===================================================================
   Deterministic RNG — 32-bit, matching the JS implementation exactly
   =================================================================== */
function ivc_imul($a, $b) {
  $a &= 0xFFFFFFFF; $b &= 0xFFFFFFFF;
  $ah = ($a >> 16) & 0xFFFF; $al = $a & 0xFFFF;
  $bh = ($b >> 16) & 0xFFFF; $bl = $b & 0xFFFF;
  return ($al * $bl + ((($ah * $bl + $al * $bh) << 16) & 0xFFFFFFFF)) & 0xFFFFFFFF;
}
/** returns the xmur3 seed (first output), matching xmur3(str)() in JS */
function ivc_xmur3_seed($str) {
  $h = (1779033703 ^ strlen($str)) & 0xFFFFFFFF;
  $len = strlen($str);
  for ($i = 0; $i < $len; $i++) {
    $h = ivc_imul($h ^ ord($str[$i]), 3432918353);
    $h = ((($h << 13) & 0xFFFFFFFF) | ($h >> 19)) & 0xFFFFFFFF;
  }
  $h = ivc_imul($h ^ ($h >> 16), 2246822507);
  $h = ivc_imul($h ^ ($h >> 13), 3266489909);
  return ($h ^ ($h >> 16)) & 0xFFFFFFFF;
}
/** first mulberry32 output in [0,1) from a 32-bit seed */
function ivc_mulberry32_first($a) {
  $a = ($a + 0x6D2B79F5) & 0xFFFFFFFF;
  $t = $a;
  $t = ivc_imul($t ^ ($t >> 15), (1 | $t) & 0xFFFFFFFF);
  $t = ((($t + ivc_imul($t ^ ($t >> 7), (61 | $t) & 0xFFFFFFFF)) & 0xFFFFFFFF) ^ $t) & 0xFFFFFFFF;
  return (($t ^ ($t >> 14)) & 0xFFFFFFFF) / 4294967296;
}

/* ===================================================================
   Target parsing + whitelist + score
   =================================================================== */
function ivc_parse_target($input) {
  $s = trim((string)$input);
  if ($s === '') return null;
  $s = preg_replace('#^https?://#i', '', $s);
  $s = preg_replace('#^www\.#i', '', $s);
  $s = preg_replace('~[?#].*$~', '', $s);
  $s = rtrim($s, '/');
  $host = strtolower(explode('/', $s)[0]);
  if ($host === '') return null;
  $rest = substr($s, strlen($host));
  $segs = array_values(array_filter(explode('/', $rest), fn($x) => $x !== ''));
  $isGithub = ($host === 'github.com');
  if ($isGithub && count($segs) >= 1) {
    $sub = implode('/', array_slice($segs, 0, 2));
    $key = 'github.com/' . strtolower($sub);
    $display = 'github.com/' . $sub;
  } else {
    $key = $host;
    $display = $host;
  }
  return [
    'host' => $host, 'segs' => $segs, 'isGithub' => $isGithub,
    'key' => $key, 'display' => $display, 'fetchArg' => $s,
  ];
}

function ivc_is_human($t) {
  foreach (HUMAN_WHITELIST as $d) {
    $d = strtolower($d);
    if ($t['host'] === $d || str_ends_with($t['host'], '.' . $d)) return true;
  }
  if ($t['isGithub'] && !empty($t['segs'][0]) &&
      in_array(strtolower($t['segs'][0]), HUMAN_GITHUB_OWNERS, true)) return true;
  return false;
}

/** Deterministic vibe score: 0 for humans, else 80–100 (first rand() call). */
function ivc_score($t) {
  if (ivc_is_human($t)) return 0;
  $seed = ivc_xmur3_seed($t['key']);
  $r = ivc_mulberry32_first($seed);
  return 80 + (int)floor($r * 21);
}

/* ===================================================================
   Metadata fetch (title / description / og-image / favicon), GitHub-aware
   =================================================================== */
function ivc_meta($t) {
  $host = $t['host'];
  $fallback = [
    'ok' => true, 'kind' => 'site', 'domain' => $t['display'],
    'title' => $t['display'], 'description' => '', 'image' => '',
    'favicon' => 'https://icons.duckduckgo.com/ip3/' . $host . '.ico',
    'language' => null, 'stars' => null,
  ];

  $raw = $t['fetchArg'];
  if (!preg_match('#^https?://#i', $raw)) $raw = 'https://' . $raw;

  /* SSRF guard */
  $ip = gethostbyname($host);
  if (filter_var($ip, FILTER_VALIDATE_IP) &&
      !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
    return $fallback;
  }

  /* GitHub API */
  if ($t['isGithub']) {
    $g = ivc_github($t);
    if ($g) return $g;
  }

  $ctx = stream_context_create([
    'http' => [
      'method' => 'GET', 'timeout' => 8, 'follow_location' => 1, 'max_redirects' => 4,
      'ignore_errors' => true,
      'user_agent' => 'Mozilla/5.0 (compatible; IsItVibeCodedBot/2.1; +https://isitvibecoded.iazz.fr)',
      'header' => "Accept: text/html,application/xhtml+xml\r\n",
    ],
    'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
  ]);
  $fp = @fopen($raw, 'r', false, $ctx);
  if ($fp === false) return $fallback;
  $html = @stream_get_contents($fp, 400000);
  fclose($fp);
  if ($html === false || $html === '') return $fallback;

  $title = ivc_meta_val($html, 'property', 'og:title');
  if ($title === '' && preg_match('#<title[^>]*>(.*?)</title>#is', $html, $m)) $title = trim($m[1]);
  if ($title === '') $title = $host;

  $desc = ivc_meta_val($html, 'property', 'og:description');
  if ($desc === '') $desc = ivc_meta_val($html, 'name', 'description');

  $image = ivc_meta_val($html, 'property', 'og:image:secure_url');
  if ($image === '') $image = ivc_meta_val($html, 'property', 'og:image');
  if ($image === '') $image = ivc_meta_val($html, 'name', 'twitter:image');
  if ($image === '') $image = ivc_meta_val($html, 'name', 'twitter:image:src');
  $image = ivc_absolutize($image, $raw);

  $favicon = '';
  if (preg_match_all('#<link[^>]+rel\s*=\s*["\']([^"\']*icon[^"\']*)["\'][^>]*>#i', $html, $links, PREG_SET_ORDER)) {
    foreach ($links as $lnk) {
      if (preg_match('#href\s*=\s*["\'](.*?)["\']#i', $lnk[0], $h)) { $favicon = trim($h[1]); break; }
    }
  }
  $favicon = $favicon !== '' ? ivc_absolutize($favicon, $raw)
                             : 'https://icons.duckduckgo.com/ip3/' . $host . '.ico';

  $clean = fn($s) => trim(preg_replace('/\s+/u', ' ', $s ?? ''));
  $dec = fn($s) => html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');

  return [
    'ok' => true, 'kind' => 'site', 'domain' => $host,
    'title' => mb_substr($dec($clean($title)), 0, 160),
    'description' => mb_substr($dec($clean($desc)), 0, 300),
    'image' => $image, 'favicon' => $favicon,
    'language' => null, 'stars' => null,
  ];
}

function ivc_github($t) {
  $segs = $t['segs'];
  $reserved = ['features','marketplace','pricing','about','login','join','sponsors',
    'topics','collections','trending','explore','settings','notifications','orgs','apps',
    'search','new','codespaces','issues','pulls','dashboard'];
  $call = function ($path) {
    $ctx = stream_context_create(['http' => [
      'method' => 'GET', 'timeout' => 7, 'ignore_errors' => true,
      'user_agent' => 'IsItVibeCodedBot/2.1',
      'header' => "Accept: application/vnd.github+json\r\n",
    ]]);
    $raw = @file_get_contents("https://api.github.com/$path", false, $ctx);
    if ($raw === false) return null;
    $j = json_decode($raw, true);
    return is_array($j) ? $j : null;
  };
  $fav = 'https://github.githubassets.com/favicons/favicon.svg';

  if (count($segs) >= 2 && !in_array(strtolower($segs[0]), $reserved, true)) {
    $repo = preg_replace('/\.git$/', '', $segs[1]);
    $r = $call('repos/' . $segs[0] . '/' . $repo);
    if ($r && !empty($r['full_name'])) {
      $bits = [];
      if (!empty($r['language'])) $bits[] = $r['language'];
      $bits[] = '★ ' . (int)($r['stargazers_count'] ?? 0);
      $bits[] = '⑂ ' . (int)($r['forks_count'] ?? 0);
      $desc = $r['description'] ?: ('A GitHub repository · ' . implode(' · ', $bits));
      return [
        'ok' => true, 'kind' => 'repo', 'domain' => 'github.com/' . $r['full_name'],
        'title' => $r['full_name'], 'description' => mb_substr($desc, 0, 300),
        'image' => 'https://opengraph.githubassets.com/1/' . $r['full_name'],
        'favicon' => $fav, 'language' => $r['language'] ?? null,
        'stars' => (int)($r['stargazers_count'] ?? 0),
      ];
    }
  }
  if (count($segs) === 1 && !in_array(strtolower($segs[0]), $reserved, true)) {
    $u = $call('users/' . $segs[0]);
    if ($u && !empty($u['login'])) {
      return [
        'ok' => true, 'kind' => 'user', 'domain' => 'github.com/' . $u['login'],
        'title' => ($u['name'] ?: $u['login']) . ' · GitHub',
        'description' => $u['bio'] ?: ((int)($u['public_repos'] ?? 0) . ' public repositories · ' . (int)($u['followers'] ?? 0) . ' followers'),
        'image' => $u['avatar_url'] ?? '', 'favicon' => $fav,
        'language' => null, 'stars' => null,
      ];
    }
  }
  return null;
}

function ivc_meta_val($html, $keyAttr, $keyVal) {
  $q = preg_quote($keyVal, '#');
  if (preg_match('#<meta[^>]*' . $keyAttr . '\s*=\s*["\']' . $q . '["\'][^>]*content\s*=\s*["\'](.*?)["\'][^>]*>#is', $html, $m))
    return trim($m[1]);
  if (preg_match('#<meta[^>]*content\s*=\s*["\'](.*?)["\'][^>]*' . $keyAttr . '\s*=\s*["\']' . $q . '["\'][^>]*>#is', $html, $m))
    return trim($m[1]);
  return '';
}
function ivc_absolutize($url, $base) {
  if ($url === '') return '';
  if (preg_match('#^https?://#i', $url)) return $url;
  $bp = parse_url($base);
  $scheme = $bp['scheme'] ?? 'https';
  $bhost = $bp['host'] ?? '';
  $port = isset($bp['port']) ? ':' . $bp['port'] : '';
  if (str_starts_with($url, '//')) return $scheme . ':' . $url;
  if (str_starts_with($url, '/')) return "$scheme://$bhost$port$url";
  $path = $bp['path'] ?? '/';
  $dir = preg_replace('#/[^/]*$#', '/', $path);
  if ($dir === '') $dir = '/';
  return "$scheme://$bhost$port$dir$url";
}
