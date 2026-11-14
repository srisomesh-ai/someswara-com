<?php
// Bharat GPS Tracker AI call agent: shared helpers (settings, login, call log, Sarvam API).

// Where the settings, call log and training are kept. A deploy replaces the website folder and was wiping
// what was saved inside it, so the data lives next to the website folder, outside what a deploy touches.
// If that place cannot be used, it falls back to the data folder inside the dashboard.
function pick_data_dir(): string {
    $old = __DIR__ . '/data';
    $root = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
    if ($root !== '' && strpos(__DIR__, $root) === 0) {
        $new = dirname($root) . '/ivr-data';
        if ((@is_dir($new) || @mkdir($new, 0700, true)) && @is_writable($new)) {
            if (!@is_file("$new/settings.json")) {          // first time: bring along what is already saved
                foreach ((array)glob("$old/*.json") as $f) @copy($f, "$new/" . basename($f));
            }
            return $new;
        }
    }
    return $old;
}
define('DATA_DIR', pick_data_dir());
const MAX_CALLS = 500;
const LIVE_URL = 'wss://call.someswara.com/live';   // the live call server on the VPS (ivr/live/)
const PHONE_URL = 'wss://call.someswara.com/phone'; // where Exotel sends real phone calls on that server
const VOBIZ_URL = 'https://call.someswara.com/vobiz/answer';   // the "Answer URL" for a Vobiz application
// Said to a caller whose number is not on the test list while the line is in "test numbers only".
const BUSY_TE = 'నమస్తే, Bharat GPS Tracker కి call చేసినందుకు thanks. మా team మీకు వెంటనే call back చేస్తారు.';
const BUSY_HI = 'नमस्ते, Bharat GPS Tracker में call करने के लिए धन्यवाद. हमारी team आपको जल्दी call back करेगी.';
// Said on a phone call when a reply could not be prepared, so the caller is not left in silence.
const RETRY_TE = 'క్షమించండి, ఒకసారి మళ్ళీ చెప్తారా?';
const RETRY_HI = 'माफ़ कीजिए, एक बार फिर बताइए?';
const RETRY_EN = 'Sorry, could you say that once more?';
// On live calls she follows the caller's language. These are the languages she can switch between.
const LIVE_LANGS = ['te' => ['name' => 'Telugu', 'code' => 'te-IN'], 'hi' => ['name' => 'Hindi', 'code' => 'hi-IN'], 'en' => ['name' => 'English', 'code' => 'en-IN']];
const VOICE_EN = 'priya';   // voice used when a caller speaks English
const GREETING = 'నమస్తే, Bharat GPS Tracker కి call చేసినందుకు thanks. చెప్పండి, మీకు ఏ vehicle కి tracker కావాలి?';
const GREETING_HI = 'नमस्ते, Bharat GPS Tracker में call करने के लिए धन्यवाद. बताइए, आपको किस vehicle के लिए tracker चाहिए?';
const LANGS = ['te' => ['name' => 'Telugu', 'code' => 'te-IN'], 'hi' => ['name' => 'Hindi', 'code' => 'hi-IN']];
const VOICES_HI = ['aparna_hi_customer', 'aarti_hi_customer', 'shubh_hi_customer'];   // Sarvam's Hindi customer-care voices
const VOICES = ['kavitha_te_conversation', 'pooja_te_conversation', 'shubh', 'aditya', 'ritu', 'priya', 'neha', 'rahul', 'pooja', 'rohan', 'simran', 'kavya', 'amit', 'dev',
    'ishita', 'shreya', 'ratan', 'varun', 'manan', 'sumit', 'roopa', 'kabir', 'aayan', 'ashutosh', 'advait', 'anand',
    'tanya', 'tarun', 'sunny', 'mani', 'gokul', 'vijay', 'shruti', 'suhani', 'mohit', 'kavitha', 'rehan', 'soham', 'rupali'];

const SYSTEM_PROMPT = <<<'TXT'
You are the phone assistant of Bharat GPS Tracker, a GPS vehicle tracker company in Visakhapatnam. You answer incoming phone calls from new and existing customers. Everything you write is spoken aloud to the caller.

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
- First understand why they are calling. There are three kinds of caller: a new customer who wants a tracker, an existing customer whose GPS is not working, and an existing customer who wants to renew or to check their validity. If it is not clear, ask whether they want a new tracker or already have ours.
- Anyone who says my vehicle, my GPS, or asks to check, see or look at something for their vehicle already has our tracker. Never ask them whether they want a new tracker. When customers say license, validity, expiry, recharge, subscription, plan or renewal date, they all mean how long their GPS subscription is still valid.
- Recognise the vehicle from whatever name the caller uses and never ask for it again once they have said it. Activa, Jupiter, Access and scooty are scooters. Splendor, Pulsar, Bullet and Royal Enfield are bikes. Swift, Bolero, Innova, Creta and Scorpio are cars. Auto means auto rickshaw. Tata Ace, Bolero pickup, DCM, lorry, tipper and truck are goods vehicles. JCB, tractor and crane are heavy vehicles. If you do not know a name, ask only what type of vehicle it is.

New customer:
- Find out the vehicle and what it is used for. There are three kinds of use. Personal use: their own vehicle of any type, to see where it is and sometimes to watch how the driver drives. Commercial use: vehicles given on rent or run for business, where engine cut-off and extra security matter. Corporate use: company vehicles, from cars to heavy vehicles, which always need a GST bill, so tell them a GST bill is given.
- If their words already show the use, do not ask it. Otherwise ask in a natural way, for example whether it is their own vehicle or one given on rent.
- Every tracker comes with one year subscription, five years warranty and free installation by our technician. After the first year the yearly renewal is one thousand two hundred rupees.
- Explain to a new customer the one or two features that matter most for their use, in simple words, such as seeing live location in the mobile app, or switching off the engine from the phone for rented vehicles. Do not read out every feature at once.
{{PRICES}}
- A new customer has not decided yet, and your job is to help them decide, like a good sales professional. Lead the call one step at a time: first the vehicle and the use, then the one product that fits with its price, then answer their doubts, then offer to book the installation.
- After you answer a question from a new customer, move one step forward: give the one benefit that matters for their use, or ask the next useful question, such as when they would like it fitted. Do not leave the call hanging and do not try to end it.
- When they sound interested or have no more doubts, offer the booking yourself, for example ask whether you can book the installation for today or tomorrow. When they agree, take their name and area, once, and say our team will call to fix the time.
- If they hesitate or say they will think about it, do not pressure them. Give one honest reason that fits their use, and offer to send the details on WhatsApp to this number.

Existing customer, GPS not working or offline:
- Do not sell anything. Say sorry for the trouble, then ask these one at a time: since when it is offline; whether any service or repair work was done on the vehicle in recent days, or the battery was removed or changed; the vehicle number; their name; and where the vehicle is now.
- Then say a technician will be assigned and our team will call them to confirm the visit time.

Existing customer who wants to renew, or who wants to check the license, validity, expiry or recharge of their vehicle:
- They are already our customer, so do not ask which type of vehicle it is, where the vehicle is, or what it is used for.
- Say sure, and ask only the vehicle number.
{{VEHICLE}}- If they only wanted to check and you have no result to tell them, say the team will check and call them back.
- For renewal, ask which plan they want: three months, six months or one year. The one year plan is one thousand two hundred rupees. If they ask the price of the three or six month plan and you were not told it, say it will be in the payment link.
- When they have chosen, say you have noted it, that they will get the payment link very soon on WhatsApp and email, and that once they pay, the vehicle is renewed automatically.

Existing customer whose GPS has already stopped because it was not renewed for a long time:
- Explain the process simply: our technician visits, changes the SIM card and activates the GPS again so it comes back online.
- Take the vehicle number, their name and where the vehicle is, and say our team will call to fix the visit.

Always:
- Address the caller only as sir, madam, అండి or గారు with their name (in Hindi सर, मैडम or जी). Callers often call you అమ్మా, అన్నా, అక్కా, చెల్లి, బాబు, తల్లి, bro, dear, बेटा, भैया or दीदी. Never copy the word they use for you and never use any such family or friendly word for them. Stay polite and professional however casually they speak.
- Keep it short. Every reply is one or two short sentences, never more than about twenty words, and takes only a few seconds to say. Answer only what they just asked, then stop, or ask one short question. A long answer sounds like reading a script.
- Say a thing once. Do not repeat the price, subscription, warranty or free installation again later in the call unless they ask again. Do not finish every sentence with the same polite word.
- Never end a reply by asking whether they need any other help, anything else, or any other details. That sounds like you want to finish the call. Ask it at most once in the whole call, and only when their work is completely finished, such as after a booking, a renewal or a complaint is noted.
- Never invent a feature, a price, a charge or a promise. For any charge you were not told, say the team will confirm it.
- Do not repeat a vehicle number back to the caller unless you are unsure of a letter or a digit, or a check result tells you to. When you do say a vehicle number, it is always said in English, one letter and one digit at a time, whatever language the call is in. Write it exactly like this: A P, three nine, W A, one five seven five. Never say it as Telugu or Hindi number words, and never join the digits into a big number.
- Callers say the number in many ways: letters in any language, digits one by one or in pairs like fifteen seventy five or పదిహేను డెబ్బై ఐదు. An Indian vehicle number is two letters for the state, two digits, one to three letters, then four digits. Work out the number in that shape before you repeat it.
- If the caller is angry or asks something you do not know, say our team will call them back shortly on this number.
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
// The same instructions when the caller speaks English.
const SPEAK_EN = <<<'TXT'
How to speak:
- Speak like a friendly Indian customer care person: simple, everyday Indian English, the way people really talk on the phone. Short, plain words.
- Reply in one or two short sentences, about twenty words at most. Ask only one question at a time.
- Begin most replies with a small natural reaction to what the caller just said: okay, sure, got it, alright, of course. Change it each time and never use the same one twice in a row.
- Be warm and relaxed, not like reading a script. Use sir or madam only after the caller's voice or words make it clear.
- If the caller only says hello or something unclear, respond naturally and gently repeat your question in different words.
- No lists, no symbols, no emojis, no formatting. Write numbers in words.

TXT;
// On a live call the server works out the caller's language for every reply and picks these instructions to match.
const LIVE_LANG_RULE = '- Answer in %s now. The caller may change between Telugu, Hindi and English at any time; you are always given the instructions for the language they just spoke. If they speak any other language, tell them politely that a team member will call them back.';
// Added only when the owner himself is on the line and has switched on "Train by voice".
const TRAIN_BLOCK = <<<'TXT'


TRAINING MODE. The person on this call is the owner of the business, and he is training you. He acts as a customer. Sometimes he steps out of that role to correct you or teach you, for example: "no, not like that", "wrong", "you should say", "remember this", "అలా కాదు", "ऐसे नहीं". When he does:
1. Stop the sales talk. In one short sentence, say back what you understood he wants you to do from now on, and ask whether you should remember it.
2. Only after he clearly says yes, give a very short confirmation, and then at the very end of your reply add the rule in exactly this form: [[RULE: the rule as one clear sentence, in the language he used]]
3. If he says no, or corrects what you understood, go back to step 1.
Never write [[RULE: ...]] unless he has just said yes. The tag is not spoken; do not mention it. After that, wait for him to carry on as a customer.
TXT;
const NO_PRICES = '- The price list is not loaded yet. Do not say any price, model name, offer or discount. Say you will send the full details and price on WhatsApp to this same number.';
const HAS_PRICES = '- Our products and prices are listed at the end. Each price is the final price: say exactly that amount and add nothing to it. Once you know the vehicle and the need, suggest the one product from the list that fits that vehicle and need best, with its price and what the price includes. Never read out the whole list. Say a price in rupees, in words, the way people say it on the phone. Say only a product, price or charge that is in the list. For a discount, an offer or anything not in the list, say the team will confirm it on WhatsApp on this same number.';
const LEAD_EMAIL = 'info@bharatgpstracker.com';                         // where the summary of every phone call goes
const VEHICLE_CHECK = "- As soon as they have said the vehicle number, do not repeat the number back and do not ask them to confirm it. Say only a few words that you are checking, like one minute, I will check, and end that same reply with the tag [[VEHICLE: NUMBER]], where NUMBER is the vehicle number in capital English letters and digits with no spaces, for example [[VEHICLE: AP31TX4521]]. Use plain square brackets. The tag is never spoken. Do not say or ask anything else in that reply. The result of the check then comes to you: tell the caller what it says and follow it. Offer renewal only when the result says less than thirty days are left or it has expired; when thirty days or more are left, skip the plan and payment steps below. Write the tag only once for each number.\n";
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
    // Each save writes its own temporary file, then swaps it in whole. Two saves at the same moment
    // (a phone call and the dashboard) can no longer leave a half-written file behind.
    $tmp = $p . '.' . bin2hex(random_bytes(4)) . '.tmp';
    if (file_put_contents($tmp, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) === false || !rename($tmp, $p)) @unlink($tmp);
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
        'phone' => !empty($c['phone']),
        'from' => (string)($c['from'] ?? ''),
        'answered' => !isset($c['answered']) || $c['answered'],
    ];
}

// ---------- phone line ----------
// A phone number as its last ten digits, so +91, 0 and spaces do not matter when comparing.
function phone_digits($n): string { return substr(preg_replace('~\D~', '', (string)$n), -10); }

function phone_view(array $s): array {
    return [
        'url' => PHONE_URL . '?key=' . ($s['phone_key'] ?? ''),
        'vobiz_url' => VOBIZ_URL . '?key=' . ($s['phone_key'] ?? ''),
        'mode' => ($s['phone_mode'] ?? 'test') === 'all' ? 'all' : 'test',
        'tests' => implode("\n", (array)($s['phone_tests'] ?? [])),
        'last' => (int)($s['phone_last'] ?? 0),
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
        'learned' => (object)array_filter((array)($t['learned'] ?? []), 'is_int'),   // rule text => when she learned it on a call
        'train_on' => !empty(settings()['train_on']),
        'train_nums' => implode("\n", (array)(settings()['train_nums'] ?? [])),
    ];
}

function voice_for_lang(string $l): string {
    $s = settings();
    if ($l === 'en') return VOICE_EN;
    if ($l === 'hi') { $v = (string)($s['voice_hi'] ?? ''); return in_array($v, voices_for('hi'), true) ? $v : VOICES_HI[0]; }
    $v = (string)($s['voice'] ?? '');
    return in_array($v, VOICES, true) ? $v : VOICES[0];
}

// Everything the live call server needs to run one call.
function live_payload(string $lang, bool $trainer): array {
    $prompts = []; $voices = []; $codes = [];
    foreach (LIVE_LANGS as $l => $info) {
        $prompts[$l] = system_prompt($l, true, $trainer);
        $voices[$l] = voice_for_lang($l);
        $codes[$l] = $info['code'];
    }
    return ['key' => sarvam_key(), 'lang' => $lang, 'lang_code' => $codes[$lang], 'voice' => $voices[$lang], 'prompt' => $prompts[$lang],
        'prompts' => $prompts, 'voices' => $voices, 'codes' => $codes, 'trainer' => $trainer,
        'retry' => ['te' => RETRY_TE, 'hi' => RETRY_HI, 'en' => RETRY_EN], 'retry_text' => $lang === 'hi' ? RETRY_HI : RETRY_TE];
}

function system_prompt(string $lang = 'te', bool $live = false, bool $trainer = false): string {
    $t = training();
    $p = SYSTEM_PROMPT;
    if ($lang === 'hi' || $lang === 'en') {
        $a = strpos($p, 'How to speak:'); $b = strpos($p, 'What to do on the call:');
        $p = substr($p, 0, $a) . ($lang === 'hi' ? SPEAK_HI : SPEAK_EN) . "\n" . substr($p, $b);
    }
    $rule = $live ? sprintf(LIVE_LANG_RULE, LIVE_LANGS[$lang]['name']) : ($lang === 'hi' ? OTHER_LANG_HI : OTHER_LANG_TE);
    $p = str_replace(OTHER_LANG_TE, $rule, $p);
    $p = str_replace('{{VEHICLE}}', $live && gps_on() ? VEHICLE_CHECK : '', $p);
    $cat = catalog();
    $p = str_replace('{{PRICES}}', $cat === '' ? NO_PRICES : HAS_PRICES, $p);
    if ($cat !== '') $p .= "\n\n" . $cat;
    $notes = trim(products()['notes']);
    if ($notes !== '') $p .= "\n\nOther charges and notes from the owner. Say these only when they come up:\n" . $notes;
    if ($trainer) $p .= TRAIN_BLOCK;
    if (!$t['rules'] && !$t['examples']) return $p;
    $p .= "\n\nThe owner of the business has trained you with the rules and examples below. If any of them conflicts with the instructions above, follow the owner's.";
    if ($t['rules']) $p .= "\n\nOwner's rules. Follow every one on every call:\n- " . implode("\n- ", $t['rules']);
    if ($t['examples']) {
        $p .= "\n\nHow the owner wants you to answer. Copy the style and the idea, not the exact words. An example may be written in another language; always reply in " . LIVE_LANGS[$lang]['name'] . ":";
        foreach ($t['examples'] as $e) $p .= "\nCustomer: " . ($e['q'] ?? '') . "\nYou: " . ($e['a'] ?? '');
    }
    return $p;
}

// ---------- products and prices ----------
// A price the way it is said on the phone: 1769 -> "one thousand seven hundred sixty nine".
function num_words(int $n): string {
    $one = ['', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten', 'eleven', 'twelve', 'thirteen', 'fourteen',
        'fifteen', 'sixteen', 'seventeen', 'eighteen', 'nineteen'];
    $ten = ['', '', 'twenty', 'thirty', 'forty', 'fifty', 'sixty', 'seventy', 'eighty', 'ninety'];
    if ($n <= 0) return 'zero';
    $w = [];
    foreach ([[100000, 'lakh'], [1000, 'thousand'], [100, 'hundred']] as [$size, $name]) {
        if ($n >= $size) { $w[] = num_words(intdiv($n, $size)) . ' ' . $name; $n %= $size; }
    }
    if ($n >= 20) { $w[] = trim($ten[intdiv($n, 10)] . ' ' . $one[$n % 10]); }
    elseif ($n > 0) $w[] = $one[$n];
    return implode(' ', $w);
}

// The owner's own price list (Products and prices page) as text for her instructions. '' means no product yet.
const FITS = ['Bike', 'Scooter', 'Car', 'Auto', 'Truck', 'Bus', 'Tractor or JCB', 'Person, bag or pet'];
function products(): array {
    $d = read_json('products.json');
    $list = [];
    foreach ((array)($d['products'] ?? []) as $p) if (is_array($p) && ($p['name'] ?? '') !== '' && (int)($p['price'] ?? 0) > 0) $list[] = $p;
    return ['products' => $list, 'notes' => (string)($d['notes'] ?? ''), 'fits' => FITS];
}

// One time only: copy every product from bharatgpstracker.com into the owner's list, for him to edit and delete.
// Returns how many were added, or -1 when the website could not be read (it is tried again next time).
const SITE_FEED = 'https://bharatgpstracker.com/api.php?action=site';
function import_site_products(): int {
    $d = read_json('products.json');
    if (!empty($d['site_import'])) return 0;
    $ch = curl_init(SITE_FEED);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 10, CURLOPT_FOLLOWLOCATION => true]);
    $raw = curl_exec($ch);
    curl_close($ch);
    $site = is_string($raw) ? json_decode($raw, true) : null;
    if (!is_array($site) || empty($site['products']) || !is_array($site['products'])) return -1;
    $words = ['Bike' => 'bike|motorcycle', 'Scooter' => 'scooter|scooty', 'Car' => '\bcars?\b', 'Auto' => '\bautos?\b|rickshaw', 'Truck' => 'truck|lorry',
        'Bus' => '\bbus(es)?\b', 'Tractor or JCB' => 'tractor|jcb', 'Person, bag or pet' => 'bag|helmet|id card|\bpets?\b|\bkids?\b|child|person|portable'];
    $list = array_values(array_filter((array)($d['products'] ?? []), 'is_array'));
    $have = array_map(fn($x) => mb_strtolower((string)($x['name'] ?? '')), $list);
    $added = 0;
    foreach ($site['products'] as $p) {
        if (!is_array($p)) continue;
        $base = clean_line($p['name'] ?? '', 80);
        $types = [];
        foreach ((array)($p['variants'] ?? []) as $v) if (is_array($v) && (int)($v['p'] ?? 0) > 0) $types[] = [clean_line($v['n'] ?? '', 40), (int)$v['p']];
        if (!$types && (int)($p['price'] ?? 0) > 0) $types[] = ['', (int)$p['price']];
        if ($base === '' || !$types) continue;
        $about = clean_line($p['subtitle'] ?? '', 200);
        $feat = [];
        foreach ((array)($p['simple'] ?? []) as $line) { $line = clean_line(is_string($line) ? $line : '', 120); if ($line !== '' && count($feat) < 8) $feat[] = $line; }
        $text = mb_strtolower($base . ' ' . ($p['cat'] ?? '') . ' ' . $about . ' ' . implode(' ', $feat));
        $fits = [];
        foreach ($words as $fit => $re) if (preg_match('~' . $re . '~u', $text)) $fits[] = $fit;
        foreach ($types as [$type, $price]) {
            $name = clean_line(count($types) > 1 && $type !== '' ? $base . ' - ' . $type : $base, 80);
            if (in_array(mb_strtolower($name), $have, true) || count($list) >= 40) continue;
            $list[] = ['id' => bin2hex(random_bytes(6)), 'name' => $name, 'price' => $price, 'fits' => $fits, 'for' => $about, 'inc' => '', 'feat' => $feat];
            $have[] = mb_strtolower($name);
            $added++;
        }
    }
    $d['products'] = $list;
    $d['site_import'] = time();
    write_json('products.json', $d);
    return $added;
}

function catalog(): string {
    $rows = [];
    foreach (products()['products'] as $p) {
        $line = '- ' . $p['name'] . ': Rs ' . (int)$p['price'] . ' (' . num_words((int)$p['price']) . ').';
        if (!empty($p['fits'])) $line .= ' Fits: ' . implode(', ', (array)$p['fits']) . '.';
        if (($p['for'] ?? '') !== '') $line .= ' Best for: ' . rtrim($p['for'], '.') . '.';
        if (($p['inc'] ?? '') !== '') $line .= ' Price includes: ' . rtrim($p['inc'], '.') . '.';
        if (!empty($p['feat'])) $line .= ' Features: ' . implode('. ', array_map(fn($x) => rtrim((string)$x, '.'), (array)$p['feat'])) . '.';
        $rows[] = $line;
    }
    return $rows ? "Products and prices (the owner's own list for phone customers):\n" . implode("\n", $rows) : '';
}

// ---------- GPS servers ----------
// The three tracking servers (GPSWOX). She only reads from them: one request per server, the vehicle list.
// The keys are typed in the dashboard and kept in settings.json, never in this code.
const GPS_SERVERS = [1 => 'bharatgps.com', 2 => 'bharatgps.in', 3 => 'bharatgps.school'];
function gps_keys(): array {
    $k = (array)(settings()['gps_keys'] ?? []);
    $out = [];
    foreach (GPS_SERVERS as $id => $host) $out[$id] = trim((string)($k[$id] ?? ''));
    return $out;
}
function gps_on(): bool { return (bool)array_filter(gps_keys()); }
function plate_key(string $t): string { return preg_replace('~[^A-Z0-9]~', '', strtoupper($t)); }

// A small copy of every server's vehicle list (number, expiry, last signal), refreshed when older than ten minutes.
function gps_index(bool $fresh = false): array {
    $c = read_json('gps.json');
    if (!$fresh && is_array($c) && ($c['at'] ?? 0) > time() - 600) return $c;
    $keys = array_filter(gps_keys());
    $multi = curl_multi_init(); $hs = [];
    foreach ($keys as $id => $key) {
        $ch = curl_init('https://' . GPS_SERVERS[$id] . '/api/get_devices?lang=en&user_api_hash=' . rawurlencode($key));
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_CONNECTTIMEOUT => 6, CURLOPT_TIMEOUT => 18, CURLOPT_USERAGENT => 'Mozilla/5.0']);
        curl_multi_add_handle($multi, $ch); $hs[$id] = $ch;
    }
    do { curl_multi_exec($multi, $run); if ($run) curl_multi_select($multi, 1.0); } while ($run > 0);
    $old = is_array($c) ? $c : [];
    $new = ['at' => time(), 'servers' => [], 'dev' => []];
    foreach (GPS_SERVERS as $id => $host) {
        if (!isset($hs[$id])) { $new['servers'][$id] = ['state' => 'nokey']; continue; }
        $body = curl_multi_getcontent($hs[$id]); $code = curl_getinfo($hs[$id], CURLINFO_HTTP_CODE);
        curl_multi_remove_handle($multi, $hs[$id]); curl_close($hs[$id]);
        $data = is_string($body) ? json_decode($body, true) : null;
        $groups = is_array($data) && ($data === [] || isset($data[0])) ? $data : null;
        if ($groups === null) {
            // Not readable just now: say why, and keep this server's last good list so calls still work.
            $new['servers'][$id] = ['state' => ($code === 401 || $code === 403 || (is_array($data) && isset($data['status']) && !$data['status'])) ? 'badkey' : 'down', 'count' => 0];
            foreach ((array)($old['dev'] ?? []) as $d) if (($d[0] ?? 0) === $id) $new['dev'][] = $d;
            continue;
        }
        $n = 0;
        foreach ($groups as $g) foreach ((array)($g['items'] ?? []) as $dv) {
            if (!is_array($dv)) continue;
            $dd = (array)($dv['device_data'] ?? []);
            $exp = '';
            foreach (['expiration_date', 'sim_expiration_date'] as $f) { $v = substr((string)($dd[$f] ?? ''), 0, 10); if (preg_match('~^[12]\d{3}-\d\d-\d\d$~', $v)) { $exp = $v; break; } }
            $name = clean_line($dv['name'] ?? '', 80);
            $new['dev'][] = [$id, plate_key($name), plate_key((string)($dd['plate_number'] ?? '')), $name, $exp, clean_line((string)($dv['time'] ?? ($dd['time'] ?? '')), 30)];
            $n++;
        }
        $new['servers'][$id] = ['state' => 'ok', 'count' => $n];
    }
    curl_multi_close($multi);
    write_json('gps.json', $new);
    return $new;
}

// Find one vehicle number on the servers. status: found, none, many (several vehicles match), off (no key yet), down.
function gps_find(string $number): array {
    $q = plate_key($number);
    if (!gps_on()) return ['status' => 'off'];
    if (strlen($q) < 4) return ['status' => 'none', 'number' => $q];
    $ix = gps_index();
    $exact = []; $part = [];
    foreach ((array)($ix['dev'] ?? []) as $d) {
        if ($d[1] === $q || $d[2] === $q) $exact[] = $d;
        elseif (strlen($q) >= 6 && (strpos($d[1], $q) !== false || ($d[2] !== '' && strpos($d[2], $q) !== false))) $part[] = $d;
    }
    $hit = $exact ?: $part;
    $near = false;
    if (!$hit && ($want = plate_parts($q))) {
        // Heard over the phone, one letter is easily wrong (W for V, B for D). If exactly one vehicle has the same
        // state, district and four-digit number and differs only in the letters between, or only in the district
        // number, take it as the closest match and let her confirm the real number with the caller.
        $series = []; $district = [];
        foreach ((array)($ix['dev'] ?? []) as $d) {
            foreach ([$d[1], $d[2]] as $k) {
                $have = $k !== '' ? plate_parts($k) : null;
                if (!$have || $have[0] !== $want[0] || $have[3] !== $want[3]) continue;
                if ($have[1] === $want[1] && $have[2] === $want[2]) { $exact[] = $d; break; }   // same number written with or without zeros
                if ($have[1] === $want[1] && strlen($have[2]) === strlen($want[2]) && levenshtein($have[2], $want[2]) <= 1) { $series[] = $d; break; }
                if ($have[2] === $want[2] && $want[2] !== '') { $district[] = $d; break; }
            }
        }
        $hit = $exact ?: (count($series) === 1 ? $series : (!$series && count($district) === 1 ? $district : []));
        $near = !$exact && (bool)$hit;
    }
    if (!$hit) {
        $down = array_filter((array)($ix['servers'] ?? []), fn($x) => in_array($x['state'] ?? '', ['down', 'badkey'], true));
        return ['status' => $down ? 'down' : 'none', 'number' => $q];
    }
    if (count($hit) > 1) return ['status' => 'many', 'number' => $q, 'count' => count($hit)];
    [$id, , , $name, $exp, $last] = $hit[0];
    $r = ['status' => 'found', 'number' => $q, 'name' => $name, 'server' => $id, 'host' => GPS_SERVERS[$id], 'expiry' => $exp, 'last' => $last, 'near' => $near];
    if ($exp !== '') {
        $r['days'] = (int)(new DateTime('today'))->diff(new DateTime($exp))->format('%r%a');
        $r['expiry_text'] = date('j F Y', strtotime($exp));
    }
    return $r;
}

// AP39WA1575 -> [AP, 39, WA, 1575]; null when the text is not a vehicle number.
function plate_parts(string $k): ?array {
    return preg_match('~([A-Z]{2})0?(\d{1,2})([A-Z]{0,3})0*(\d{1,4})(?!\d)~', $k, $m) ? [$m[1], (string)(int)$m[2], $m[3], (string)(int)$m[4]] : null;
}

// The result in words, for her.
function gps_result_text(array $r): string {
    $n = $r['number'] ?? '';
    if ($r['status'] === 'found' && !empty($r['near'])) {
        $exp = ($r['expiry'] ?? '') !== '' ? " If they say yes, its expiry date is {$r['expiry_text']}" . ($r['days'] < 0 ? ', so it has already expired' : '')
            . ($r['days'] >= 30 ? ': thirty days or more are left, so say renewal is not needed now and do not ask for a plan.' : ': less than thirty days are left, so ask which plan they want.')
            : ' If they say yes, say the team will confirm the expiry date, and continue.';
        return "$n itself is not on our server, but there is one very close number: {$r['name']}. Ask the caller whether {$r['name']} is their vehicle number, saying it letter by letter.$exp If they say no, ask them to say the number once more.";
    }
    switch ($r['status']) {
        case 'found':
            if (($r['expiry'] ?? '') === '') return "Vehicle $n is on our server, but no expiry date is recorded for it. Tell the caller the vehicle is found and that the team will confirm the expiry date, then continue.";
            $d = $r['days'];
            $when = $d > 1 ? "$d days from today" : ($d === 1 ? 'tomorrow' : ($d === 0 ? 'today' : ($d === -1 ? 'yesterday, so it has already expired' : 'which was ' . (-$d) . ' days ago, so it has already expired')));
            $next = $d >= 30
                ? 'There are thirty days or more left, so renewal is not needed now. Tell the caller the expiry date and that the vehicle is still valid, and that they can renew when it is closer to that date. Do not ask which plan they want and do not push renewal.'
                : 'There are less than thirty days left, so tell the caller the expiry date and ask which plan they want to renew with.';
            return "Vehicle $n is found on our server. Its expiry date is {$r['expiry_text']}, $when. $next";
        case 'many': return "More than one vehicle matches $n. Ask the caller to say the full vehicle number once more, slowly.";
        case 'down': return "The server could not be checked just now. Do not say the vehicle is missing. Say you could not check at the moment and continue without the expiry date.";
        default: return "Vehicle $n is not on any of our servers. Tell the caller that this vehicle number is not in our records, which means its GPS expired earlier and was removed from the server. Then explain the two choices: adding it again by re-installing the same GPS costs one thousand seven hundred rupees; or, if that GPS no longer works, a new GPS is better, which costs three thousand three hundred rupees. Ask which they prefer. Only if the caller says the number you checked was wrong, take the right number and check that.";
    }
}

// ---------- lead email ----------
// One email to the team after every real phone call: who called, what they asked, and the full talk.
function lead_mail(array $c): bool {
    $from = (string)($c['from'] ?? '');
    $num = $from !== '' ? '+91 ' . substr($from, 0, 5) . ' ' . substr($from, 5) : 'Number not shown';
    $s = call_summary($c);
    $answered = !isset($c['answered']) || $c['answered'];
    $spoke = $s['asked'] !== '';
    $what = !$answered ? 'Missed call, she asked them to wait for a call back' : ($spoke ? 'New call lead' : 'Caller hung up without speaking');
    $body = "$what\n\nCaller: $num\nTime: " . date('j M Y, g:i A', (int)$c['started']) . "\nLength: {$s['seconds']} seconds\n";
    if ($from !== '') $body .= "Call back: tel:+91$from\nWhatsApp: https://wa.me/91$from\n";
    if ($spoke) $body .= "First thing they said: {$s['asked']}\n";
    $body .= "\nThe call:\n";
    foreach ($c['turns'] as $t) $body .= ($t['who'] === 'agent' ? 'Agent: ' : 'Caller: ') . $t['text'] . "\n";
    $host = preg_replace('~[^a-z0-9.-]~', '', strtolower(preg_replace('~^www\.|:\d+$~', '', (string)($_SERVER['HTTP_HOST'] ?? '')))) ?: 'someswara.com';
    $subject = '=?UTF-8?B?' . base64_encode("$what: $num") . '?=';
    $headers = "From: Bharat GPS call agent <noreply@$host>\r\nMIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64";
    return function_exists('mail') && @mail(LEAD_EMAIL, $subject, chunk_split(base64_encode($body)), $headers);
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
