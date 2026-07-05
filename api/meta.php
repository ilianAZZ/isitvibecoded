<?php
/**
 * /api/meta?url=<domain-or-url>
 * Returns the target's real title, description, OG image and favicon as JSON.
 * GitHub repo/user URLs are resolved via the GitHub API.
 */
require __DIR__ . '/lib.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: public, max-age=3600');

$raw = isset($_GET['url']) ? trim($_GET['url']) : '';
if ($raw === '') { echo json_encode(['ok' => false, 'error' => 'missing url']); exit; }

$t = ivc_parse_target($raw);
if (!$t) { echo json_encode(['ok' => false, 'error' => 'bad url']); exit; }

$meta = ivc_meta($t);
echo json_encode($meta, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
