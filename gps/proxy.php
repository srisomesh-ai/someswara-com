<?php
// Forwards test calls to the BharatGPS (GPSWOX) API so the browser avoids CORS.
// Only https servers and read-only endpoints are allowed.
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex');

$allowed = ['get_devices', 'get_devices_latest', 'get_history', 'get_user_data', 'get_events'];
$ep = $_GET['ep'] ?? '';
if (!in_array($ep, $allowed, true)) {
    http_response_code(400);
    echo json_encode(['status' => 0, 'message' => 'Endpoint not allowed']);
    exit;
}

// Server: default bharatgps.in; any https GPSWOX server allowed, but not private/internal addresses
$server = trim($_GET['server'] ?? 'https://bharatgps.in');
$server = preg_replace('#/api/?$#i', '', rtrim($server, '/'));
$parts = parse_url($server);
$host = $parts['host'] ?? '';
if (($parts['scheme'] ?? '') !== 'https' || $host === '' || isset($parts['query']) || isset($parts['user'])) {
    http_response_code(400);
    echo json_encode(['status' => 0, 'message' => 'Server URL must start with https://']);
    exit;
}
$ips = gethostbynamel($host) ?: [];
if (!$ips) {
    http_response_code(400);
    echo json_encode(['status' => 0, 'message' => 'Server not found: ' . $host]);
    exit;
}
foreach ($ips as $ip) {
    if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
        http_response_code(400);
        echo json_encode(['status' => 0, 'message' => 'Server address not allowed']);
        exit;
    }
}

$params = $_GET;
unset($params['ep'], $params['server']);
if (empty($params['user_api_hash'])) {
    http_response_code(400);
    echo json_encode(['status' => 0, 'message' => 'API hash missing']);
    exit;
}
$params['lang'] = $params['lang'] ?? 'en';

$url = $server . '/api/' . $ep . '?' . http_build_query($params);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_HTTPHEADER => ['Accept: application/json'],
    CURLOPT_FOLLOWLOCATION => false,
    CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
]);
$body = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err = curl_error($ch);
curl_close($ch);

if ($body === false) {
    http_response_code(502);
    echo json_encode(['status' => 0, 'message' => 'Server not reachable: ' . $err]);
    exit;
}
http_response_code($code ?: 200);
echo $body;
