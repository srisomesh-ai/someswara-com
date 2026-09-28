<?php
// AI Video Studio — shared helpers, storage and model catalogue.

define('APP_DIR', __DIR__);
define('DATA_DIR', APP_DIR . '/data');
define('VIDEO_DIR', APP_DIR . '/videos');
define('UPLOAD_DIR', APP_DIR . '/uploads');
define('CONFIG_FILE', DATA_DIR . '/config.json');
define('JOBS_FILE', DATA_DIR . '/jobs.json');
define('FAL_QUEUE', 'https://queue.fal.run/');

foreach (array(DATA_DIR, VIDEO_DIR, UPLOAD_DIR) as $d) {
    if (!is_dir($d)) { @mkdir($d, 0755, true); }
}

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(array(
        'lifetime' => 60 * 60 * 24 * 30,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ));
    session_name('aivs');
    session_start();
}

/*
 * Model catalogue. Prices are fal.ai USD per second of video at 720p,
 * [without audio, with audio]. They are estimates for the on-screen
 * cost preview only — fal bills the actual amount. Edit here if fal changes rates.
 */
function models() {
    return array(
        'kling3' => array(
            'name' => 'Kling 3.0',
            't2v' => 'fal-ai/kling-video/v3/pro/text-to-video',
            'i2v' => 'fal-ai/kling-video/v3/pro/image-to-video',
            'image_field' => 'start_image_url',
            'price' => array(0.112, 0.168),
            'lengths' => array(5, 8, 10, 15),
            'aspects' => array('9:16', '16:9', '1:1'),
            'duration_format' => 'int',
        ),
        'veo_lite' => array(
            'name' => 'Veo 3.1 Lite',
            't2v' => 'fal-ai/veo3.1/lite',
            'i2v' => 'fal-ai/veo3.1/lite/image-to-video',
            'image_field' => 'image_url',
            'price' => array(0.03, 0.05),
            'lengths' => array(4, 6, 8),
            'aspects' => array('9:16', '16:9'),
            'duration_format' => 's',
        ),
        'veo' => array(
            'name' => 'Veo 3.1',
            't2v' => 'fal-ai/veo3.1',
            'i2v' => 'fal-ai/veo3.1/image-to-video',
            'image_field' => 'image_url',
            'price' => array(0.20, 0.40),
            'lengths' => array(4, 6, 8),
            'aspects' => array('9:16', '16:9'),
            'duration_format' => 's',
        ),
        'seedance2' => array(
            'name' => 'Seedance 2.0',
            't2v' => 'bytedance/seedance-2.0/text-to-video',
            'i2v' => 'bytedance/seedance-2.0/image-to-video',
            'image_field' => 'image_url',
            'price' => array(0.156, 0.156),
            'lengths' => array(5, 8, 10, 15),
            'aspects' => array('9:16', '16:9', '1:1'),
            'duration_format' => 'int',
        ),
    );
}

function default_config() {
    return array('password_hash' => '', 'fal_key' => '', 'inr_rate' => 88);
}

function read_json($file, $default) {
    if (!file_exists($file)) { return $default; }
    $raw = @file_get_contents($file);
    $data = json_decode($raw, true);
    return is_array($data) ? $data : $default;
}

function write_json($file, $data) {
    $tmp = $file . '.tmp';
    file_put_contents($tmp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), LOCK_EX);
    rename($tmp, $file);
}

function config() {
    return array_merge(default_config(), read_json(CONFIG_FILE, array()));
}

function save_config($cfg) {
    write_json(CONFIG_FILE, $cfg);
}

function is_setup_done() {
    $c = config();
    return $c['password_hash'] !== '' && $c['fal_key'] !== '';
}

function is_logged_in() {
    return !empty($_SESSION['aivs_ok']);
}

/* Run $fn(&$jobs) while holding an exclusive lock on the jobs file. */
function with_jobs($fn) {
    $fh = fopen(JOBS_FILE . '.lock', 'c');
    flock($fh, LOCK_EX);
    $jobs = read_json(JOBS_FILE, array());
    $result = $fn($jobs);
    write_json(JOBS_FILE, $jobs);
    flock($fh, LOCK_UN);
    fclose($fh);
    return $result;
}

function estimate_usd($modelKey, $seconds, $audio) {
    $m = models();
    if (!isset($m[$modelKey])) { return 0; }
    return $m[$modelKey]['price'][$audio ? 1 : 0] * $seconds;
}

function fal_request($method, $url, $body = null) {
    $cfg = config();
    $ch = curl_init($url);
    $headers = array('Authorization: Key ' . $cfg['fal_key'], 'Accept: application/json');
    if ($body !== null) {
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }
    curl_setopt_array($ch, array(
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_CONNECTTIMEOUT => 15,
    ));
    $raw = curl_exec($ch);
    $err = curl_error($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $json = json_decode((string) $raw, true);
    return array('code' => $code, 'error' => $err, 'json' => is_array($json) ? $json : null, 'raw' => $raw);
}

/* Turn a fal error body into one readable sentence. */
function fal_error_text($res) {
    if ($res['error']) { return 'Connection problem: ' . $res['error']; }
    $j = $res['json'];
    if ($res['code'] === 401 || $res['code'] === 403) { return 'fal.ai rejected the API key. Check it in settings.'; }
    if (is_array($j) && isset($j['detail'])) {
        if (is_string($j['detail'])) { return $j['detail']; }
        if (is_array($j['detail']) && isset($j['detail'][0]['msg'])) { return $j['detail'][0]['msg']; }
    }
    return 'fal.ai error (HTTP ' . $res['code'] . ')';
}

/* Save a remote video into videos/ so the gallery keeps working after fal's link expires. */
function download_video($url, $jobId) {
    $name = $jobId . '.mp4';
    $dest = VIDEO_DIR . '/' . $name;
    $fp = fopen($dest, 'wb');
    $ch = curl_init($url);
    curl_setopt_array($ch, array(
        CURLOPT_FILE => $fp,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 240,
        CURLOPT_CONNECTTIMEOUT => 20,
    ));
    $ok = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    fclose($fp);
    if (!$ok || $code >= 400 || filesize($dest) < 1000) {
        @unlink($dest);
        return null;
    }
    return 'videos/' . $name;
}

function base_url() {
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $scheme = $https ? 'https' : 'http';
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) { $scheme = $_SERVER['HTTP_X_FORWARDED_PROTO']; }
    $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
    return $scheme . '://' . $_SERVER['HTTP_HOST'] . $dir . '/';
}

function json_out($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function h($s) {
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}
