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
$writes = ['setup', 'login', 'logout', 'save_settings', 'call_start', 'call_turn', 'call_text', 'call_end', 'call_flag',
    'training_save', 'correction_save', 'correction_ignore', 'live_token', 'phone_save', 'phone_newkey', 'train_save', 'product_save', 'product_remove', 'product_notes', 'gps_save', 'rec_token', 'areas_key', 'areas_save', 'area_tech_add', 'area_tech_remove'];
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

// ---------- live call server ----------
// The VPS has no login of its own. The dashboard hands the browser a one-time token (live_token, below);
// the VPS trades it here for what the call needs, and reports the transcript back with the same token.
// Every request from the call server says which version of server.js is running on the VPS. Remember the latest.
if (isset($_GET['sv']) && ctype_digit((string)$_GET['sv']) && in_array($action, ['phone_config', 'live_config'], true)) {
    write_json('server.json', ['v' => (int)$_GET['sv'], 'at' => time()]);
}

// The call server asks whether a pass for one recording is good. The pass itself is the secret.
if ($action === 'rec_check') {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') fail('Bad request.', 400);
    $p = read_json('rec.json')[(string)($_POST['t'] ?? '')] ?? null;
    if (!is_array($p) || ($p['exp'] ?? 0) < time() || !hash_equals((string)$p['id'], (string)($_POST['id'] ?? ''))) fail('Bad pass.', 403);
    out(['ok' => true]);
}

// A real phone call has reached the live call server. The secret key in the address Exotel was given is the pass.
if ($action === 'phone_config') {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') fail('Bad request.', 400);
    $s = settings();
    if (empty($s['phone_key']) || !hash_equals((string)$s['phone_key'], (string)($_POST['key'] ?? ''))) fail('Bad key.', 403);
    if (sarvam_key() === '') fail('Add your Sarvam API key in Settings first.');
    $from = phone_digits($_POST['from'] ?? '');
    $trainer = !empty($s['train_on']) && $from !== '' && in_array($from, (array)($s['train_nums'] ?? []), true);
    $allowed = $trainer || ($s['phone_mode'] ?? 'test') === 'all' || ($from !== '' && in_array($from, (array)($s['phone_tests'] ?? []), true));
    $lang = lang(); $voice = current_voice();
    $greeting = $allowed ? training()['greeting'] : ($lang === 'hi' ? BUSY_HI : BUSY_TE);
    $call = ['id' => bin2hex(random_bytes(8)), 'started' => time(), 'last' => time(), 'voice' => $voice, 'lang' => $lang,
        'live' => true, 'phone' => true, 'from' => $from, 'answered' => $allowed, 'turns' => [['who' => 'agent', 'text' => $greeting]]];
    call_save($call);
    $all = array_filter(read_json('live.json'), fn($r) => is_array($r) && ($r['exp'] ?? 0) > time());
    $tok = bin2hex(random_bytes(24));
    $all[$tok] = ['exp' => time() + 7200, 'call_id' => $call['id'], 'trainer' => $trainer];   // lets the server report this call's transcript
    write_json('live.json', $all);
    $s['phone_last'] = time();
    write_json('settings.json', $s);
    $pay = live_payload($lang, $trainer);
    $memory = $allowed && !$trainer ? caller_memory($from, $call['id']) : '';   // an earlier call from this same number
    if ($memory !== '') { foreach ($pay['prompts'] as $l => $x) $pay['prompts'][$l] = $x . "\n\n" . $memory; $pay['prompt'] = $pay['prompts'][$lang]; }
    out(['allowed' => $allowed, 'token' => $tok, 'greeting' => $greeting, 'call_id' => $call['id'], 'returning' => $memory !== ''] + $pay);
}

if (in_array($action, ['live_config', 'live_sync', 'live_rule', 'live_vehicle', 'live_place'], true)) {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') fail('Bad request.', 400);
    $tok = (string)($_POST['token'] ?? '');
    $all = read_json('live.json');
    if (!preg_match('~^[a-f0-9]{48}$~', $tok) || !isset($all[$tok]) || ($all[$tok]['exp'] ?? 0) < time()) fail('Bad token.', 403);
    if ($action === 'live_config') {
        if (!empty($all[$tok]['call_id'])) fail('Token already used.', 403);
        if (sarvam_key() === '') fail('Add your Sarvam API key in Settings first.');
        $lang = lang(); $voice = current_voice(); $greeting = training()['greeting'];
        $call = ['id' => bin2hex(random_bytes(8)), 'started' => time(), 'last' => time(), 'voice' => $voice, 'lang' => $lang,
            'live' => true, 'turns' => [['who' => 'agent', 'text' => $greeting]]];
        call_save($call);
        $trainer = !empty(settings()['train_on']);   // a Talk test is made by the logged-in admin
        $all[$tok]['call_id'] = $call['id'];
        $all[$tok]['trainer'] = $trainer;
        write_json('live.json', $all);
        out(['greeting' => $greeting, 'call_id' => $call['id']] + live_payload($lang, $trainer));
    }
    if ($action === 'live_place') {   // the caller said where the vehicle is: is it inside the service area?
        if (empty($all[$tok]['call_id'])) fail('Bad token.', 403);
        $n = (int)($all[$tok]['places'] ?? 0);
        if ($n >= 6) fail('Too many checks on this call.', 429);
        $all[$tok]['places'] = $n + 1;
        write_json('live.json', $all);
        $r = area_check((string)($_POST['place'] ?? ''));
        out(['status' => $r['status'], 'text' => area_result_text($r), 'fact' => area_result_fact($r)]);
    }
    if ($action === 'live_vehicle') {   // the caller confirmed a vehicle number: look it up on the GPS servers
        if (empty($all[$tok]['call_id'])) fail('Bad token.', 403);
        $n = (int)($all[$tok]['lookups'] ?? 0);
        if ($n >= 6) fail('Too many checks on this call.', 429);
        $all[$tok]['lookups'] = $n + 1;
        write_json('live.json', $all);
        session_write_close();
        $r = gps_find((string)($_POST['number'] ?? ''));
        if (($c = call_find((string)$all[$tok]['call_id'])) && ($r['number'] ?? '') !== '' && empty($r['near'])) {   // remembered for the next call from this number
            $c['vehicles'] = array_values(array_unique(array_merge((array)($c['vehicles'] ?? []), [$r['status'] === 'found' ? $r['plate'] : $r['number']])));
            call_save($c);
        }
        $why = in_array($_POST['why'] ?? '', ['renew', 'trouble'], true) ? $_POST['why'] : '';
        out(['status' => $r['status'], 'near' => !empty($r['near']), 'text' => gps_result_text($r, $why), 'fact' => gps_result_fact($r)]);
    }
    if ($action === 'live_rule') {   // she was taught something on a call and the owner said yes
        if (empty($all[$tok]['trainer'])) fail('This call cannot teach her.', 403);
        $rule = clean_line($_POST['rule'] ?? '', 300);
        if ($rule === '') fail('Empty rule.');
        $t = read_json('training.json');
        $rules = array_values(array_filter((array)($t['rules'] ?? []), 'is_string'));
        if (!in_array($rule, $rules, true)) {
            if (count($rules) >= 40) fail('The rule list is full.');
            $rules[] = $rule;
            $t['rules'] = $rules;
            $t['learned'][$rule] = time();
            write_json('training.json', $t);
        }
        out(['ok' => true]);
    }
    $call = call_find((string)($all[$tok]['call_id'] ?? ''));
    if (!$call) fail('Call not found.', 404);
    $turns = [];
    foreach ((array)json_decode((string)($_POST['turns'] ?? '[]'), true) as $t) {
        if (!is_array($t)) continue;
        $text = clean_line($t['text'] ?? '', 1000);
        if ($text === '') continue;
        $row = ['who' => ($t['who'] ?? '') === 'agent' ? 'agent' : 'caller', 'text' => $text];
        $old = $call['turns'][count($turns)] ?? null;          // keep a "marked wrong" already made on this line
        if ($old && !empty($old['flagged']) && $old['text'] === $text) $row['flagged'] = true;
        $turns[] = $row;
    }
    if ($turns) $call['turns'] = array_slice($turns, 0, 200);
    $call['last'] = time();
    if (($_POST['ended'] ?? '') === '1' && empty($call['ended'])) $call['ended'] = time();
    if ((int)($_POST['rec_secs'] ?? 0) > 0) $call['rec'] = (int)$_POST['rec_secs'];   // the call server kept a recording
    // A real phone call has ended: understand it, raise a task if the team has work, and email the team, once.
    // The owner's own training calls are left out. The call server gets its answer first; the rest takes a few seconds.
    $wrap = !empty($call['ended']) && !empty($call['phone']) && empty($call['mailed']) && empty($all[$tok]['trainer']);
    if ($wrap) $call['mailed'] = 2;   // being done
    call_save($call);
    if (!$wrap) out(['ok' => true]);
    ignore_user_abort(true); @set_time_limit(90);
    $body = json_encode(['ok' => true]);
    header('Content-Type: application/json; charset=utf-8'); header('Cache-Control: no-store'); header('Content-Length: ' . strlen($body)); header('Connection: close');
    echo $body;
    if (function_exists('fastcgi_finish_request')) fastcgi_finish_request();
    elseif (function_exists('litespeed_finish_request')) litespeed_finish_request();
    else { while (ob_get_level()) ob_end_flush(); flush(); }
    $keep = call_wrap($call);
    $fresh = call_find($call['id']) ?: $call;
    call_save($keep + $fresh);
    exit;
}

if (!logged_in()) fail('Log in first.', 401);

// ---------- admin actions ----------
function need_key(): void {
    session_write_close();
    if (sarvam_key() === '') fail('Add your Sarvam API key in Settings first.');
}

function current_voice(): string {
    $list = voices_for(lang());   // each language remembers its own voice
    $v = (string)(settings()[lang() === 'hi' ? 'voice_hi' : 'voice'] ?? '');
    return in_array($v, $list, true) ? $v : $list[0];
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
        'key_hint' => sarvam_key() !== '' ? substr(sarvam_key(), -4) : '',
        'voice' => current_voice(),
        'voices' => voices_for(lang()),
        'lang' => lang(),
        'stats' => ['calls' => $count, 'leads' => 0, 'staff' => 0, 'avg_seconds' => $count ? (int)round($secs / $count) : 0],
        'calls' => array_map('call_summary', array_slice($all, 0, 50)),
        'server_old' => (int)(read_json('server.json')['v'] ?? 0) < LIVE_VERSION,   // the VPS still runs an older call server
    ]);

case 'save_settings':
    $s = settings();
    $key = trim((string)($_POST['sarvam_key'] ?? ''));
    if ($key !== '') $s['sarvam_key'] = $key;
    $lang = (string)($_POST['lang'] ?? '');
    if (isset(LANGS[$lang])) $s['lang'] = $lang;
    $cur = isset(LANGS[$s['lang'] ?? '']) ? $s['lang'] : 'te';
    $voice = (string)($_POST['voice'] ?? '');
    if (in_array($voice, voices_for($cur), true)) $s[$cur === 'hi' ? 'voice_hi' : 'voice'] = $voice;
    $pw = (string)($_POST['password'] ?? '');
    if ($pw !== '') {
        if (strlen($pw) < 8) fail('Use a password of at least 8 characters.');
        $s['password_hash'] = password_hash($pw, PASSWORD_DEFAULT);
    }
    write_json('settings.json', $s);
    out(['ok' => true]);

case 'phone_get':
    $s = settings();
    if (empty($s['phone_key'])) { $s['phone_key'] = bin2hex(random_bytes(16)); write_json('settings.json', $s); }
    out(phone_view($s));

case 'phone_newkey':   // the old address stops working at once
    $s = settings();
    $s['phone_key'] = bin2hex(random_bytes(16));
    write_json('settings.json', $s);
    out(phone_view($s));

case 'phone_save':
    $s = settings();
    $s['phone_mode'] = ($_POST['mode'] ?? '') === 'all' ? 'all' : 'test';
    $tests = [];
    foreach (preg_split('~[\r\n,]+~', (string)($_POST['tests'] ?? '')) as $line) {
        $n = phone_digits($line);
        if (strlen($n) === 10 && !in_array($n, $tests, true)) $tests[] = $n;
    }
    $s['phone_tests'] = array_slice($tests, 0, 20);
    write_json('settings.json', $s);
    out(phone_view($s));

case 'live_token':   // a one-time pass for the browser to open a call on the live call server
    need_key();
    $all = array_filter(read_json('live.json'), fn($r) => is_array($r) && ($r['exp'] ?? 0) > time());
    $tok = bin2hex(random_bytes(24));
    $all[$tok] = ['exp' => time() + 7200];
    write_json('live.json', $all);
    out(['url' => LIVE_URL, 'token' => $tok]);

case 'call_start':
    need_key();
    $voice = current_voice();
    $typed = ($_POST['mode'] ?? '') === 'type';
    $lang = lang();
    $greeting = training()['greeting'];
    $audio = null;
    if (!$typed || ($_POST['speak'] ?? '') === '1') {
        $tts = sarvam_tts($greeting, $voice, $lang);
        if (isset($tts['error'])) fail($tts['error'], 502);
        $audio = $tts['audio'];
    }
    $call = [
        'id' => bin2hex(random_bytes(8)),
        'started' => time(),
        'last' => time(),
        'voice' => $voice,
        'lang' => $lang,
        'typed' => $typed,
        'turns' => [['who' => 'agent', 'text' => $greeting]],
    ];
    call_save($call);
    out(['call_id' => $call['id'], 'text' => $greeting, 'audio' => $audio]);

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
    $chat = sarvam_chat($call['turns'], $call['lang'] ?? 'te');
    $t2 = microtime(true);
    if (isset($chat['error'])) fail($chat['error'], 502);
    $tts = sarvam_tts($chat['text'], $call['voice'], $call['lang'] ?? 'te');
    $t3 = microtime(true);
    if (isset($tts['error'])) fail($tts['error'], 502);

    $call['turns'][] = ['who' => 'agent', 'text' => $chat['text']];
    $call['last'] = time();
    call_save($call);
    out(['heard' => $stt['text'], 'reply' => $chat['text'], 'audio' => $tts['audio'], 'turn' => count($call['turns']) - 1,
        'ms' => ['hear' => (int)(($t1 - $t0) * 1000), 'think' => (int)(($t2 - $t1) * 1000), 'speak' => (int)(($t3 - $t2) * 1000)]]);

case 'call_text':   // the caller's line typed instead of spoken
    need_key();
    $call = open_call();
    $text = clean_line($_POST['text'] ?? '', 500);
    if ($text === '') fail('Type something first.');
    $call['turns'][] = ['who' => 'caller', 'text' => $text, 'typed' => true];
    $t1 = microtime(true);
    $chat = sarvam_chat($call['turns'], $call['lang'] ?? 'te');
    $t2 = microtime(true);
    if (isset($chat['error'])) fail($chat['error'], 502);
    $audio = null;
    if (($_POST['speak'] ?? '') === '1') {
        $tts = sarvam_tts($chat['text'], $call['voice'], $call['lang'] ?? 'te');
        if (isset($tts['error'])) fail($tts['error'], 502);
        $audio = $tts['audio'];
    }
    $t3 = microtime(true);
    $call['turns'][] = ['who' => 'agent', 'text' => $chat['text']];
    $call['last'] = time();
    call_save($call);
    out(['reply' => $chat['text'], 'audio' => $audio, 'turn' => count($call['turns']) - 1,
        'ms' => ['hear' => 0, 'think' => (int)(($t2 - $t1) * 1000), 'speak' => (int)(($t3 - $t2) * 1000)]]);

case 'call_end':
    $call = open_call();
    $call['ended'] = time();
    call_save($call);
    out(['ok' => true]);

case 'call_flag':   // mark one of her replies as wrong; it then waits in Training for the right answer
    $call = call_find((string)($_POST['call_id'] ?? ''));
    if (!$call) fail('Call not found.', 404);
    $i = (int)($_POST['turn'] ?? -1);
    if (!isset($call['turns'][$i]) || $call['turns'][$i]['who'] !== 'agent') fail('Reply not found.', 404);
    if (empty($call['turns'][$i]['flagged'])) {
        $call['turns'][$i]['flagged'] = true;
        $call['flagged'] = true;
        call_save($call);
        $q = '';
        for ($j = $i - 1; $j >= 0; $j--) {
            if ($call['turns'][$j]['who'] === 'caller') { $q = $call['turns'][$j]['text']; break; }
        }
        $t = read_json('training.json');
        $t['corrections'][] = ['id' => bin2hex(random_bytes(6)), 'q' => $q, 'said' => $call['turns'][$i]['text'], 'created' => time()];
        $t['corrections'] = array_slice($t['corrections'], -100);
        write_json('training.json', $t);
    }
    out(['ok' => true]);

case 'training_get':
    out(training());

case 'training_save':
    $rules = []; $examples = [];
    foreach ((array)json_decode((string)($_POST['rules'] ?? '[]'), true) as $r) {
        $r = clean_line(is_string($r) ? $r : '', 300);
        if ($r !== '') $rules[] = $r;
    }
    foreach ((array)json_decode((string)($_POST['examples'] ?? '[]'), true) as $e) {
        if (!is_array($e)) continue;
        $q = clean_line($e['q'] ?? '', 300); $a = clean_line($e['a'] ?? '', 500);
        if ($q !== '' && $a !== '') $examples[] = ['q' => $q, 'a' => $a];
    }
    $t = read_json('training.json');
    $t[lang() === 'hi' ? 'greeting_hi' : 'greeting'] = clean_line($_POST['greeting'] ?? '', 400);
    $t['rules'] = array_slice($rules, 0, 40);
    $t['examples'] = array_slice($examples, 0, 60);
    $t['learned'] = array_intersect_key((array)($t['learned'] ?? []), array_flip($t['rules']));   // forget labels of removed rules
    write_json('training.json', $t);
    out(training());

case 'train_save':   // who may teach her by voice
    $s = settings();
    $s['train_on'] = ($_POST['on'] ?? '') === '1';
    $nums = [];
    foreach (preg_split('~[\r\n,]+~', (string)($_POST['nums'] ?? '')) as $line) {
        $n = phone_digits($line);
        if (strlen($n) === 10 && !in_array($n, $nums, true)) $nums[] = $n;
    }
    $s['train_nums'] = array_slice($nums, 0, 10);
    write_json('settings.json', $s);
    out(training());

case 'areas_get':
case 'areas_key':
case 'areas_save':
case 'area_tech_add':
case 'area_tech_remove':
    $d = read_json('areas.json');
    $d['techs'] = array_values(array_filter((array)($d['techs'] ?? []), 'is_array'));
    $keyState = '';
    if ($action === 'areas_key') {
        $v = trim((string)($_POST['key'] ?? ''));
        $s = settings();
        if ($v === '-') $s['maps_key'] = '';
        elseif ($v !== '' && strpos($v, '•') === false) {
            if (strlen($v) < 20 || strlen($v) > 120 || preg_match('~\s~', $v)) fail('That does not look like a Google Maps API key. Paste the full key.');
            $s['maps_key'] = $v;
        }
        write_json('settings.json', $s);
        if (maps_key() !== '') {   // try it once
            $t = geo_find('Visakhapatnam', false);
            $keyState = $t['ok'] ? 'ok' : ($t['why'] === 'key' ? 'bad' : 'down');
            $s['maps_ok'] = $keyState; $s['maps_msg'] = $t['msg'] ?? ''; write_json('settings.json', $s);
        }
    }
    if ($action === 'areas_save') {
        $km = (int)preg_replace('~\D~', '', (string)($_POST['km'] ?? ''));
        if ($km < 1 || $km > 2000) fail('Type the distance in km, from 1 to 2000.');
        $d['km'] = $km;
        $d['outside'] = mb_substr(trim(preg_replace('~\s+~u', ' ', (string)($_POST['outside'] ?? ''))), 0, 300);
        write_json('areas.json', $d);
    }
    if ($action === 'area_tech_add') {
        $name = clean_line($_POST['name'] ?? '', 60); $place = clean_line($_POST['place'] ?? '', 120);
        if ($name === '') fail('Type the technician name.');
        if ($place === '') fail('Type the town or area.');
        if (count($d['techs']) >= 60) fail('The list is full. Remove one first.');
        session_write_close();
        $g = geo_find($place, false);
        if (!$g['ok']) fail($g['why'] === 'none' ? 'The map could not find "' . $place . '". Add the town or district name too.' : ($g['why'] === 'key' ? 'Google did not accept the Maps key. Check it in step 1.' : 'The map could not be reached just now. Try again.'));
        $d['techs'][] = ['id' => bin2hex(random_bytes(6)), 'name' => $name, 'place' => $place, 'lat' => $g['lat'], 'lng' => $g['lng'], 'found' => $g['label'], 'via' => $g['via']];
        write_json('areas.json', $d);
    }
    if ($action === 'area_tech_remove') {
        $id = (string)($_POST['id'] ?? '');
        $d['techs'] = array_values(array_filter($d['techs'], fn($t) => ($t['id'] ?? '') !== $id));
        write_json('areas.json', $d);
    }
    $a = areas(); $s = settings(); $key = maps_key();
    out(['techs' => $a['techs'], 'km' => $a['km'], 'outside' => $a['outside'],
        'key' => ['has' => $key !== '', 'hint' => $key !== '' ? substr($key, -4) : '', 'state' => $key === '' ? 'free' : (string)($s['maps_ok'] ?? 'ok'), 'msg' => (string)($s['maps_msg'] ?? '')]]);

case 'area_try':
    session_write_close();
    out(area_check((string)($_GET['q'] ?? '')));

case 'gps_get':
case 'gps_save':
    if ($action === 'gps_save') {
        $s = settings(); $keys = gps_keys();
        foreach (GPS_SERVERS as $id => $host) {
            $v = trim((string)($_POST['k' . $id] ?? ''));
            if ($v === '' || strpos($v, '•') !== false) continue;   // empty or still the hidden one: keep what is saved
            if ($v === '-') { $keys[$id] = ''; continue; }           // a single dash removes the key
            if (strlen($v) < 20 || strlen($v) > 200 || preg_match('~\s~', $v)) fail('The key for ' . $host . ' does not look right. Paste the full API key.');
            $keys[$id] = $v;
        }
        $s['gps_keys'] = $keys; write_json('settings.json', $s);
        session_write_close();
        $ix = gps_index(true);
    } else { $ix = read_json('gps.json'); }
    $rows = [];
    foreach (GPS_SERVERS as $id => $host) {
        $key = gps_keys()[$id];
        $st = (array)($ix['servers'][$id] ?? []);
        $rows[] = ['id' => $id, 'host' => $host, 'has_key' => $key !== '', 'hint' => $key !== '' ? substr($key, -4) : '',
            'state' => $key === '' ? 'nokey' : ($st['state'] ?? 'unknown'), 'count' => (int)($st['count'] ?? 0)];
    }
    out(['servers' => $rows]);

case 'gps_find':
    session_write_close();
    out(gps_find((string)($_GET['q'] ?? '')));

case 'products_get':
    $n = import_site_products();   // the website's products are copied in once, the first time this page opens
    out(products() + ['imported' => $n]);

case 'product_save':   // a new product, or a changed one when its id is sent
    $d = read_json('products.json');
    $list = array_values(array_filter((array)($d['products'] ?? []), 'is_array'));
    $name = clean_line($_POST['name'] ?? '', 80);
    $price = (int)preg_replace('~\D~', '', (string)($_POST['price'] ?? ''));
    if ($name === '') fail('Type the product name.');
    if ($price < 1 || $price > 9999999) fail('Type the price in rupees, numbers only.');
    $fits = array_values(array_intersect(FITS, (array)json_decode((string)($_POST['fits'] ?? '[]'), true)));
    $feat = [];
    foreach (preg_split('~[\r\n]+~', (string)($_POST['feat'] ?? '')) as $line) { $line = clean_line($line, 120); if ($line !== '' && count($feat) < 8) $feat[] = $line; }
    $row = ['id' => (string)($_POST['id'] ?? ''), 'name' => $name, 'price' => $price, 'fits' => $fits,
        'for' => clean_line($_POST['for'] ?? '', 200), 'inc' => clean_line($_POST['inc'] ?? '', 200), 'feat' => $feat];
    $found = false;
    foreach ($list as $i => $x) if ($row['id'] !== '' && ($x['id'] ?? '') === $row['id']) { $list[$i] = $row; $found = true; }
    if (!$found) {
        if (count($list) >= 40) fail('The list is full. Remove a product first.');
        $row['id'] = bin2hex(random_bytes(6));
        $list[] = $row;
    }
    $d['products'] = $list;
    write_json('products.json', $d);
    out(products());

case 'product_remove':
    $d = read_json('products.json');
    $id = (string)($_POST['id'] ?? '');
    $d['products'] = array_values(array_filter((array)($d['products'] ?? []), fn($x) => is_array($x) && ($x['id'] ?? '') !== $id));
    write_json('products.json', $d);
    out(products());

case 'product_notes':
    $d = read_json('products.json');
    $d['notes'] = mb_substr(trim(preg_replace('~[ \t]+~u', ' ', str_replace("\r", '', (string)($_POST['notes'] ?? '')))), 0, 1500);
    write_json('products.json', $d);
    out(products());

case 'correction_save':   // the right answer for a marked reply becomes a new example
case 'correction_ignore':
    $t = read_json('training.json');
    $id = (string)($_POST['id'] ?? '');
    $found = null; $rest = [];
    foreach ((array)($t['corrections'] ?? []) as $c) {
        if (is_array($c) && ($c['id'] ?? '') === $id) $found = $c; else $rest[] = $c;
    }
    if (!$found) fail('Correction not found.', 404);
    if ($action === 'correction_save') {
        $a = clean_line($_POST['answer'] ?? '', 500);
        if ($a === '') fail('Type what she should have said.');
        $q = clean_line($found['q'] ?? '', 300);
        $t['examples'][] = ['q' => $q !== '' ? $q : '(start of the call)', 'a' => $a];
        $t['examples'] = array_slice($t['examples'], -60);
    }
    $t['corrections'] = $rest;
    write_json('training.json', $t);
    out(training());

case 'rec_token':   // a two-hour pass for the logged-in owner to play one call's recording
    $call = call_find((string)($_POST['id'] ?? ''));
    if (!$call || empty($call['rec'])) fail('This call has no recording.', 404);
    $all = array_filter(read_json('rec.json'), fn($r) => is_array($r) && ($r['exp'] ?? 0) > time());
    $tok = bin2hex(random_bytes(24));
    $all[$tok] = ['id' => $call['id'], 'exp' => time() + 7200];
    write_json('rec.json', $all);
    out(['url' => REC_URL . '/' . $call['id'] . '.wav?t=' . $tok]);

case 'call_get':
    $call = call_find((string)($_GET['id'] ?? ''));
    if (!$call) fail('Call not found.', 404);
    out(['call' => call_summary($call) + ['lines' => $call['turns']]]);

default:
    fail('Unknown action.', 404);
}
