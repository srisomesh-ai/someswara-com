<?php
// AI Video Studio — JSON API used by the main screen.
require __DIR__ . '/lib.php';

if (!is_logged_in()) { json_out(array('error' => 'Your session expired. Reload and log in.'), 401); }
// Custom header blocks cross-site form posts (simple CSRF guard).
if (empty($_SERVER['HTTP_X_AIVS'])) { json_out(array('error' => 'Bad request'), 400); }

$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($action === 'list') {
    json_out(list_payload());
}

if ($action === 'refresh') {
    refresh_pending();
    json_out(list_payload());
}

if ($action === 'generate' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $in = json_decode(file_get_contents('php://input'), true);
    if (!is_array($in)) { json_out(array('error' => 'Invalid request'), 400); }
    json_out(generate($in));
}

json_out(array('error' => 'Unknown action'), 404);

function list_payload() {
    $cfg = config();
    $jobs = read_json(JOBS_FILE, array());
    $spentUsd = 0;
    foreach ($jobs as $j) {
        if ($j['status'] === 'done') { $spentUsd += $j['cost_usd']; }
    }
    usort($jobs, function ($a, $b) { return $b['created'] - $a['created']; });
    return array(
        'jobs' => $jobs,
        'spent_inr' => round($spentUsd * $cfg['inr_rate']),
        'inr_rate' => $cfg['inr_rate'],
    );
}

function generate($in) {
    $models = models();
    $key = isset($in['model']) ? $in['model'] : '';
    if (!isset($models[$key])) { return array('error' => 'Choose a model'); }
    $m = $models[$key];

    $prompt = trim(isset($in['prompt']) ? (string) $in['prompt'] : '');
    if ($prompt === '') { return array('error' => 'Describe your ad first'); }
    if (mb_strlen($prompt) > 2500) { return array('error' => 'Keep the description under 2,500 characters'); }

    $aspect = isset($in['aspect']) ? $in['aspect'] : '9:16';
    if (!in_array($aspect, $m['aspects'], true)) { $aspect = $m['aspects'][0]; }

    $length = isset($in['length']) ? (int) $in['length'] : 8;
    if (!in_array($length, $m['lengths'], true)) { $length = end($m['lengths']); }

    $audio = !empty($in['audio']);

    $imageUrl = null;
    if (!empty($in['image'])) {
        $imageUrl = save_upload($in['image']);
        if ($imageUrl === null) { return array('error' => 'That photo could not be used. Try a JPG or PNG under 8 MB.'); }
    }

    $body = array(
        'prompt' => $prompt,
        'aspect_ratio' => $aspect,
        'duration' => $m['duration_format'] === 's' ? ($length . 's') : (string) $length,
        'generate_audio' => $audio,
    );
    if ($imageUrl) { $body[$m['image_field']] = $imageUrl; }
    $endpoint = $imageUrl ? $m['i2v'] : $m['t2v'];

    $res = fal_request('POST', FAL_QUEUE . $endpoint, $body);
    if ($res['code'] < 200 || $res['code'] >= 300 || empty($res['json']['request_id'])) {
        return array('error' => fal_error_text($res));
    }

    $job = array(
        'id' => bin2hex(random_bytes(8)),
        'model' => $key,
        'model_name' => $m['name'],
        'prompt' => $prompt,
        'aspect' => $aspect,
        'length' => $length,
        'audio' => $audio,
        'with_photo' => $imageUrl !== null,
        'request_id' => $res['json']['request_id'],
        'status_url' => $res['json']['status_url'],
        'response_url' => $res['json']['response_url'],
        'status' => 'queued',
        'cost_usd' => estimate_usd($key, $length, $audio),
        'created' => time(),
        'video' => null,
        'error' => null,
    );
    with_jobs(function (&$jobs) use ($job) { $jobs[] = $job; });
    return array('ok' => true, 'job' => $job);
}

/* Accepts a data: URL from the browser, stores it under an unguessable name and returns its public URL for fal to fetch. */
function save_upload($dataUrl) {
    if (!preg_match('#^data:image/(jpeg|jpg|png|webp);base64,(.+)$#', $dataUrl, $mt)) { return null; }
    $bin = base64_decode($mt[2], true);
    if ($bin === false || strlen($bin) > 8 * 1024 * 1024 || @getimagesizefromstring($bin) === false) { return null; }
    $ext = $mt[1] === 'png' ? 'png' : ($mt[1] === 'webp' ? 'webp' : 'jpg');
    $name = bin2hex(random_bytes(16)) . '.' . $ext;
    file_put_contents(UPLOAD_DIR . '/' . $name, $bin);
    return base_url() . 'uploads/' . $name;
}

/* Check fal for every unfinished job; download finished videos to the server. */
function refresh_pending() {
    $pending = array();
    foreach (read_json(JOBS_FILE, array()) as $j) {
        if ($j['status'] === 'queued' || $j['status'] === 'running') { $pending[] = $j; }
    }
    foreach ($pending as $j) {
        $update = check_job($j);
        if ($update) {
            with_jobs(function (&$jobs) use ($j, $update) {
                foreach ($jobs as &$x) {
                    if ($x['id'] === $j['id']) { $x = array_merge($x, $update); }
                }
                unset($x);
            });
        }
    }
}

function check_job($j) {
    if (time() - $j['created'] > 30 * 60) {
        return array('status' => 'failed', 'error' => 'Timed out after 30 minutes', 'cost_usd' => 0);
    }
    $st = fal_request('GET', $j['status_url']);
    if ($st['code'] < 200 || $st['code'] >= 300 || !isset($st['json']['status'])) {
        return null; // temporary problem; try again on next poll
    }
    $status = $st['json']['status'];
    if ($status === 'IN_QUEUE') { return $j['status'] === 'queued' ? null : array('status' => 'queued'); }
    if ($status === 'IN_PROGRESS') { return array('status' => 'running'); }
    if ($status !== 'COMPLETED') { return null; }

    $res = fal_request('GET', $j['response_url']);
    $videoUrl = isset($res['json']['video']['url']) ? $res['json']['video']['url'] : null;
    if (!$videoUrl) {
        return array('status' => 'failed', 'error' => fal_error_text($res), 'cost_usd' => 0);
    }
    @set_time_limit(300);
    $local = download_video($videoUrl, $j['id']);
    return array('status' => 'done', 'video' => $local ? $local : $videoUrl, 'finished' => time());
}
