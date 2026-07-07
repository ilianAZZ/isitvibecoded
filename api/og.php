<?php
/**
 * /api/og?url=<domain-or-url>
 * Generates a 1200x630 Open Graph card on the fly for a given target:
 * its logo, title, description and the deterministic vibe score.
 */
require __DIR__ . '/lib.php';

/* Never leak warnings into the binary image stream. */
ini_set('display_errors', '0');
error_reporting(0);

$raw = isset($_GET['url']) ? trim($_GET['url']) : '';
$t = $raw !== '' ? ivc_parse_target($raw) : null;

/* Graceful fallback if GD is unavailable → serve the static card. */
if (!function_exists('imagecreatetruecolor') || !function_exists('imagettftext') || !$t) {
  header('Location: /assets/og.png', true, 302);
  exit;
}

$meta   = ivc_meta($t);
$human  = ivc_is_human($t);
$score  = ivc_score($t);

/* ---- fonts (bundled, with system fallbacks) ---- */
function ivc_font($bold) {
  $cands = $bold
    ? [__DIR__.'/../assets/fonts/DejaVuSans-Bold.ttf', '/usr/share/fonts/ttf-dejavu/DejaVuSans-Bold.ttf', '/System/Library/Fonts/Supplemental/Arial Bold.ttf']
    : [__DIR__.'/../assets/fonts/DejaVuSans.ttf', '/usr/share/fonts/ttf-dejavu/DejaVuSans.ttf', '/System/Library/Fonts/Supplemental/Arial.ttf'];
  foreach ($cands as $c) if (is_file($c)) return $c;
  return $cands[0];
}
$FB = ivc_font(true);   // bold
$FR = ivc_font(false);  // regular

$W = 1200; $H = 630;
$im = imagecreatetruecolor($W, $H);
imagealphablending($im, true);
imageantialias($im, true);

/* ---- palette (light theme, matches the site) ---- */
$c = fn($r,$g,$b) => imagecolorallocate($im, $r,$g,$b);
$ink    = $c(15,23,42);
$muted  = $c(100,116,139);
$brand  = $c(99,102,241);
$track  = $c(229,232,236);
$green  = $c(5,150,105);
$red    = $c(220,38,38);

/* ---- light background (#ffffff -> #f4f5f8) ---- */
for ($y = 0; $y < $H; $y++) {
  $tf = $y / $H;
  imageline($im, 0, $y, $W, $y, $c(
    (int)(255 + (244 - 255) * $tf),
    (int)(255 + (245 - 255) * $tf),
    (int)(255 + (248 - 255) * $tf)
  ));
}

/* ---- helpers ---- */
function tw($size,$font,$text){ $b=imagettfbbox($size,0,$font,$text); return abs($b[2]-$b[0]); }
function ellip($size,$font,$text,$maxw){
  if (tw($size,$font,$text) <= $maxw) return $text;
  while ($text!=='' && tw($size,$font,$text.'…') > $maxw) $text = mb_substr($text,0,-1);
  return rtrim($text).'…';
}
function text($im,$size,$font,$x,$y,$color,$text){ imagettftext($im,$size,0,(int)round($x),(int)round($y),$color,$font,$text); }

$PADX = 80;

/* ---- brand wordmark (top-left) ---- */
imagefilledellipse($im, $PADX+8, 74, 18, 18, $brand);
text($im,21,$FB,$PADX+28,82,$ink,'Is It Vibe Coded?');

/* ---- logo + domain ---- */
$logoY = 168; $logoSize = 112;
$logo = ivc_load_logo($meta, $t['host']);
if ($logo) {
  imagecopyresampled($im,$logo,$PADX,$logoY,0,0,$logoSize,$logoSize,imagesx($logo),imagesy($logo));
  imagedestroy($logo);
  $domX = $PADX + $logoSize + 24;
} else {
  $domX = $PADX;
}
$maxTextW = 860 - $domX;
$dom = ellip(38,$FB,$meta['domain'],$maxTextW);
text($im,38,$FB,$domX,$logoY+58,$ink,$dom);
$kicker = $meta['kind']==='repo' ? 'GitHub repository' : ($meta['kind']==='user' ? 'GitHub profile' : 'Website analysis');
text($im,18,$FR,$domX,$logoY+94,$muted,$kicker);

/* ---- short verdict (auto-fit so it never touches the gauge) ---- */
if ($human){
  $verdict = 'Certified human-written'; $vc = $green;
} else {
  $verdict = 'Likely vibe coded'; $vc = $red;
}
$vSize = 46;
while ($vSize > 28 && tw($vSize,$FB,$verdict) > 760) $vSize -= 2;
text($im,$vSize,$FB,$PADX,410,$vc,$verdict);

/* ---- gauge (right) - supersampled for smooth edges ---- */
$gx=1000; $gy=315; $gd=230; $thick=26;
$frac = $human ? 1.0 : $score/100;
$arcRGB = $human ? [5,150,105] : [99,102,241];
ivc_ring($im,$gx,$gy,$gd,$thick,$frac,[229,232,236],$arcRGB);
$big = $human ? '100%' : $score.'%';
$bs = 60; $bw = tw($bs,$FB,$big);
text($im,$bs,$FB,$gx-$bw/2,$gy+8,$ink,$big);
$sub = $human ? 'HUMAN' : 'AI VIBE';
$ss = 17; $sw = tw($ss,$FB,$sub);
text($im,$ss,$FB,$gx-$sw/2,$gy+48,$muted,$sub);

/* ---- slim brand accent bar (bottom) ---- */
for ($x = 0; $x < $W; $x++) {
  $tf = $x / $W;
  $bar = $c((int)(79+(124-79)*$tf),(int)(70+(58-70)*$tf),(int)(229+(237-229)*$tf));
  imageline($im,$x,$H-8,$x,$H,$bar);
}

/* ---- output ---- */
header('Content-Type: image/png');
header('Cache-Control: public, max-age=86400');
imagepng($im);
imagedestroy($im);

/* draw a smooth ring gauge: filled annulus, 4x supersampled, transparent
   inner hole so it composites cleanly over the (gradient) background */
function ivc_ring($dst,$cx,$cy,$diam,$thick,$frac,$trackRGB,$arcRGB){
  $S = 4;
  $R = ($diam/2) * $S;          // outer radius
  $inner = $R - $thick*$S;      // inner radius
  $pad = 4*$S;
  $sz = (int)($R*2 + $pad*2);
  $lc = (int)($sz/2);
  $layer = imagecreatetruecolor($sz,$sz);
  imagesavealpha($layer,true);
  imagealphablending($layer,false);
  $trans = imagecolorallocatealpha($layer,0,0,0,127);
  imagefilledrectangle($layer,0,0,$sz,$sz,$trans);
  // track (full ring) then arc (pie), then punch the centre transparent
  imagefilledellipse($layer,$lc,$lc,(int)($R*2),(int)($R*2),
    imagecolorallocate($layer,$trackRGB[0],$trackRGB[1],$trackRGB[2]));
  if ($frac > 0){
    imagefilledarc($layer,$lc,$lc,(int)($R*2),(int)($R*2),
      -90,(int)round(-90+360*$frac),
      imagecolorallocate($layer,$arcRGB[0],$arcRGB[1],$arcRGB[2]),IMG_ARC_PIE);
  }
  imagefilledellipse($layer,$lc,$lc,(int)($inner*2),(int)($inner*2),$trans);
  imagealphablending($dst,true);
  $out = (int)($sz/$S);
  imagecopyresampled($dst,$layer,(int)($cx-$out/2),(int)($cy-$out/2),0,0,$out,$out,$sz,$sz);
  imagedestroy($layer);
}

/* fetch a raster logo (favicon) usable by GD; falls back to DuckDuckGo (PNG) */
function ivc_load_logo($meta, $host){
  $urls = [];
  if (!empty($meta['favicon'])) $urls[] = $meta['favicon'];
  $urls[] = 'https://icons.duckduckgo.com/ip3/'.$host.'.ico'; // served as PNG
  $ctx = stream_context_create(['http'=>['timeout'=>6,'ignore_errors'=>true,
    'user_agent'=>'IsItVibeCodedBot/2.1'],'ssl'=>['verify_peer'=>false,'verify_peer_name'=>false]]);
  foreach ($urls as $u){
    $data = @file_get_contents($u, false, $ctx);
    if ($data === false || $data === '') continue;
    $img = @imagecreatefromstring($data); // works for PNG/JPEG/GIF/WEBP, not SVG/ICO
    if ($img !== false) return $img;
  }
  return null;
}
