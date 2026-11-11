<?php
require __DIR__ . '/lib.php';
start_session();
set_time_limit(90);

$action = $_GET['action'] ?? '';

function out($data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
function fail(string $msg, int $code = 400): void { out(['error' => $msg], $code); }

// Anything that changes data must be a POST sent by this page (custom header blocks cross-site forms).
$writes = ['setup', 'login', 'logout', 'save_settings', 'call_start', 'call_turn', 'call_end', 'call_flag'];
if (in_array($action, $writes, true)) {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || ($_SERVER['HTTP_X_IVR'] ?? '') !== '1') fail('Bad request.', 400);
}

// ---------- public actions ----------
if ($action === 'state') out(['setup' => is_setup(), 'logged_in' => logged_in()]);

if ($action === 'setup') {
    if (is_setup()) fail('Already set up.', 403);
    $pw = (string)($_POST['password'] ?? '');
    if (strlen($pw) < 8) fail('Use a password of at least 8 characters.');
    write_json('settings.json', [
        'password_hash' => password_hash($pw, PASSWORD_DEFAULT),
        'sarvam_key' => trim((string)($_POST['sarvam_key'] ?? '')),
        'voice' => VOICES[0],
    ]);
    session_regenerate_id(true);
    $_SESSION['ivr_admin'] = true;
    out(['ok' => true]);
}

if ($action === 'login') {
    if ($left = login_locked()) fail('Too many wrong tries. Try again in ' . ceil($left / 60) . ' minutes.', 429);
    $s = settings();
    if (!empty($s['password_hash']) && password_verify((string)($_POST['password'] ?? ''), $s['password_hash'])) {
        write_json('login.json', ['fails' => 0, 'until' => 0]);
        session_regenerate_id(true);
        $_SESSION['ivr_admin'] = true;
        out(['ok' => true]);
    }
    login_failed();
    sleep(1);
    fail('Wrong password.', 401);
}

if (!logged_in()) fail('Log in first.', 401);

// ---------- admin actions ----------
function need_key(): void {
    if (sarvam_key() === '') fail('Add your Sarvam API key in Settings first.');
}

function current_voice(): string {
    $v = (string)(settings()['voice'] ?? '');
    return in_array($v, VOICES, true) ? $v : VOICES[0];
}

function open_call(): array {
    $c = call_find((string)($_POST['call_id'] ?? ''));
    if (!$c) fail('Call not found.', 404);
    if (!empty($c['ended'])) fail('This call has ended.');
    return $c;
}

switch ($action) {

case 'logout':
    $_SESSION = [];
    session_destroy();
    out(['ok' => true]);

case 'overview':
    $all = calls_all();
    $today = date('Y-m-d');
    $count = 0; $secs = 0;
    foreach ($all as $c) {
        if (date('Y-m-d', $c['started']) !== $today) continue;
        $count++;
        $secs += call_summary($c)['seconds'];
    }
    out([
        'has_key' => sarvam_key() !== '',
        'voice' => current_voice(),
        'voices' => VOICES,
        'stats' => ['calls' => $count, 'leads' => 0, 'staff' => 0, 'avg_seconds' => $count ? (int)round($secs / $count) : 0],
        'calls' => array_map('call_summary', array_slice($all, 0, 50)),
    ]);

case 'save_settings':
    $s = settings();
    $key = trim((string)($_POST['sarvam_key'] ?? ''));
    if ($key !== '') $s['sarvam_key'] = $key;
    $voice = (string)($_POST['voice'] ?? '');
    if (in_array($voice, VOICES, true)) $s['voice'] = $voice;
    $pw = (string)($_POST['password'] ?? '');
    if ($pw !== '') {
        if (strlen($pw) < 8) fail('Use a password of at least 8 characters.');
        $s['password_hash'] = password_hash($pw, PASSWORD_DEFAULT);
    }
    write_json('settings.json', $s);
    out(['ok' => true]);

case 'call_start':
    need_key();
    $voice = current_voice();
    $tts = sarvam_tts(GREETING, $voice);
    if (isset($tts['error'])) fail($tts['error'], 502);
    $call = [
        'id' => bin2hex(random_bytes(8)),
        'started' => time(),
        'last' => time(),
        'voice' => $voice,
        'turns' => [['who' => 'agent', 'text' => GREETING]],
    ];
    call_save($call);
    out(['call_id' => $call['id'], 'text' => GREETING, 'audio' => $tts['audio']]);

case 'call_turn':
    need_key();
    $call = open_call();
    $f = $_FILES['audio'] ?? null;
    if (!$f || $f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) fail('No audio received.');
    if ($f['size'] > 2 * 1024 * 1024) fail('That recording is too long.');
    if (file_get_contents($f['tmp_name'], false, null, 0, 4) !== 'RIFF') fail('Audio must be WAV.');

    $t0 = microtime(true);
    $stt = sarvam_stt($f['tmp_name']);
    $t1 = microtime(true);
    if (isset($stt['error'])) fail($stt['error'], 502);
    if ($stt['text'] === '') out(['heard' => '', 'reply' => null]);

    $call['turns'][] = ['who' => 'caller', 'text' => $stt['text']];
    $chat = sarvam_chat($call['turns']);
    $t2 = microtime(true);
    if (isset($chat['error'])) fail($chat['error'], 502);
    $tts = sarvam_tts($chat['text'], $call['voice']);
    $t3 = microtime(true);
    if (isset($tts['error'])) fail($tts['error'], 502);

    $call['turns'][] = ['who' => 'agent', 'text' => $chat['text']];
    $call['last'] = time();
    call_save($call);
    out(['heard' => $stt['text'], 'reply' => $chat['text'], 'audio' => $tts['audio'],
        'ms' => ['hear' => (int)(($t1 - $t0) * 1000), 'think' => (int)(($t2 - $t1) * 1000), 'speak' => (int)(($t3 - $t2) * 1000)]]);

case 'call_end':
    $call = open_call();
    $call['ended'] = time();
    call_save($call);
    out(['ok' => true]);

case 'call_flag':
    $call = call_find((string)($_POST['call_id'] ?? ''));
    if (!$call) fail('Call not found.', 404);
    for ($i = count($call['turns']) - 1; $i >= 0; $i--) {
        if ($call['turns'][$i]['who'] === 'agent') { $call['turns'][$i]['flagged'] = true; break; }
    }
    $call['flagged'] = true;
    call_save($call);
    out(['ok' => true]);

case 'call_get':
    $call = call_find((string)($_GET['id'] ?? ''));
    if (!$call) fail('Call not found.', 404);
    out(['call' => call_summary($call) + ['lines' => $call['turns']]]);

default:
    fail('Unknown action.', 404);
}
