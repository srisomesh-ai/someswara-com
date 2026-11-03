<?php
session_start();
require __DIR__ . '/lib.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

function out($data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json');
    header('Cache-Control: no-store');
    echo json_encode($data);
    exit;
}
function fail(string $msg, int $code = 400): void { out(['error' => $msg], $code); }

// ---------- public actions ----------
if ($action === 'state') out(['setup' => is_setup(), 'logged_in' => logged_in()]);

if ($action === 'setup') {
    if (is_setup()) fail('Already set up', 403);
    $pw = $_POST['password'] ?? '';
    $key = trim($_POST['api_key'] ?? '');
    if (strlen($pw) < 6) fail('Use a password of at least 6 characters.');
    if ($key === '') fail('Enter your RunPod API key.');
    write_json('settings.json', [
        'password_hash' => password_hash($pw, PASSWORD_DEFAULT),
        'api_key' => $key,
        'volume_id' => trim($_POST['volume_id'] ?? ''),
        'idle_minutes' => 15,
    ]);
    $_SESSION['ok'] = true;
    out(['ok' => true]);
}

if ($action === 'login') {
    $s = settings();
    if (!empty($s['password_hash']) && password_verify($_POST['password'] ?? '', $s['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['ok'] = true;
        out(['ok' => true]);
    }
    sleep(1);
    fail('Wrong password.', 401);
}

if (!logged_in()) fail('Log in first.', 401);

// ---------- logged-in actions ----------
switch ($action) {

case 'logout':
    session_destroy();
    out(['ok' => true]);

case 'settings':
    $s = settings();
    out(['volume_id' => $s['volume_id'] ?? '', 'idle_minutes' => $s['idle_minutes'] ?? 15,
         'key_hint' => '…' . substr($s['api_key'] ?? '', -4)]);

case 'save_settings':
    $s = settings();
    if (($k = trim($_POST['api_key'] ?? '')) !== '') $s['api_key'] = $k;
    $s['volume_id'] = trim($_POST['volume_id'] ?? '');
    $s['idle_minutes'] = max(5, min(120, (int)($_POST['idle_minutes'] ?? 15)));
    if (($pw = $_POST['password'] ?? '') !== '') {
        if (strlen($pw) < 6) fail('Use a password of at least 6 characters.');
        $s['password_hash'] = password_hash($pw, PASSWORD_DEFAULT);
    }
    write_json('settings.json', $s);
    out(['ok' => true]);

case 'status':
    $pod = read_json('pod.json');
    if (empty($pod['id'])) out(['on' => false, 'note' => $pod['note'] ?? '']);
    $r = rp('GET', '/pods/' . rawurlencode($pod['id']));
    if ($r['code'] === 404 || (is_array($r['data']) && ($r['data']['desiredStatus'] ?? '') === 'TERMINATED')) {
        write_json('pod.json', ['note' => 'GPU switched itself off after being idle. Billing stopped.']);
        out(['on' => false, 'note' => 'GPU switched itself off after being idle. Billing stopped.']);
    }
    if ($r['code'] !== 200) out(['on' => true, 'phase' => 'unknown', 'msg' => rp_error($r)] + $pod);
    $p = $r['data'];
    $cost = (float)($p['costPerHr'] ?? $pod['cost_hr'] ?? 0);
    $gpu = $p['machine']['gpuTypeId'] ?? $p['machine']['gpuType']['displayName'] ?? $p['gpu']['displayName'] ?? $pod['gpu'] ?? 'GPU';
    $mins = (time() - $pod['started']) / 60;
    $boot = boot_status($pod['id']);
    $phase = $boot === 'ready' ? 'ready' : (str_starts_with($boot, 'Error') ? 'error' : 'starting');
    out([
        'on' => true, 'phase' => $phase,
        'msg' => $boot === '' ? 'Waiting for a GPU machine' : $boot,
        'gpu' => str_replace(['NVIDIA ', 'GeForce '], '', $gpu),
        'cost_hr' => $cost, 'minutes' => round($mins), 'spent' => round($cost * $mins / 60, 2),
    ]);

case 'start':
    $pod = read_json('pod.json');
    if (!empty($pod['id'])) out(['ok' => true]);
    $r = pod_create();
    if ($r['code'] < 200 || $r['code'] >= 300 || empty($r['data']['id'])) {
        $msg = rp_error($r);
        if (stripos($msg, 'no ') !== false && stripos($msg, 'available') !== false)
            $msg = 'No GPU free right now. Try again in a few minutes.';
        fail($msg);
    }
    $p = $r['data'];
    write_json('pod.json', [
        'id' => $p['id'], 'started' => time(),
        'cost_hr' => $p['costPerHr'] ?? 0,
        'gpu' => $p['machine']['gpuTypeId'] ?? GPU_TYPES[0],
    ]);
    out(['ok' => true]);

case 'stop':
    $pod = read_json('pod.json');
    if (!empty($pod['id'])) {
        $r = rp('DELETE', '/pods/' . rawurlencode($pod['id']));
        if ($r['code'] >= 400 && $r['code'] !== 404) fail(rp_error($r));
    }
    write_json('pod.json', ['note' => 'GPU stopped. Billing stopped.']);
    out(['ok' => true]);

case 'render':
    $pod = read_json('pod.json');
    if (empty($pod['id'])) fail('Start the GPU first.');
    if (boot_status($pod['id']) !== 'ready') fail('The GPU is still getting ready. Try again in a moment.');
    $prompt = trim($_POST['prompt'] ?? '');
    if ($prompt === '') fail('Describe the shot first.');
    if (empty($_FILES['image']['tmp_name']) || !is_uploaded_file($_FILES['image']['tmp_name']))
        fail('Add a reference photo first.');
    if (!@getimagesize($_FILES['image']['tmp_name'])) fail('That file is not an image.');

    $frames = ((int)($_POST['seconds'] ?? 5)) === 3 ? 49 : 81;
    $steps = ($_POST['quality'] ?? 'normal') === 'high' ? 30 : 20;
    $id = date('Ymd_His') . '_' . bin2hex(random_bytes(2));

    $up = comfy('POST', '/upload/image', ['timeout' => 120, 'form' => [
        'image' => new CURLFile($_FILES['image']['tmp_name'], 'image/jpeg', "ref_$id.jpg"),
        'overwrite' => 'true',
    ]]);
    $upd = json_decode($up['body'], true);
    if ($up['code'] !== 200 || empty($upd['name'])) fail('Could not send the photo to the GPU. Try again.');

    $wf = build_workflow($prompt, $upd['name'], $frames, $steps, "previs_$id");
    $q = comfy('POST', '/prompt', ['json' => ['prompt' => $wf, 'client_id' => CLIENT_ID], 'timeout' => 60]);
    $qd = json_decode($q['body'], true);
    if ($q['code'] !== 200 || empty($qd['prompt_id'])) {
        $err = $qd['error']['message'] ?? $qd['error'] ?? substr($q['body'], 0, 200);
        fail('The video engine refused the job: ' . (is_string($err) ? $err : json_encode($err)));
    }
    // keep a small thumbnail of the reference for the gallery
    @copy($_FILES['image']['tmp_name'], CLIPS_DIR . "/$id.jpg");

    $jobs = read_json('jobs.json');
    array_unshift($jobs, [
        'id' => $id, 'prompt_id' => $qd['prompt_id'], 'prompt' => $prompt,
        'seconds' => $frames === 49 ? 3 : 5, 'quality' => $steps === 30 ? 'high' : 'normal',
        'status' => 'queued', 'pct' => 0, 'created' => time(),
    ]);
    write_json('jobs.json', $jobs);
    out(['ok' => true, 'id' => $id]);

case 'jobs':
    $jobs = read_json('jobs.json');
    $pod = read_json('pod.json');
    $changed = false;
    $progress = null;
    foreach ($jobs as &$j) {
        if (!in_array($j['status'], ['queued', 'rendering'], true)) continue;
        if (empty($pod['id'])) { $j['status'] = 'failed'; $j['error'] = 'GPU was stopped before it finished'; $changed = true; continue; }
        $h = comfy('GET', '/history/' . rawurlencode($j['prompt_id']));
        $hd = json_decode($h['body'], true);
        if ($h['code'] === 200 && !empty($hd[$j['prompt_id']])) {
            $e = $hd[$j['prompt_id']];
            if (($e['status']['status_str'] ?? '') === 'error') {
                $j['status'] = 'failed'; $j['error'] = 'Render failed on the GPU';
            } else {
                $file = null;
                foreach ($e['outputs'] ?? [] as $node)
                    foreach (['images', 'videos', 'gifs'] as $k)
                        foreach ($node[$k] ?? [] as $f)
                            if (preg_match('/\.(mp4|webm)$/i', $f['filename'])) $file = $f;
                if (!$file) { $j['status'] = 'failed'; $j['error'] = 'No video came back'; }
                else {
                    $dest = CLIPS_DIR . "/{$j['id']}.mp4";
                    $v = comfy('GET', '/view?' . http_build_query([
                        'filename' => $file['filename'], 'subfolder' => $file['subfolder'] ?? '',
                        'type' => $file['type'] ?? 'output']), ['file' => $dest, 'timeout' => 300]);
                    if ($v['code'] === 200 && filesize($dest) > 1000) { $j['status'] = 'done'; $j['pct'] = 100; }
                    else @unlink($dest); // try again next poll
                }
            }
            $changed = true;
        } else {
            $progress ??= render_progress($pod['id']);
            if (isset($progress[$j['prompt_id']])) {
                $j['status'] = 'rendering';
                $j['pct'] = (int)$progress[$j['prompt_id']];
                $changed = true;
            }
        }
    }
    unset($j);
    if ($changed) write_json('jobs.json', $jobs);
    out(array_map(fn($j) => array_diff_key($j, ['prompt_id' => 1]), $jobs));

case 'delete':
    $id = preg_replace('/[^0-9A-Za-z_]/', '', $_POST['id'] ?? '');
    $jobs = array_values(array_filter(read_json('jobs.json'), fn($j) => $j['id'] !== $id));
    write_json('jobs.json', $jobs);
    @unlink(CLIPS_DIR . "/$id.mp4");
    @unlink(CLIPS_DIR . "/$id.jpg");
    out(['ok' => true]);

case 'file':
    $id = preg_replace('/[^0-9A-Za-z_]/', '', $_GET['id'] ?? '');
    $ext = ($_GET['type'] ?? '') === 'thumb' ? 'jpg' : 'mp4';
    $path = CLIPS_DIR . "/$id.$ext";
    if (!$id || !is_file($path)) fail('Not found', 404);
    session_write_close();
    $size = filesize($path);
    header('Content-Type: ' . ($ext === 'mp4' ? 'video/mp4' : 'image/jpeg'));
    header('Accept-Ranges: bytes');
    header('Cache-Control: private, max-age=86400');
    if (isset($_GET['dl'])) header("Content-Disposition: attachment; filename=\"shot_$id.$ext\"");
    // Range support so phones can play/seek the video
    if ($ext === 'mp4' && preg_match('/bytes=(\d*)-(\d*)/', $_SERVER['HTTP_RANGE'] ?? '', $m)) {
        $start = $m[1] === '' ? 0 : (int)$m[1];
        $end = $m[2] === '' ? $size - 1 : min((int)$m[2], $size - 1);
        http_response_code(206);
        header("Content-Range: bytes $start-$end/$size");
        header('Content-Length: ' . ($end - $start + 1));
        $fh = fopen($path, 'rb');
        fseek($fh, $start);
        echo fread($fh, $end - $start + 1);
        fclose($fh);
        exit;
    }
    header("Content-Length: $size");
    readfile($path);
    exit;

default:
    fail('Unknown action');
}
