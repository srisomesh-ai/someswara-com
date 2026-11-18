<?php
// Forwards test calls to the BharatGPS (GPSWOX) API so the browser avoids CORS.
// Only bharatgps.in and read-only endpoints are allowed.
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

$params = $_GET;
unset($params['ep']);
if (empty($params['user_api_hash'])) {
    http_response_code(400);
    echo json_encode(['status' => 0, 'message' => 'API hash missing']);
    exit;
}
$params['lang'] = $params['lang'] ?? 'en';

$url = 'https://bharatgps.in/api/' . $ep . '?' . http_build_query($params);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_HTTPHEADER => ['Accept: application/json'],
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
