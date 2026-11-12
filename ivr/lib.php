<?php
// Bharat GPS Tracker AI call agent: shared helpers (settings, login, call log, Sarvam API).

const DATA_DIR = __DIR__ . '/data';
const MAX_CALLS = 500;
const LIVE_URL = 'wss://call.someswara.com/live';   // the live call server on the VPS (ivr/live/)
const GREETING = 'నమస్తే, Bharat GPS Tracker కి call చేసినందుకు thanks. చెప్పండి, మీకు ఏ vehicle కి tracker కావాలి?';
const GREETING_HI = 'नमस्ते, Bharat GPS Tracker में call करने के लिए धन्यवाद. बताइए, आपको किस vehicle के लिए tracker चाहिए?';
const LANGS = ['te' => ['name' => 'Telugu', 'code' => 'te-IN'], 'hi' => ['name' => 'Hindi', 'code' => 'hi-IN']];
const VOICES_HI = ['aparna_hi_customer', 'aarti_hi_customer', 'shubh_hi_customer'];   // Sarvam's Hindi customer-care voices
const VOICES = ['kavitha_te_conversation', 'pooja_te_conversation', 'shubh', 'aditya', 'ritu', 'priya', 'neha', 'rahul', 'pooja', 'rohan', 'simran', 'kavya', 'amit', 'dev',
    'ishita', 'shreya', 'ratan', 'varun', 'manan', 'sumit', 'roopa', 'kabir', 'aayan', 'ashutosh', 'advait', 'anand',
    'tanya', 'tarun', 'sunny', 'mani', 'gokul', 'vijay', 'shruti', 'suhani', 'mohit', 'kavitha', 'rehan', 'soham', 'rupali'];

const SYSTEM_PROMPT = <<<'TXT'
You are the phone sales assistant of Bharat GPS Tracker, a GPS vehicle tracker company in Visakhapatnam. You are answering a customer's incoming phone call. Everything you write is spoken aloud to the caller.

How to speak:
- Speak like a friendly local customer care person: everyday spoken Telugu mixed with the common English words people really use (tracker, vehicle, bike, car, installation, SIM, app, WhatsApp, location).
- Write Telugu words in Telugu script and English words in English letters.
- Reply in one or two short sentences, about twenty words at most. Ask only one question at a time.
- Begin most replies with a small natural reaction to what the caller just said, the way people do on the phone: సరే అండి, ఓకే, అవునా, అర్థమైంది, తప్పకుండా. Change it each time and never use the same one twice in a row.
- Use spoken Telugu, never bookish or written-style Telugu. Say కావాలా, చెప్పండి, పంపిస్తాను, చూసుకోవచ్చు. Do not use words like అవసరము, దయచేసి, సంప్రదించండి, వినియోగదారు.
- Be warm and relaxed, not like reading a script. Address the caller as అండి. Use సర్ or మేడమ్ only after the caller's voice or words make it clear.
- If the caller only says hello or something unclear, respond naturally and gently repeat your question in different words.
- No lists, no symbols, no emojis, no formatting. Write numbers in words.

What to do on the call:
- First find out which vehicle the tracker is for, then what the customer is worried about (theft, where it is parked, tracking a driver, family safety).
- Then suggest in simple words what kind of tracker suits them, and say that live location is seen in the mobile app.
- The price list is not loaded yet. Do not say any price, model name, offer or discount. Say you will send the full details and price on WhatsApp to this same number.
- Never invent a feature, a price or a promise.
- If the caller is angry, has a complaint about a device they already have, or asks something you do not know, say you will connect them to the team.
- If the caller speaks only Hindi or English, tell them politely in that language that a team member will call them back.
- If the caller asks whether you are a person or a machine, say honestly that you are the AI assistant of Bharat GPS Tracker.
TXT;

// The same instructions for a Hindi call: only the speaking style and the language rule differ.
const SPEAK_HI = <<<'TXT'
How to speak:
- Speak like a friendly customer care person: everyday spoken Hindi mixed with the common English words people really use (tracker, vehicle, bike, car, installation, SIM, app, WhatsApp, location). The simple Hindi of a phone call, never formal or news-reader Hindi.
- Write Hindi words in Devanagari script and English words in English letters.
- Reply in one or two short sentences, about twenty words at most. Ask only one question at a time.
- Begin most replies with a small natural reaction to what the caller just said, the way people do on the phone: जी, अच्छा जी, ठीक है, बिल्कुल, हाँ जी. Change it each time and never use the same one twice in a row.
- Use spoken words: चाहिए, बताइए, देख लीजिए, भेज देते हैं. Do not use words like कृपया, आवश्यकता, संपर्क करें, उपभोक्ता.
- Be warm and relaxed, not like reading a script. Address the caller as आप and जी. Use सर or मैडम only after the caller's voice or words make it clear.
- Speak for the company as हम (हम भेज देते हैं, हम बता देते हैं), so you never need a masculine or feminine verb for yourself.
- If the caller only says hello or something unclear, respond naturally and gently repeat your question in different words.
- No lists, no symbols, no emojis, no formatting. Write numbers in words.

TXT;
const OTHER_LANG_TE = '- If the caller speaks only Hindi or English, tell them politely in that language that a team member will call them back.';
const OTHER_LANG_HI = '- If the caller speaks English, answer in simple English mixed with Hindi. If the caller speaks only Telugu or another language, tell them politely that a team member will call them back.';

date_default_timezone_set('Asia/Kolkata');
if (!is_dir(DATA_DIR)) @mkdir(DATA_DIR, 0755, true);

// ---------- storage ----------
function read_json(string $file, $default = []) {
    $p = DATA_DIR . "/$file";
    if (!is_file($p)) return $default;
    $d = json_decode((string)file_get_contents($p), true);
    return is_array($d) ? $d : $default;
}

function write_json(string $file, $data): void {
    $p = DATA_DIR . "/$file";
    file_put_contents("$p.tmp", json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    rename("$p.tmp", $p);
}

function settings(): array { return read_json('settings.json'); }
function lang(): string { $l = (string)(settings()['lang'] ?? 'te'); return isset(LANGS[$l]) ? $l : 'te'; }
function voices_for(string $lang): array { return $lang === 'hi' ? array_merge(VOICES_HI, array_slice(VOICES, 2)) : VOICES; }
function is_setup(): bool { return !empty(settings()['password_hash']); }

// ---------- login ----------
function start_session(): void {
    $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    session_name('bgtivr');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => $dir === '' ? '/' : $dir . '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function logged_in(): bool { return !empty($_SESSION['ivr_admin']); }

function login_locked(): int {
    $l = read_json('login.json');
    $left = (int)($l['until'] ?? 0) - time();
    return $left > 0 ? $left : 0;
}

function login_failed(): void {
    $l = read_json('login.json');
    $fails = (int)($l['fails'] ?? 0) + 1;
    $until = $fails >= 8 ? time() + 600 : 0;
    write_json('login.json', ['fails' => $fails >= 8 ? 0 : $fails, 'until' => $until]);
}

// ---------- call log ----------
function calls_all(): array { return read_json('calls.json'); }

function call_find(string $id): ?array {
    foreach (calls_all() as $c) if (($c['id'] ?? '') === $id) return $c;
    return null;
}

function call_save(array $call): void {
    $all = calls_all();
    $found = false;
    foreach ($all as $i => $c) {
        if (($c['id'] ?? '') === $call['id']) { $all[$i] = $call; $found = true; break; }
    }
    if (!$found) array_unshift($all, $call);
    write_json('calls.json', array_slice($all, 0, MAX_CALLS));
}

function call_summary(array $c): array {
    $asked = '';
    foreach ($c['turns'] as $t) if ($t['who'] === 'caller') { $asked = $t['text']; break; }
    $end = $c['ended'] ?? $c['last'] ?? $c['started'];
    return [
        'id' => $c['id'],
        'started' => $c['started'],
        'asked' => $asked,
        'seconds' => max(0, $end - $c['started']),
        'flagged' => !empty($c['flagged']),
        'turns' => count($c['turns']),
    ];
}

// ---------- training (greeting, owner's rules, example answers, corrections) ----------
function clean_line($s, int $max): string {
    return mb_substr(trim(preg_replace('~\s+~u', ' ', (string)$s)), 0, $max);
}

function training(): array {
    $t = read_json('training.json');
    $hi = lang() === 'hi';   // each language keeps its own greeting; rules and examples are shared
    $greeting = clean_line($t[$hi ? 'greeting_hi' : 'greeting'] ?? '', 400);
    return [
        'lang' => lang(),
        'greeting' => $greeting !== '' ? $greeting : ($hi ? GREETING_HI : GREETING),
        'rules' => array_values(array_filter((array)($t['rules'] ?? []), 'is_string')),
        'examples' => array_values(array_filter((array)($t['examples'] ?? []), 'is_array')),
        'corrections' => array_values(array_filter((array)($t['corrections'] ?? []), 'is_array')),
    ];
}

function system_prompt(string $lang = 'te'): string {
    $t = training();
    $p = SYSTEM_PROMPT;
    if ($lang === 'hi') {
        $a = strpos($p, 'How to speak:'); $b = strpos($p, 'What to do on the call:');
        $p = substr($p, 0, $a) . SPEAK_HI . "\n" . substr($p, $b);
        $p = str_replace(OTHER_LANG_TE, OTHER_LANG_HI, $p);
    }
    if (!$t['rules'] && !$t['examples']) return $p;
    $p .= "\n\nThe owner of the business has trained you with the rules and examples below. If any of them conflicts with the instructions above, follow the owner's.";
    if ($t['rules']) $p .= "\n\nOwner's rules. Follow every one on every call:\n- " . implode("\n- ", $t['rules']);
    if ($t['examples']) {
        $p .= "\n\nHow the owner wants you to answer. Copy the style and the idea, not the exact words. An example may be written in another language; always reply in " . LANGS[$lang]['name'] . ":";
        foreach ($t['examples'] as $e) $p .= "\nCustomer: " . ($e['q'] ?? '') . "\nYou: " . ($e['a'] ?? '');
    }
    return $p;
}

// ---------- HTTP ----------
function http_post(string $url, array $headers, $body, int $timeout = 40): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => $timeout,
    ]);
    $out = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    $data = is_string($out) ? json_decode($out, true) : null;
    return ['code' => $code, 'data' => is_array($data) ? $data : [], 'err' => $err];
}

function sarvam_error(array $r, string $what): string {
    if ($r['code'] === 401 || $r['code'] === 403) return 'Sarvam rejected the API key. Check it in Settings.';
    if ($r['code'] === 429) return 'Sarvam says the credits or the rate limit are used up.';
    $e = $r['data']['error'] ?? null;
    $msg = is_array($e) ? ($e['message'] ?? '') : (is_string($e) ? $e : '');
    if ($msg === '' && $r['code'] === 0 && stripos($r['err'], 'timed out') !== false) return "$what took too long: Sarvam did not answer in time. Please try again.";
    if ($msg === '') $msg = $r['err'] !== '' ? $r['err'] : "HTTP {$r['code']}";
    return "$what failed: $msg";
}

// ---------- Sarvam ----------
function sarvam_key(): string { return (string)(settings()['sarvam_key'] ?? ''); }

// Caller audio (WAV) to text. Telugu and English words come back mixed, as spoken.
function sarvam_stt(string $wavPath): array {
    $r = http_post('https://api.sarvam.ai/speech-to-text',
        ['api-subscription-key: ' . sarvam_key()],
        ['model' => 'saaras:v3', 'mode' => 'codemix', 'file' => new CURLFile($wavPath, 'audio/wav', 'caller.wav')], 15);
    if ($r['code'] !== 200) return ['error' => sarvam_error($r, 'Listening')];
    return ['text' => trim((string)($r['data']['transcript'] ?? ''))];
}

// Conversation so far to the agent's next line.
function sarvam_chat(array $turns, string $lang = 'te'): array {
    $messages = [
        ['role' => 'system', 'content' => system_prompt($lang)],
        ['role' => 'user', 'content' => '(The call has just connected.)'],
    ];
    foreach ($turns as $t) {
        $messages[] = ['role' => $t['who'] === 'agent' ? 'assistant' : 'user', 'content' => $t['text']];
    }
    $key = sarvam_key();
    // The main model thinks before it answers, and that thinking uses up the same word allowance.
    // When the allowance ran out mid-thought the answer came back empty, so give it plenty of room.
    $ask = function (array $extra, int $limit) use ($key, $messages): array {
        $r = http_post('https://api.sarvam.ai/v1/chat/completions',
            ["Authorization: Bearer $key", "api-subscription-key: $key", 'Content-Type: application/json'],
            json_encode($extra + ['messages' => $messages, 'temperature' => 0.4, 'max_tokens' => 2000], JSON_UNESCAPED_UNICODE), $limit);
        if ($r['code'] !== 200) return ['error' => sarvam_error($r, 'Thinking')];
        $text = (string)($r['data']['choices'][0]['message']['content'] ?? '');
        $text = preg_replace('~<think>.*?(</think>|$)~su', '', $text);
        $text = trim(preg_replace('~\s+~u', ' ', str_replace(['*', '#', '`', '_'], '', $text)));
        return ['text' => $text, 'why' => (string)($r['data']['choices'][0]['finish_reason'] ?? '')];
    };
    // Sarvam's conversation model goes first: it answers directly, so it is the quick one.
    $quick = $ask(['model' => 'sarvam-105b-conversations'], 12);
    if (!isset($quick['error']) && $quick['text'] !== '') return ['text' => $quick['text']];
    // If it fails or says nothing, fall back to the main model, which thinks first and is slower.
    $first = $ask(['model' => 'sarvam-105b', 'reasoning_effort' => 'low'], 20);
    if (isset($first['error'])) return $first;
    if ($first['text'] !== '') return ['text' => $first['text']];
    $why = $first['why'] === 'length' ? 'it ran out of room while thinking' : ($first['why'] !== '' ? 'reason given: ' . $first['why'] : 'no reason given');
    return ['error' => "Thinking failed: the model sent an empty reply ($why)."];
}

// What is shown in the transcript is not always safe to read aloud: symbols, brackets and
// stray punctuation get spoken or cause odd pauses. This makes a clean copy for the voice only.
function speech_text(string $t): string {
    $t = preg_replace('~[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}\x{FE0F}\x{200D}]~u', '', $t);   // emoji
    $t = str_replace(['₹', 'Rs.', 'Rs ', '%', '&', '@'], ['rupees ', 'rupees ', 'rupees ', ' percent', ' and ', ' at '], $t);
    $t = str_replace(['"', '“', '”', '‘', '’', '(', ')', '[', ']', '{', '}', '<', '>', '|', '~', '^', '='], ' ', $t);
    $t = str_replace(['…', '...', '..'], '.', $t);
    $t = preg_replace('~\s+[-–—]+\s+~u', ', ', $t);       // a dash between words becomes a short pause
    $t = str_replace(['–', '—', '/', '\\', ';', ':'], [', ', ', ', ' ', ' ', ',', ','], $t);
    $t = preg_replace('~\s+([,.?!])~u', '$1', $t);            // no space before punctuation
    $t = preg_replace('~([,.?!])[,.?!]+~u', '$1', $t);        // one mark at a time
    $t = trim(preg_replace('~\s+~u', ' ', $t), " ,");
    if ($t !== '' && !preg_match('~[.?!]$~u', $t)) $t .= '.';
    return $t;
}

// Agent text to speech in the call's language. Returns base64 audio (MP3, about a tenth the size of WAV).
function sarvam_tts(string $text, string $speaker, string $lang = 'te'): array {
    $key = sarvam_key();
    $headers = ["api-subscription-key: $key", 'Content-Type: application/json'];
    $model = strpos($speaker, '_') !== false ? 'bulbul:v4-flash' : 'bulbul:v3';
    $body = ['text' => mb_substr(speech_text($text), 0, 2400), 'language_code' => LANGS[$lang]['code'] ?? 'te-IN', 'model' => $model,
        'speaker' => $speaker, 'speech_sample_rate' => 24000, 'output_audio_codec' => 'mp3', 'pace' => 1.15];
    $r = http_post('https://api.sarvam.ai/text-to-speech', $headers, json_encode($body, JSON_UNESCAPED_UNICODE), 15);
    if ($r['code'] === 400 || $r['code'] === 422) {
        // If compressed audio is refused, fall back to the default WAV.
        unset($body['output_audio_codec']);
        $r2 = http_post('https://api.sarvam.ai/text-to-speech', $headers, json_encode($body, JSON_UNESCAPED_UNICODE));
        if ($r2['code'] === 200) $r = $r2;
    }
    if ($r['code'] !== 200) return ['error' => sarvam_error($r, 'Speaking')];
    $audio = (string)($r['data']['audios'][0] ?? '');
    if ($audio === '') return ['error' => 'Speaking failed: no audio came back.'];
    return ['audio' => $audio];
}
