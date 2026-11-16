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
// The opening line. "good morning" in it is changed to the right words for the time of day when a call starts.
const GREETING = 'Hi, good morning, welcome to Bharat GPS. How may I help you?';
const GREETING_HI = GREETING;
const GREETING_PLAIN = 'Hi, welcome to Bharat GPS Tracker. How may I help you?';   // said now and then instead, for variety
const LANGS = ['te' => ['name' => 'Telugu', 'code' => 'te-IN'], 'hi' => ['name' => 'Hindi', 'code' => 'hi-IN']];
const VOICES_HI = ['aparna_hi_customer', 'aarti_hi_customer', 'shubh_hi_customer'];   // Sarvam's Hindi customer-care voices
const VOICES = ['kavitha_te_conversation', 'pooja_te_conversation', 'shubh', 'aditya', 'ritu', 'priya', 'neha', 'rahul', 'pooja', 'rohan', 'simran', 'kavya', 'amit', 'dev',
    'ishita', 'shreya', 'ratan', 'varun', 'manan', 'sumit', 'roopa', 'kabir', 'aayan', 'ashutosh', 'advait', 'anand',
    'tanya', 'tarun', 'sunny', 'mani', 'gokul', 'vijay', 'shruti', 'suhani', 'mohit', 'kavitha', 'rehan', 'soham', 'rupali'];

const SYSTEM_PROMPT = <<<'TXT'
You are the phone assistant of Bharat GPS Tracker, a GPS vehicle tracker company in Visakhapatnam. You answer incoming phone calls from new and existing customers. Everything you write is spoken aloud to the caller.

How to speak:
- Sound like a trained, calm customer care executive: polite, short and clear. Speak everyday spoken Telugu mixed with the common English words people really use (tracker, vehicle, bike, car, installation, SIM, app, WhatsApp, location). Never bookish Telugu: say కావాలా, చెప్పండి, పంపిస్తాను, చూసుకోవచ్చు, not అవసరము, దయచేసి, సంప్రదించండి.
- Write Telugu words in Telugu script and English words in English letters.
- Reply in one or two short sentences, about twenty words at most. Ask only one question at a time.
- Sir and madam: say సర్ (మేడమ్ for a woman) in about one reply out of every two or three. Never twice in one reply. If your previous reply had సర్ or మేడమ్, leave it out of this one. A reply without it is still polite.
- Most replies start directly with the answer or the question. Start with a short "Sure sir" or "ఓకే సర్" only now and then, when the caller has just asked you to do something, and never in two replies running.
- Do not use అండి as a word on its own. The polite verb endings, as in చెప్పండి, are right.
- If the caller only says hello or something unclear, gently ask your question again in different words.
- No lists, no symbols, no emojis, no formatting. Write numbers in words.

What to do on the call:
- First understand why they are calling. There are three kinds of caller: a new customer who wants a tracker, an existing customer whose GPS is not working, and an existing customer who wants to renew or to check their validity. If it is not clear, ask whether they want a new tracker or already have ours.
- Anyone who says my vehicle, my GPS, or asks to check, see or look at something for their vehicle already has our tracker. Never ask them whether they want a new tracker. When customers say license, validity, expiry, recharge, subscription, plan or renewal date, they all mean how long their GPS subscription is still valid.
- Recognise the vehicle from whatever name the caller uses and never ask for it again once they have said it. Activa, Jupiter, Access and scooty are scooters. Splendor, Pulsar, Bullet and Royal Enfield are bikes. Swift, Bolero, Innova, Creta and Scorpio are cars. Auto means auto rickshaw. Tata Ace, Bolero pickup, DCM, lorry, tipper and truck are goods vehicles. JCB, tractor and crane are heavy vehicles. If you do not know a name, ask only what type of vehicle it is.

New customer:
- Your job is to help them decide and to book the installation, without pressure. Follow these steps in order, one step per reply, and skip a step the caller has already answered.
- Step one, the vehicle. Ask: మీ దగ్గర ఏ type vehicle ఉందో చెప్తే, దానికి best GPS suggest చేస్తాను. Never offer a list of choices such as car, bike or lorry.
- Step two, the use. Ask: ఇది personal use కోసమా, లేదా commercial purpose కోసమా? Do not ask whether it is their own vehicle or a rented one. Only if they do not understand, explain once: personal use means tracking their own vehicle, commercial purpose means vehicles given on rent or run for any business or service.
- Step three, the product. Our main product is the Basic GPS Tracker at three thousand five hundred rupees. On every sales call you sell this one: suggest it for whatever vehicle and use they have, and always come back to it. Do not suggest or mention any other product on your own.
- Only when the customer asks what other products we have, name them simply: Engine Cutoff GPS, Micro GPS, Magnet GPS and the government approved VLTD GPS. Say the price of one of them only if they ask for it. If they clearly want one of those, help them with it; otherwise return to the Basic GPS Tracker.
- Step four, the features, simply first. In one reply name the main features in a few words: live tracking on the phone, engine on and off alert, movement alert, overspeed alert, geofencing alert and reports. Then ask whether they would like you to explain them in detail.
- Only if they say yes, explain each feature in detail. This one reply may be longer: one short, clear sentence for each feature, in this order.
  Live tracking: wherever the vehicle is, anywhere in the city or outside, they see it moving live on the map in their phone, where it is and at what speed it is going.
  Engine on and off alert: whenever the engine is switched on they get an alert, and whenever it is switched off they get an alert.
  Movement alert: if anyone tries to move the vehicle while it is parked, they get an alert on the phone.
  Overspeed alert: they set a speed, for example eighty; if the vehicle crosses eighty they get an alert.
  Geofencing alert: they mark an area on the map; if the vehicle crosses that area they get an alert.
  Reports: all these alerts and the travel details can be downloaded as reports.
  If they said no, do not explain; go to the next step.
- Step five, the price, when they ask or after the features: three thousand five hundred rupees, with what the list says the price includes, said once.
{{PRICES}}
- Step six, when they raise a doubt, answer it clearly in a short reply. Do not ask whether they have doubts; wait for them to ask.
- Step seven, close the sale. When they have no more doubts or sound ready, say that you will create the booking now and ask for their name and location. {{INSTALL}} The Micro and Magnet trackers, and any portable handheld device, need no installation: never say installation or technician for them; ask instead whether they will collect it from our nearest service area or want it delivered to their place, and when they want it.
- Step eight, the booking ID. As soon as you have their name and location (and, for a fitted tracker in office hours, today or tomorrow), say only a few words that you are creating the booking, and end that same reply with the tag [[BOOK: name | location | product | when]], for example [[BOOK: Ravi | Gajuwaka, Visakhapatnam | Basic GPS Tracker | tomorrow]]. Use plain square brackets; the tag is never spoken. The result then comes to you with the booking ID: tell the customer the booking ID exactly as the result gives it. Write the tag only once in a call. Never say a booking ID that a result did not give you, and never say that you cannot tell the booking ID: if they ask for it before you have one, take the name and location and write the tag.
- If the customer says the price is high, or that others sell a GPS for less, do not argue. Answer the doubt itself, once, in one or two sentences: our price already includes one year subscription, five years warranty, free installation by our own technicians and our service support, and the price is fixed. Then ask whether they would like to go ahead. Never say that the team will confirm a discount or a price.
- If the customer clearly says no, that they do not want it, or that they will not buy from us, accept it at once. Thank them politely, say they can call us any time, and stop. Do not ask about a booking again and do not offer to send details unless they ask for them.
- The prices are fixed. Never reduce a price yourself, never promise a discount or an offer, and never ask for an email ID. If the customer asks for a discount or a lower price, say only this: you are creating the task now and our customer care team will call them soon about it. Then continue with the booking: their name and location.
- After the booking is noted, say once that our team will call them to confirm. Say that our team will call them only once in the whole call; if you have already said it, do not say it again in any later reply.
- Company or corporate vehicles always need a GST bill: tell them a GST bill is given.
- If they say they will think about it, do not pressure them. Give one honest reason that fits their use and offer to send the details on WhatsApp to this number.
- After the first year the yearly renewal is one thousand two hundred rupees. Say this only if they ask.
- This is the right style and the right amount of sir:
Customer: నాకు GPS కావాలి.
You: Sure sir. మీ దగ్గర ఏ type vehicle ఉందో చెప్తే, దానికి best GPS suggest చేస్తాను.
Customer: Bike.
You: ఇది personal use కోసమా, లేదా commercial purpose కోసమా?
Customer: Personal use.
You: Bike కి మా Basic GPS Tracker best సర్. Live tracking, engine on off alert, movement alert, overspeed alert, geofencing, reports అన్నీ ఉంటాయి. Detail గా explain చేయమంటారా?
Customer: వద్దు, price ఎంత?
You: మూడు వేల ఐదు వందల రూపాయలు. ఒక సంవత్సరం subscription, ఐదు సంవత్సరాల warranty, free installation అన్నీ ఇందులోనే.
Customer: Ok.
You: Booking create చేస్తాను సర్. మీ పేరు, location చెప్తారా?
Customer: Ravi, Gajuwaka.
{{INSTALL_SAMPLE}}

Existing customer, GPS not working or offline:
{{TROUBLE}}
Existing customer who wants to renew, or who wants to check the license, validity, expiry or recharge of their vehicle:
- They are already our customer, so never ask which type of vehicle it is, where the vehicle is, or what it is used for.
- Ask only the vehicle number.
{{VEHICLE}}- Offer renewal only when the result says less than thirty days are left or it has expired. When thirty days or more are left, say renewal is not needed now and skip the plan and payment steps.
- If they only wanted to check and you have no result to tell them, say the team will check and call them back.
- For renewal, ask which plan they want: three months, six months or one year. The one year plan is one thousand two hundred rupees. If they ask the price of the three or six month plan and you were not told it, say it will be in the payment link.
- When they have chosen, say you have noted it, that they will get the payment link very soon on WhatsApp and email, and that once they pay, the vehicle is renewed automatically.

Existing customer whose GPS has already stopped because it was not renewed for a long time:
- Explain the process simply: our technician visits, changes the SIM card and activates the GPS again so it comes back online.
- Take the vehicle number, their name and where the vehicle is, and say our team will call to fix the visit.

Always:
- When a caller tells you something bad, such as a missing or stolen vehicle, an accident, or a problem that has troubled them for days, first say one plain, simple apology and only then help: in English "I am sorry to hear that, sir", in Telugu "Sorry to hear that సర్", in Hindi "Sorry to hear that सर". No అయ్యో, no "చాలా బాధగా ఉంది", no dramatic or emotional words. Say the apology once on the call and never again.
- A caller who says the vehicle itself is missing, lost or stolen is an existing customer in an emergency: say the simple apology, ask the vehicle number and check it. After the check, do exactly what the result tells you. It will usually tell you to verify the caller first. Until a result says the caller is verified, never tell whether the GPS is online or offline or where the vehicle is, and never say the vehicle is safe. Never offer products, prices, renewal or plans on such a call, never ask whether they need any other help, and do not suggest any other way.
- Never invent a feature, a price, a charge, a promise, a website, a link, a login method or an app feature. Say only what is in these instructions or in a check result. For any charge you were not told, say the team will confirm it.
{{PLACE}}- Say a thing once. Never say again what you said in your previous reply, and do not repeat the price, subscription, warranty or free installation later in the call unless they ask. When the caller answers your question, accept the answer and move to the next step. If you did not understand, ask only about the part you missed, in different words.
- A caller who calls you madam or sir is addressing you. It tells you nothing about them. Until you are told whether the caller is a man or a woman, the word for them is sir.
- The only address words are sir or madam, or గారు with their name (in Hindi सर, मैडम or जी). Callers often call you అమ్మా, అన్నా, అక్కా, చెల్లి, బాబు, తల్లి, bro, dear, बेटा, भैया or दीदी. Never copy the word they use for you and never use any such family or friendly word for them.
- Answer what the caller asked, fully and clearly, and then stop or ask the next step's question. Never end a reply with ఇంకేమైనా help కావాలా, ఇంకేమైనా doubts ఉన్నాయా, or any question of that kind: the caller is often not finished and will ask the next thing themselves. Such a question may be asked at most once in the whole call, only when their work is completely finished, such as after a booking, a renewal or a complaint is noted, and never in the same words twice.
- Do not repeat a vehicle number back to the caller unless you are unsure of a letter or a digit, or a check result tells you to. When you do say a vehicle number, it is always said in English, one letter and one digit at a time, whatever language the call is in. Write it exactly like this: A P, three nine, W A, one five seven five. Never say it as Telugu or Hindi number words, and never join the digits into a big number.
- Callers say the number in many ways: letters in any language, digits one by one or in pairs like fifteen seventy five or పదిహేను డెబ్బై ఐదు. An Indian vehicle number is two letters for the state, two digits, one to three letters, then four digits. Work out the number in that shape before you use it.
- If the caller is angry, do not argue. If they are angry or ask something you do not know, say our team will call them back shortly on this number.
- If the caller speaks only Hindi or English, tell them politely in that language that a team member will call them back.
- If the caller asks whether you are a person or a machine, say honestly that you are the AI assistant of Bharat GPS Tracker.
TXT;

const OFFICE_FROM = 8;    // installations are confirmed from 8 am
const OFFICE_TO = 18;     // until 6 pm
const INSTALL_DAY = 'For a tracker fitted in the vehicle, also ask whether they want the installation today or tomorrow.';
const INSTALL_NIGHT = 'It is outside office hours now (after six in the evening and before eight in the morning), and an installation cannot be assigned or confirmed at this time. So never ask whether they want the installation today or tomorrow, and never promise a day or a time for an installation, a technician visit or a delivery. For a tracker fitted in the vehicle, say that our team will call them after eight in the morning to confirm the installation time.';
const INSTALL_SAMPLE_DAY = "You: Thank you Ravi గారు. Installation ఈ రోజు కావాలా, రేపు కావాలా?\nCustomer: రేపు.";
const INSTALL_SAMPLE_NIGHT = "You: Thank you Ravi గారు. మా team ఉదయం ఎనిమిది గంటల తర్వాత call చేసి installation time confirm చేస్తారు.\nCustomer: Ok.";

// The same instructions for a Hindi call: only the speaking style and the language rule differ.
const SPEAK_HI = <<<'TXT'
How to speak:
- Speak everyday spoken Hindi mixed with the common English words people really use (tracker, vehicle, bike, car, installation, SIM, app, WhatsApp, location). The simple Hindi of a phone call, never formal or news-reader Hindi.
- Write Hindi words in Devanagari script and English words in English letters.
- Reply in one or two short sentences, about twenty words at most. Ask only one question at a time.
- Sir and madam: say सर (मैडम for a woman) in about one reply out of every two or three. Never twice in one reply. If your previous reply had सर or मैडम, leave it out of this one. A reply without it is still polite.
- Most replies start directly with the answer or the question. Start with a short जी सर or ठीक है सर only now and then, when the caller has just asked you to do something, and never in two replies running.
- Use spoken words: चाहिए, बताइए, देख लीजिए, भेज देते हैं. Do not use words like कृपया, आवश्यकता, संपर्क करें, उपभोक्ता.
- Sound like a trained, calm customer care executive. Address the caller as आप.
- Speak for the company as हम (हम भेज देते हैं, हम बता देते हैं), so you never need a masculine or feminine verb for yourself.
- If the caller only says hello or something unclear, respond naturally and gently repeat your question in different words.
- No lists, no symbols, no emojis, no formatting. Write numbers in words.

TXT;
// The same instructions when the caller speaks English.
const SPEAK_EN = <<<'TXT'
How to speak:
- Speak simple, everyday Indian English, the way people really talk on the phone. Short, plain words.
- Reply in one or two short sentences, about twenty words at most. Ask only one question at a time.
- Sir and madam: say sir (madam for a woman) in about one reply out of every two or three. Never twice in one reply. If your previous reply had sir or madam, leave it out of this one. A reply without it is still polite.
- Most replies start directly with the answer or the question. Start with a short "Sure sir" or "Okay sir" only now and then, when the caller has just asked you to do something, and never in two replies running.
- Sound like a trained, calm customer care executive.
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
const HAS_PRICES = '- Our products and prices are listed at the end. Each price is the final price: say exactly that amount and add nothing to it. Sell the Basic GPS Tracker as the steps above say; the other products are in the list only for when the customer asks about them. Never read out the whole list. Say a price in rupees, in words, the way people say it on the phone. Say only a product, price or charge that is in the list. For an offer or anything not in the list, say the team will confirm it on WhatsApp on this same number.';
// Which team gets the email about a finished phone call, by what the call was. Every email is also copied to MAIL_COPY.
// For now the owner wants every call email at one address only (16 Nov 2026). Before that: renewals and re-adding
// went to support@, complaints to support@ and manager@, other requests to manager@, new customers to sales@,
// with a copy of everything to admin@.
const MAIL_TO = [
    'renewal'      => ['sales@bharatgps.com'],
    'readd'        => ['sales@bharatgps.com'],
    'troubleshoot' => ['sales@bharatgps.com'],
    'other'        => ['sales@bharatgps.com'],
    'new'          => ['sales@bharatgps.com'],
    'none'         => ['sales@bharatgps.com'],
];
const MAIL_COPY = '';    // nobody is copied for now
const MAIL_ON = true;    // one email to the team after every real phone call
const VEHICLE_CHECK = "- You need the full vehicle number: two state letters, two digits, one to three letters, then four digits, like AP39BP0042. If the caller gives only part of it, such as only the last four digits, do not check: ask them for the full number starting with the state letters.\n- Callers often say the number in pieces, over two or three turns, for example AP 31 DZ and then 5531. Join the pieces in the order they were said. As soon as the joined pieces make a full number, check that number at once. Never ask for the full number again when the pieces already said make one.\n- As soon as they have said the full number, do not repeat it back and do not ask them to confirm it. Say only a few words that you are checking, like one minute, I will check, and end that same reply with the tag [[VEHICLE: NUMBER]], where NUMBER is the full vehicle number in capital English letters and digits with no spaces, for example [[VEHICLE: AP31TX4521]]. Use plain square brackets. The tag is never spoken. Do not say or ask anything else in that reply. The result of the check then comes to you: tell the caller the part that matches why they called, and follow it. Write the tag only once for each number.\n- Never tell the caller an expiry date, a validity, or whether the GPS is online unless a check result gave it to you. If no result has come, say you could not check just now and that the team will call them. Never guess.\n- Some check results tell you to verify the caller first. Then ask only for the mobile number registered with us for that vehicle. When they have told a ten-digit mobile number, do not repeat it back: say only a few words that you are checking and end that same reply with the tag [[PHONE: NUMBER]], where NUMBER is the ten digits with no spaces, for example [[PHONE: 9876543210]]. The tag is never spoken. Until the result comes and says the caller is verified, tell them nothing about the vehicle: not whether it is in our records, not its status, not its location. Never tell the caller which mobile number is registered, not even a part of it.\n";
const TROUBLE_ASK = "- Do not sell anything. Say sorry for the trouble, then ask these one at a time: since when it is offline; whether any service or repair work was done on the vehicle in recent days, or the battery was removed or changed; the vehicle number; their name; and where the vehicle is now.\n- Then say a technician will be assigned and our team will call them to confirm the visit time.\n";
const TROUBLE_CHECK = "- Do not sell anything. Say sorry for the trouble in a few words and ask only the vehicle number. Do not ask since when it is offline; our server shows that.\n{{VEHICLE}}- The result tells you when the GPS was last online and exactly what to do next: follow it. Do not promise a technician unless the result says so.\n";
const PLACE_CHECK = "- Service area: whenever the caller says the town, village or area where the vehicle is, or where they want the installation, check that place before you promise anything. Say a few words that you are checking and end that same reply with the tag [[PLACE: NAME]], where NAME is the place in English letters, with the town or district too if they said it, for example [[PLACE: Gajuwaka, Visakhapatnam]]. The tag is never spoken. The result then comes to you: follow it. Until a place has been checked and found inside our service area, do not promise a technician visit, an installation or free installation there. Write the tag only once for each place.\n";
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

// What she is told about this caller's earlier calls from the same mobile number, so they need not repeat themselves.
// The latest earlier call from the last 30 days in which the caller actually spoke; '' when there is none.
function caller_memory(string $from, string $skipId = ''): string {
    if ($from === '') return '';
    foreach (calls_all() as $c) {
        if (($c['id'] ?? '') === $skipId || empty($c['phone']) || ($c['from'] ?? '') !== $from || empty($c['answered'])) continue;
        if (($c['started'] ?? 0) < time() - 30 * 86400) break;
        $turns = array_values(array_filter((array)($c['turns'] ?? []), fn($t) => is_array($t) && trim((string)($t['text'] ?? '')) !== ''));
        if (!array_filter($turns, fn($t) => ($t['who'] ?? '') === 'caller')) continue;
        $days = (int)(new DateTime('today'))->diff((new DateTime('@' . $c['started']))->setTimezone(new DateTimeZone('Asia/Kolkata'))->setTime(0, 0))->days;
        $when = $days === 0 ? 'earlier today' : ($days === 1 ? 'yesterday' : $days . ' days ago') . ', on ' . date('j F', $c['started']);
        $lines = [];
        foreach (array_slice($turns, -16) as $t) $lines[] = (($t['who'] ?? '') === 'agent' ? 'You: ' : 'Caller: ') . mb_substr(trim($t['text']), 0, 220);
        $talk = implode("\n", $lines);
        if (mb_strlen($talk) > 2200) $talk = '...' . mb_substr($talk, -2200);
        $m = "This same mobile number called us before, $when. Here is the end of that call:\n$talk\n";
        if (!empty($c['vehicles'])) $m .= 'Vehicle number checked on that call: ' . implode(', ', array_slice((array)$c['vehicles'], -3)) . ".\n";
        return $m . "How to use this: greet them as usual. Once they say why they are calling, if it is about the same matter, show that you remember, in a few words, and continue from where it stopped. Do not ask again for a name, vehicle number or place they already gave: say it and ask if it is the same. Do not read the old call out, and if they are calling about something new, simply help with that.";
    }
    return '';
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
        'rec' => (int)($c['rec'] ?? 0),   // seconds of recording kept on the call server, 0 when there is none
    ];
}

// A task number such as ID-2026-0412 the way she should say it aloud: I D, two zero two six, zero four one two.
function say_task_id(string $id): string {
    $w = ['zero', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine'];
    $parts = [];
    foreach (preg_split('~[^A-Za-z0-9]+~', $id, -1, PREG_SPLIT_NO_EMPTY) as $p)
        $parts[] = ctype_digit($p) ? implode(' ', array_map(fn($d) => $w[(int)$d], str_split($p))) : implode(' ', str_split(strtoupper($p)));
    return implode(', ', $parts);
}

// What happened after the call, in one line, for the Call history page.
function call_after(array $c): string {
    $b = ($c['book_note'] ?? '') !== '' ? 'Booking during the call: ' . $c['book_note'] . ' · ' : '';
    if (($c['verify_note'] ?? '') !== '') $b = 'Vehicle check: ' . $c['verify_note'] . ' · ' . $b;
    return $b . call_after_rest($c);
}
function call_after_rest(array $c): string {
    if (empty($c['phone'])) return 'Test inside the dashboard. Tasks and emails are made only after a real phone call.';
    if (isset($c['answered']) && !$c['answered']) return 'Call not answered by her (not a test number).';
    $m = (int)($c['mailed'] ?? 0);
    if ($m === 0) return empty($c['ended']) ? 'The call has not finished yet.' : 'Nothing was done. This was a training call, or the call server did not report the end of the call.';
    if ($m === 2 && !isset($c['outcome'])) return 'Still working on it. Open this call again in a minute.';
    $kind = ['renewal' => 'Renewal request', 'troubleshoot' => 'GPS complaint', 'readd' => 'Re-adding request', 'new' => 'New customer', 'other' => 'Customer request', 'none' => 'Nothing for the team to do'][$c['outcome'] ?? 'none'] ?? 'Nothing for the team to do';
    $parts = [$kind];
    if (!empty($c['extract_failed'])) $parts[] = 'The call could not be read (Sarvam did not answer)';
    if (($c['task_id'] ?? '') !== '') $parts[] = "Task {$c['task_id']} created";
    elseif (($c['task_error'] ?? '') !== '') $parts[] = 'Task not created: ' . rtrim((string)$c['task_error'], '.');
    elseif (($c['outcome'] ?? '') === 'renewal') $parts[] = 'No task for renewals';
    if (($c['form_email'] ?? '') !== '') $parts[] = ((int)($c['form_sent'] ?? 0) === 1 ? 'Form emailed to ' : 'Form could NOT be emailed to ') . $c['form_email'];
    elseif (FORM_ON && ($c['outcome'] ?? '') === 'new') $parts[] = 'No email ID was confirmed, so no form was sent';
    return implode(' · ', $parts);
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

// The greeting for a call that starts now: good morning before noon, good afternoon until 5 pm, good evening after that
// and through the night. The standard greeting is sometimes said without the time words, so calls do not all sound the same.
function call_greeting(): string {
    $g = training()['greeting'];
    if ($g === GREETING && mt_rand(1, 3) === 1) return GREETING_PLAIN;
    $h = (int)date('G');
    $now = $h >= 5 && $h < 12 ? 'morning' : ($h >= 12 && $h < 17 ? 'afternoon' : 'evening');
    return preg_replace('~\b(good)\s+(morning|afternoon|evening|night)\b~iu', '$1 ' . $now, $g);
}

function training(): array {
    $t = read_json('training.json');
    $hi = lang() === 'hi';   // each language keeps its own greeting; rules and examples are shared
    if (empty($t['greet_v2'])) {   // one time: the owner asked for the new time-of-day greeting in place of the old one
        unset($t['greeting'], $t['greeting_hi']);
        $t['greet_v2'] = 1;
        write_json('training.json', $t);
    }
    if (empty($t['script_v1'])) {   // one time (15 Nov 2026): the owner approved a clean call script; older rules and examples are set aside, not deleted
        $t['old_rules'] = $t['rules'] ?? []; $t['old_examples'] = $t['examples'] ?? [];
        $t['rules'] = []; $t['examples'] = []; $t['learned'] = [];
        $t['script_v1'] = 1;
        write_json('training.json', $t);
    }
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
// The owner asked for Telugu only on calls (15 Nov 2026). false brings back following the caller into Hindi and English.
const TELUGU_ONLY = true;
const TELUGU_RULE = '- Always answer in Telugu, whatever language the caller speaks. If the caller clearly cannot understand Telugu, tell them in simple words that a team member will call them back.';
function live_payload(string $lang, bool $trainer): array {
    if (TELUGU_ONLY) {   // one language: the call server then listens for Telugu only and never switches voice or language
        return ['key' => sarvam_key(), 'lang' => 'te', 'lang_code' => LIVE_LANGS['te']['code'], 'voice' => voice_for_lang('te'),
            'prompt' => system_prompt('te', true, $trainer, !$trainer && (int)($_GET['sv'] ?? 0) >= 13), 'trainer' => $trainer, 'retry_text' => RETRY_TE];
    }
    $prompts = []; $voices = []; $codes = [];
    foreach (LIVE_LANGS as $l => $info) {
        $prompts[$l] = system_prompt($l, true, $trainer, !$trainer && (int)($_GET['sv'] ?? 0) >= 13);
        $voices[$l] = voice_for_lang($l);
        $codes[$l] = $info['code'];
    }
    return ['key' => sarvam_key(), 'lang' => $lang, 'lang_code' => $codes[$lang], 'voice' => $voices[$lang], 'prompt' => $prompts[$lang],
        'prompts' => $prompts, 'voices' => $voices, 'codes' => $codes, 'trainer' => $trainer,
        'retry' => ['te' => RETRY_TE, 'hi' => RETRY_HI, 'en' => RETRY_EN], 'retry_text' => $lang === 'hi' ? RETRY_HI : RETRY_TE];
}

// $parts: mark what is only for new customers and what is only for existing ones, so the call server (version 13
// and later) can leave out the part a call does not need. Without it the text is exactly as before.
function system_prompt(string $lang = 'te', bool $live = false, bool $trainer = false, bool $parts = false): string {
    $t = training();
    $p = SYSTEM_PROMPT;
    if ($parts) {
        $p = str_replace(["\nNew customer:\n", "\nExisting customer, GPS not working or offline:\n", "\nAlways:\n"],
            ["\n<<NEW>>\nNew customer:\n", "\n<</NEW>>\n<<OLD>>\nExisting customer, GPS not working or offline:\n", "\n<</OLD>>\nAlways:\n"], $p);
    }
    if ($lang === 'hi' || $lang === 'en') {
        $a = strpos($p, 'How to speak:'); $b = strpos($p, 'What to do on the call:');
        $p = substr($p, 0, $a) . ($lang === 'hi' ? SPEAK_HI : SPEAK_EN) . "\n" . substr($p, $b);
    }
    $rule = $live && TELUGU_ONLY ? TELUGU_RULE : ($live ? sprintf(LIVE_LANG_RULE, LIVE_LANGS[$lang]['name']) : ($lang === 'hi' ? OTHER_LANG_HI : OTHER_LANG_TE));
    $p = str_replace(OTHER_LANG_TE, $rule, $p);
    $check = $live && gps_on();
    $p = str_replace('{{TROUBLE}}', $check ? TROUBLE_CHECK : TROUBLE_ASK, $p);
    $p = str_replace('{{VEHICLE}}', $check ? VEHICLE_CHECK : '', $p);
    // Installations are assigned and confirmed only in office hours, 8 am to 6 pm. Outside them she must not offer a day.
    $h = (int)date('G'); $open = $h >= OFFICE_FROM && $h < OFFICE_TO;
    $p = str_replace('{{INSTALL}}', $open ? INSTALL_DAY : INSTALL_NIGHT, $p);
    $p = str_replace('{{INSTALL_SAMPLE}}', $open ? INSTALL_SAMPLE_DAY : INSTALL_SAMPLE_NIGHT, $p);
    $p = str_replace('{{PLACE}}', $live && areas_on() ? PLACE_CHECK : '', $p);
    $cat = catalog();
    $p = str_replace('{{PRICES}}', $cat === '' ? NO_PRICES : HAS_PRICES, $p);
    if ($cat !== '') $p .= "\n\n" . ($parts ? "<<NEW>>\n$cat\n<</NEW>>" : $cat);
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
const PRICE_LIST_V2 = [
    ['name' => 'Basic GPS Tracker', 'price' => 3500, 'fits' => ['Bike', 'Scooter', 'Car', 'Auto', 'Truck', 'Bus', 'Tractor or JCB'],
        'for' => 'Any vehicle, to see where it is', 'inc' => '1 year subscription, 5 years warranty, free installation within our service range', 'feat' => []],
    ['name' => 'Engine Cutoff GPS Tracker', 'price' => 4500, 'fits' => ['Bike', 'Scooter', 'Car', 'Auto', 'Truck', 'Bus', 'Tractor or JCB'],
        'for' => 'Any vehicle, when more security is needed, such as rented vehicles', 'inc' => '1 year subscription, 5 years warranty, free installation within our service range',
        'feat' => ['The engine can be cut off through the mobile app']],
    ['name' => 'Micro GPS Tracker', 'price' => 3500, 'fits' => ['Person, bag or pet'],
        'for' => 'Handheld device, no installation required', 'inc' => '1 year subscription, 1 year warranty',
        'feat' => ['One charge gives 2 to 3 days of backup', 'Inbuilt mic with two-way communication, and can also listen to the voice',
            'To use the mic the customer must put their own SIM card, because our company SIM card cannot make calls', 'No engine on and off alerts']],
    ['name' => 'Magnet GPS Tracker', 'price' => 5500, 'fits' => ['Person, bag or pet'],
        'for' => 'Handheld device with a magnet, no installation required', 'inc' => '1 year subscription, 1 year warranty',
        'feat' => ['One charge gives 10 to 15 days of backup', 'Inbuilt mic to listen to the voice only',
            'To use the mic the customer must put their own SIM card, because our company SIM card cannot make calls', 'No engine on and off alerts']],
    ['name' => 'VLTD GPS Tracker (government / RTO approved AIS140)', 'price' => 9000, 'fits' => ['Car', 'Auto', 'Truck', 'Bus'],
        'for' => 'Vehicles that need the government or RTO approved AIS140 GPS', 'inc' => '1 year subscription, 1 year warranty',
        'feat' => ['Comes with an SOS button']],
];
const PRICE_NOTES_V2 = "Features of every GPS tracker: live location on phone; vehicle movement alert when it is on standby; engine on and off alert (not on Micro and Magnet); geofencing alert; overspeed alert; GPS offline alert; GPS power fail alert; can be tracked on up to 5 phones at a time.\nReports: more than 20 types of report, downloaded in Excel or PDF, and can come by email daily, weekly or monthly.\nThese prices are final.";
function products(): array {
    $d = read_json('products.json');
    if (empty($d['price_v2'])) {   // one time: the owner's final price list of 15 Nov 2026 replaces the list that was there
        $d['old_products'] = $d['products'] ?? []; $d['old_notes'] = $d['notes'] ?? '';
        $d['products'] = [];
        foreach (PRICE_LIST_V2 as $row) $d['products'][] = ['id' => bin2hex(random_bytes(6))] + $row;
        $d['notes'] = PRICE_NOTES_V2;
        $d['site_import'] = 1; $d['price_v2'] = 1;
        write_json('products.json', $d);
    }
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

// ---------- service areas ----------
// Where the technicians work from, and how far from them the business serves. A place the caller says is found
// on the map (Google when the owner has given a Maps key, otherwise the free OpenStreetMap search) and measured
// in a straight line from the nearest technician.
const AREA_OUTSIDE = 'Our technician is not in that area. The team will check and confirm on WhatsApp.';
function areas(): array {
    $d = read_json('areas.json');
    $techs = [];
    foreach ((array)($d['techs'] ?? []) as $t) if (is_array($t) && isset($t['lat'], $t['lng'])) $techs[] = $t;
    $km = (int)($d['km'] ?? 50);
    return ['techs' => $techs, 'km' => $km >= 1 && $km <= 2000 ? $km : 50, 'outside' => trim((string)($d['outside'] ?? '')) ?: AREA_OUTSIDE];
}
function areas_on(): bool { return (bool)areas()['techs']; }
function maps_key(): string { return trim((string)(settings()['maps_key'] ?? '')); }

// Find a place name on the map. ok, lat, lng, label (how the map names it), via; or ok false with why (none, key, down).
function geo_find(string $q, bool $useCache = true): array {
    $q = clean_line($q, 120);
    if (mb_strlen($q) < 2) return ['ok' => false, 'why' => 'none'];
    $k = mb_strtolower($q) . '|' . (maps_key() !== '' ? 'g' : 'o');
    $cache = read_json('geo.json');
    if ($useCache && isset($cache[$k]) && ($cache[$k]['at'] ?? 0) > time() - 90 * 86400) return $cache[$k]['r'];
    $get = function (string $url, array $head = []) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 8, CURLOPT_HTTPHEADER => $head,
            CURLOPT_USERAGENT => 'BharatGPS-call-agent/1.0 (someswara.com/ivr)']);
        $b = curl_exec($ch); curl_close($ch);
        return is_string($b) ? json_decode($b, true) : null;
    };
    if (maps_key() !== '') {
        $j = $get('https://maps.googleapis.com/maps/api/geocode/json?region=in&components=country:IN&language=en&address=' . rawurlencode($q) . '&key=' . rawurlencode(maps_key()));
        $st = is_array($j) ? (string)($j['status'] ?? '') : '';
        if ($st === 'OK' && isset($j['results'][0]['geometry']['location'])) {
            $g = $j['results'][0];
            // "India" alone means Google did not really find the place
            $r = count((array)($g['address_components'] ?? [])) <= 1 ? ['ok' => false, 'why' => 'none']
                : ['ok' => true, 'lat' => (float)$g['geometry']['location']['lat'], 'lng' => (float)$g['geometry']['location']['lng'], 'label' => clean_line(preg_replace('~,\s*India$~', '', (string)($g['formatted_address'] ?? $q)), 120), 'via' => 'Google'];
        } elseif ($st === 'ZERO_RESULTS') $r = ['ok' => false, 'why' => 'none'];
        elseif ($st === 'REQUEST_DENIED' || $st === 'INVALID_REQUEST') return ['ok' => false, 'why' => 'key', 'msg' => clean_line((string)($j['error_message'] ?? ''), 200)];
        else return ['ok' => false, 'why' => 'down'];
    } else {
        $j = $get('https://nominatim.openstreetmap.org/search?format=json&limit=1&countrycodes=in&accept-language=en&q=' . rawurlencode($q));
        if (!is_array($j)) return ['ok' => false, 'why' => 'down'];
        $r = isset($j[0]['lat'], $j[0]['lon'])
            ? ['ok' => true, 'lat' => (float)$j[0]['lat'], 'lng' => (float)$j[0]['lon'], 'label' => clean_line(implode(', ', array_slice(array_map('trim', explode(',', preg_replace('~,\s*India$~', '', (string)($j[0]['display_name'] ?? $q)))), 0, 3)), 120), 'via' => 'the free map']
            : ['ok' => false, 'why' => 'none'];
    }
    $cache[$k] = ['at' => time(), 'r' => $r];
    if (count($cache) > 400) $cache = array_slice($cache, -300, null, true);
    write_json('geo.json', $cache);
    return $r;
}

function km_between(float $a1, float $o1, float $a2, float $o2): float {
    $p = M_PI / 180; $x = sin(($a2 - $a1) * $p / 2) ** 2 + cos($a1 * $p) * cos($a2 * $p) * sin(($o2 - $o1) * $p / 2) ** 2;
    return 6371 * 2 * atan2(sqrt($x), sqrt(1 - $x));
}

// Is this place within the service distance of a technician? status: off (no technicians entered), unknown (place
// not found), down (map not reachable or key refused), in, out.
function area_check(string $place): array {
    $a = areas();
    if (!$a['techs']) return ['status' => 'off'];
    $g = geo_find($place);
    if (!$g['ok']) return ['status' => $g['why'] === 'none' ? 'unknown' : 'down', 'place' => clean_line($place, 120), 'why' => $g['why'], 'msg' => $g['msg'] ?? ''];
    $best = null;
    foreach ($a['techs'] as $t) { $d = km_between($g['lat'], $g['lng'], (float)$t['lat'], (float)$t['lng']); if ($best === null || $d < $best['km']) $best = ['km' => $d, 'tech' => $t]; }
    $km = (int)round($best['km']);
    return ['status' => $km <= $a['km'] ? 'in' : 'out', 'place' => clean_line($place, 120), 'label' => $g['label'], 'via' => $g['via'], 'km' => $km, 'limit' => $a['km'],
        'tech' => (string)($best['tech']['name'] ?? ''), 'tech_place' => (string)($best['tech']['place'] ?? ''), 'outside' => $a['outside']];
}

// The result in words, for her, and as a bare fact for later in the call.
function area_result_text(array $r): string {
    switch ($r['status']) {
        case 'in': return "{$r['label']} is inside our service area, about {$r['km']} km from our technician. Our technician can visit there. Do not say the distance or the technician's name; just continue with the next step.";
        case 'out': return "{$r['label']} is outside our service area: about {$r['km']} km from our nearest technician, and we serve up to {$r['limit']} km. Do not promise a technician visit or installation there. Tell the caller this, in the language of the call: {$r['outside']}";
        case 'unknown': return "The place \"{$r['place']}\" could not be found on the map. Ask the caller for the nearest town or the district, and check again with the PLACE tag.";
        case 'off': return 'Service areas are not set up. Continue as usual and say the team will confirm the visit.';
        default: return 'The map could not be checked just now. Do not say whether we serve that place. Say the team will confirm it, and continue.';
    }
}
function area_result_fact(array $r): string {
    if ($r['status'] === 'in') return "{$r['label']} is inside our service area; a technician can visit.";
    if ($r['status'] === 'out') return "{$r['label']} is outside our service area; no technician visit was promised.";
    return '';
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

// A small copy of every server's vehicle list (number, expiry, last signal), refreshed when older than five minutes.
function gps_index(bool $fresh = false): array {
    $c = read_json('gps.json');
    if (!$fresh && is_array($c) && ($c['at'] ?? 0) > time() - 300 && ($c['v'] ?? 0) === 4) return $c;
    $keys = array_filter(gps_keys());
    $multi = curl_multi_init(); $hs = [];
    foreach ($keys as $id => $key) {
        $ch = curl_init('https://' . GPS_SERVERS[$id] . '/api/get_devices?lang=en&user_api_hash=' . rawurlencode($key));
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_CONNECTTIMEOUT => 6, CURLOPT_TIMEOUT => 18, CURLOPT_USERAGENT => 'Mozilla/5.0']);
        curl_multi_add_handle($multi, $ch); $hs[$id] = $ch;
    }
    do { curl_multi_exec($multi, $run); if ($run) curl_multi_select($multi, 1.0); } while ($run > 0);
    $old = is_array($c) ? $c : [];
    $new = ['at' => time(), 'v' => 4, 'servers' => [], 'dev' => []];
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
            $new['dev'][] = [$id, plate_key($name), plate_key((string)($dd['plate_number'] ?? '')), $name, $exp, clean_line((string)($dv['time'] ?? ($dd['time'] ?? ($dv['timestamp'] ?? ''))), 30), strtolower((string)($dv['online'] ?? '')),
                gps_phones($dv), round((float)($dv['lat'] ?? 0), 5), round((float)($dv['lng'] ?? 0), 5), (int)round((float)($dv['speed'] ?? 0))];
            $n++;
        }
        $new['servers'][$id] = ['state' => 'ok', 'count' => $n];
    }
    curl_multi_close($multi);
    write_json('gps.json', $new);
    return $new;
}

// The customer's mobile numbers saved on a vehicle on the GPS server (in the owner, notes, name or user fields).
// The SIM number of the GPS device itself and the IMEI are not customer numbers and are left out.
function gps_phones(array $dv): array {
    // Look in every text or number field of the vehicle's record, wherever the server keeps it, except the fields that
    // hold the device's own SIM number, its IMEI, positions and times.
    $skip = ['imei', 'sim_number', 'msisdn', 'iccid', 'vin', 'lat', 'lng', 'latitude', 'longitude', 'time', 'timestamp', 'id', 'traccar_device_id',
        'tail', 'sensors', 'services', 'latest_positions', 'icon', 'icon_colors', 'device_id', 'user_id', 'course', 'altitude', 'speed', 'moved_timestamp', 'ack_timestamp', 'server_time', 'device_time'];
    $texts = [];
    $walk = function ($v, int $depth) use (&$walk, &$texts, $skip) {
        foreach ((array)$v as $k => $x) {
            if (is_string($k) && in_array(strtolower($k), $skip, true)) continue;
            if (is_string($x) || is_int($x)) $texts[] = (string)$x;
            elseif (is_array($x) && $depth < 4) $walk($x, $depth + 1);
        }
    };
    $walk($dv, 0);
    $out = [];
    $re = '~(?<!\d)(?:\+?91[\s-]?|0)?([6-9]\d{9})(?!\d)~';
    foreach ($texts as $t) {
        if (strlen($t) > 400) continue;
        // as written, and again with spaces and hyphens inside the number removed ("99632 22009")
        foreach ([$t, preg_replace('~(?<=\d)[\s-]+(?=\d)~', '', $t)] as $v) if (preg_match_all($re, $v, $m)) foreach ($m[1] as $n) $out[$n] = 1;
    }
    return array_slice(array_keys($out), 0, 8);
}
// For the owner's check on the GPS servers page: every field of one vehicle's record that holds a long number, read
// fresh from its server, with all but the last four digits hidden. It shows where a customer's mobile is really kept.
function gps_raw_numbers(int $server, string $name): array {
    $key = gps_keys()[$server] ?? '';
    if ($key === '' || !isset(GPS_SERVERS[$server])) return [];
    $ch = curl_init('https://' . GPS_SERVERS[$server] . '/api/get_devices?lang=en&user_api_hash=' . rawurlencode($key));
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_CONNECTTIMEOUT => 6, CURLOPT_TIMEOUT => 18, CURLOPT_USERAGENT => 'Mozilla/5.0']);
    $body = curl_exec($ch); curl_close($ch);
    $data = is_string($body) ? json_decode($body, true) : null;
    if (!is_array($data)) return [];
    foreach ($data as $g) foreach ((array)($g['items'] ?? []) as $dv) {
        if (!is_array($dv) || clean_line($dv['name'] ?? '', 80) !== $name) continue;
        $out = [];
        $walk = function ($v, string $path, int $depth) use (&$walk, &$out) {
            foreach ((array)$v as $k => $x) {
                $here = $path === '' ? (string)$k : "$path > $k";
                if (is_array($x)) { if ($depth < 4 && !in_array((string)$k, ['tail', 'sensors', 'services', 'latest_positions'], true)) $walk($x, $here, $depth + 1); continue; }
                if (!is_string($x) && !is_int($x)) continue;
                $digits = preg_replace('~\D~', '', (string)$x);
                if (strlen($digits) < 8 || strlen((string)$x) > 60 || preg_match('~^\d{4}-\d\d-\d\d~', (string)$x)) continue;
                $out[] = $here . ': ' . (strlen($digits) === strlen(trim((string)$x)) ? '' : '(with other text) ') . str_repeat('•', max(0, strlen($digits) - 4)) . substr($digits, -4) . ' (' . strlen($digits) . ' digits)';
            }
        };
        $walk($dv, '', 0);
        return array_slice($out, 0, 30);
    }
    return [];
}
function phone10(string $n): string { $d = preg_replace('~\D~', '', $n); return strlen($d) >= 10 ? substr($d, -10) : ''; }

// The name of the place at a point on the map, for telling a verified caller where the vehicle is.
function geo_reverse(float $lat, float $lng): string {
    if (abs($lat) < 0.01 && abs($lng) < 0.01) return '';
    $k = 'rev|' . round($lat, 3) . ',' . round($lng, 3);
    $cache = read_json('geo.json');
    if (isset($cache[$k]) && ($cache[$k]['at'] ?? 0) > time() - 90 * 86400) return (string)$cache[$k]['r'];
    $get = function (string $url) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_CONNECTTIMEOUT => 4, CURLOPT_TIMEOUT => 6, CURLOPT_USERAGENT => 'BharatGPS-call-agent/1.0 (someswara.com/ivr)']);
        $b = curl_exec($ch); curl_close($ch);
        return is_string($b) ? json_decode($b, true) : null;
    };
    $label = '';
    if (maps_key() !== '') {
        $j = $get('https://maps.googleapis.com/maps/api/geocode/json?language=en&latlng=' . $lat . ',' . $lng . '&key=' . rawurlencode(maps_key()));
        if (is_array($j) && ($j['status'] ?? '') === 'OK') $label = (string)($j['results'][0]['formatted_address'] ?? '');
    }
    if ($label === '') {
        $j = $get('https://nominatim.openstreetmap.org/reverse?format=json&zoom=16&accept-language=en&lat=' . $lat . '&lon=' . $lng);
        if (is_array($j)) $label = (string)($j['display_name'] ?? '');
    }
    if ($label === '') return '';
    $parts = array_values(array_filter(array_map('trim', explode(',', preg_replace('~,\s*India$~', '', $label))), fn($x) => $x !== '' && !preg_match('~^\d{6}$|^[A-Z0-9]{4}\+[A-Z0-9]{2,3}~', $x)));
    $label = clean_line(implode(', ', array_slice($parts, 0, 3)), 120);
    $cache[$k] = ['at' => time(), 'r' => $label];
    if (count($cache) > 400) $cache = array_slice($cache, -300, null, true);
    write_json('geo.json', $cache);
    return $label;
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
    return gps_record($hit[0], $q, $near);
}

// One vehicle from the list, as a full result.
function gps_record(array $e, string $q, bool $near = false): array {
    [$id, , , $name, $exp, $last] = $e;
    $online = in_array($e[6] ?? '', ['online', 'ack'], true);
    // The number plate as the server has it. The device name is often a customer name, so it is not used as the number.
    $plate = $q;
    foreach ([$e[2], $e[1]] as $k) if ($k !== '' && ($pp = plate_parts($k)) && preg_match('~[A-Z]{2}0?' . $pp[1] . $pp[2] . '0*' . $pp[3] . '(?!\d)~', $k, $pm)) { $plate = $pm[0]; break; }
    $ts = gps_time($last);
    $r = ['status' => 'found', 'number' => $q, 'name' => $name, 'server' => $id, 'host' => GPS_SERVERS[$id], 'expiry' => $exp, 'last' => $last, 'near' => $near, 'online' => $online, 'plate' => $plate,
        'phones' => (array)($e[7] ?? []), 'lat' => (float)($e[8] ?? 0), 'lng' => (float)($e[9] ?? 0), 'speed' => (int)($e[10] ?? 0)];
    if ($ts) { $r['last_ts'] = $ts; $r['off_hours'] = max(0, (int)floor((time() - $ts) / 3600)); $r['last'] = date('j M Y, g:i A', $ts); }
    if ($exp !== '') {
        $r['days'] = (int)(new DateTime('today'))->diff(new DateTime($exp))->format('%r%a');
        $r['expiry_text'] = date('j F Y', strtotime($exp));
    }
    return $r;
}

// Every vehicle whose number, or whose name on the server (such as "Prasanna-5727"), ends with these four digits.
// A vehicle number is hard to hear correctly on a phone line, but four digits are not; the registered mobile number
// then tells which of these vehicles is the caller's, and proves they own it.
function gps_last4(string $d4): array {
    if (!preg_match('~^\d{4}$~', $d4)) return ['state' => 'none', 'list' => []];
    $idx = gps_index();
    $list = [];
    foreach ((array)($idx['dev'] ?? []) as $e) {
        foreach ([(string)($e[2] ?? ''), (string)($e[1] ?? ''), preg_replace('~\D~', '', (string)($e[3] ?? ''))] as $k) if ($k !== '' && substr($k, -4) === $d4) { $list[] = $e; break; }
    }
    $down = false;
    foreach ((array)($idx['servers'] ?? []) as $sv) if (is_array($sv) && ($sv['state'] ?? '') === 'down') $down = true;
    return ['state' => $list ? 'found' : ($down ? 'down' : 'none'), 'list' => $list];
}
// The one among those vehicles that has this mobile number saved, as a full result; null when none has it.
function gps_pick(array $list, string $mobile): ?array {
    if ($mobile === '') return null;
    foreach ($list as $e) if (in_array($mobile, (array)($e[7] ?? []), true)) return gps_record($e, (string)(($e[2] ?? '') !== '' ? $e[2] : ($e[1] ?? '')));
    return null;
}

// AP39WA1575 -> [AP, 39, WA, 1575]; null when the text is not a vehicle number.
function plate_parts(string $k): ?array {
    return preg_match('~([A-Z]{2})0?(\d{1,2})([A-Z]{0,3})0*(\d{1,4})(?!\d)~', $k, $m) ? [$m[1], (string)(int)$m[2], $m[3], (string)(int)$m[4]] : null;
}

// The server's "last signal" text as a time. The servers give it as 13-11-2026 19:42:10 or 2026-11-13 19:42:10.
function gps_time(string $t): int {
    $t = trim($t);
    if ($t === '' || $t[0] === '0' && strpos($t, '0000') === 0) return 0;
    if (ctype_digit($t) && strlen($t) >= 9) return (int)$t;
    foreach (['d-m-Y H:i:s', 'Y-m-d H:i:s', 'd-m-Y h:i:s A', 'd/m/Y H:i:s', 'Y-m-d H:i'] as $f) {
        $d = DateTime::createFromFormat($f, $t);
        if ($d && $d->getTimestamp() > 946684800) return $d->getTimestamp();
    }
    $x = strtotime($t);
    return $x && $x > 946684800 ? $x : 0;
}

// What the servers say about one found vehicle, with what she should do for each kind of call.
// $why: 'renew' or 'trouble' when the caller's words made the reason clear; then only that part is given to her.
// $ver: the caller is verified as the owner (their mobile number matches the one saved for this vehicle). Only then
// may she say whether the GPS is online, when it was last online, or where the vehicle is. The expiry date is not secret.
const VERIFY_ASK = "You are not allowed to tell the caller anything about this vehicle yet, not even whether it is in our records or not. Say in a few polite words that for security you need to verify, and ask them to tell the mobile number registered with us for this vehicle. Say nothing else in this reply. When they tell a ten-digit mobile number, follow the rule for the PHONE tag.";
const VERIFY_ASK_STATUS = "you must verify the caller first. Until a result says they are verified, do not say whether the GPS is online or offline, when it was last online, or where the vehicle is. If they ask for any of these, say in a few polite words that for security you need to verify, and ask them to tell the mobile number registered with us for this vehicle. When they tell a ten-digit mobile number, follow the rule for the PHONE tag.";
function gps_where(array $r): string {
    $live = !empty($r['online']);
    $place = geo_reverse((float)($r['lat'] ?? 0), (float)($r['lng'] ?? 0));
    if ($place === '') return 'Location: the place name could not be read just now. If they ask where the vehicle is, say our team will check the location and call them.';
    return "Location: the vehicle's " . ($live ? 'location right now is' : 'last known location, from when the GPS was last online, is') . " near $place" . ($live ? ((int)($r['speed'] ?? 0) > 3 ? ', and it is moving at about ' . (int)$r['speed'] . ' kilometres per hour' : ', and it is not moving') : '') . '. Tell them this place when they ask where the vehicle is.';
}
function gps_facts(array $r, string $why = '', bool $ver = false): string {
    // validity
    if (($r['expiry'] ?? '') === '') $valid = 'Validity: no expiry date is recorded. If they ask about validity or renewal, say the team will confirm the expiry date.';
    else {
        $d = $r['days'];
        $when = $d > 1 ? "$d days from today" : ($d === 1 ? 'tomorrow' : ($d === 0 ? 'today' : ($d === -1 ? 'yesterday, so it has already expired' : 'which was ' . (-$d) . ' days ago, so it has already expired')));
        $valid = "Validity: the expiry date is {$r['expiry_text']}, $when. If they called about renewal or validity: " . ($d >= 30
            ? 'thirty days or more are left, so renewal is not needed now. Tell them the expiry date and that the vehicle is still valid. Do not ask which plan they want and do not push renewal.'
            : 'less than thirty days are left, so tell them the expiry date and ask which plan they want to renew with.');
    }
    // signal
    $h = $r['off_hours'] ?? null;
    $ago = $h === null ? '' : ($h < 48 ? "$h hours ago" : intdiv($h, 24) . ' days ago');
    $ask = 'take their name and where the vehicle is now, and say our team will call to confirm the visit time.';
    if (!empty($r['online']) || ($h !== null && $h < 1)) {
        $sig = 'Signal: the GPS is online on our server right now. If they called because it is not working: tell them it is showing online on our side, and ask them to close the app, open it again and check. If they still cannot see it, say our team will call them.';
    } elseif ($h === null) {
        $sig = 'Signal: the last online time is not known. If they called because it is not working: say you have noted the complaint and the team will check it, then ' . $ask;
    } else {
        $sig = "Signal: the GPS is offline. It was last online $ago, on {$r['last']}. If they called because it is not working: tell them when it was last online, then ";
        if ($h < 72) $sig .= 'ask them to start the vehicle and drive it a little, and check the app after some time. If it still does not come online, they should call us back. Do not arrange a technician now.';
        elseif ($h <= 15 * 24) $sig .= 'ask whether any service or repair was done on the vehicle or the battery was removed in recent days. Then say you have noted the complaint and our team will check and arrange a technician; ' . $ask;
        else $sig .= 'say that it has been offline for a long time, so you will request a technician to visit; ' . $ask;
        if (($r['expiry'] ?? '') !== '' && $r['days'] < 0) $sig .= ' Also tell them the subscription has expired, which is often why a GPS goes offline, and that it needs renewal to work again.';
    }
    $live = !empty($r['online']) || ($h !== null && $h < 1);
    // The caller says the vehicle itself is missing or stolen, and is verified as the owner: tell them what helps to find it.
    if ($why === 'missing') {
        $status = $live ? 'the GPS is online right now' : ($h === null ? 'the GPS is offline and its last online time is not known' : "the GPS is offline; it was last online $ago, on {$r['last']}");
        return "The caller is verified and their vehicle {$r['plate']} is missing. You have already said sorry on this call: do not say sorry again. Status: $status.\n" . gps_where($r)
            . "\nTell them, in two short sentences, the status and the place. Then say our team will call them straight away to help trace it. Do not talk about expiry dates, renewal, plans or prices, and do not ask what else they need.";
    }
    $more = $ver ? "\n" . gps_where($r) : '';
    // No expiry date on the server (a test device, a technician's device, or unlimited validity).
    if (($r['expiry'] ?? '') === '') {
        if ($why === 'renew') return "This vehicle has no expiry date recorded on our server.\nTell the caller that you do not have the expiry details for this vehicle, so our team will check and call them. Then ask whether they want to renew. Do not guess an expiry date, and do not say whether the GPS is online or offline.";
        if ($ver) return $sig . $more . "\nTell the caller only what matches why they called. Do not talk about expiry dates, renewal or plans.";
        return "This vehicle has no expiry date recorded on our server. If they ask about validity or renewal, say our team will confirm the expiry date and call them.\nFor anything else about this vehicle, " . VERIFY_ASK_STATUS;
    }
    if ($why === 'renew') return $valid . "\nTell the caller only this. Do not talk about the app, the signal or the location.";
    if ($why === 'trouble') return $sig . $more . "\nTell the caller only what matches why they called. Do not offer renewal or plans unless it says the subscription has expired.";
    if ($ver) return $valid . "\n" . $sig . $more . "\nUse only the part that matches why they called. Never say all of it in one reply.";
    return $valid . "\nYou may tell the validity. For the GPS status, whether it is online, or where the vehicle is, " . VERIFY_ASK_STATUS;
}

// The same result as plain values, for the fixed-script call flow on the call server (no wording, no instructions).
// Only for a verified owner: it carries the GPS status.
function gps_data(array $r): array {
    $h = isset($r['off_hours']) ? (int)$r['off_hours'] : null;
    return ['state' => 'ok', 'plate' => (string)$r['plate'], 'online' => !empty($r['online']) || ($h !== null && $h < 1), 'off_hours' => $h,
        'last_ts' => (int)($r['last_ts'] ?? 0), 'expiry' => (string)($r['expiry'] ?? ''), 'days' => ($r['expiry'] ?? '') !== '' ? (int)$r['days'] : null];
}

// The same result as bare facts with no instructions, kept as background once she has told the caller.
function gps_result_fact(array $r, bool $ver = false): string {
    $n = $r['number'] ?? '';
    if ($r['status'] === 'found' && empty($r['near'])) {
        $f = "vehicle $n is on our server";
        if (($r['expiry'] ?? '') !== '') $f .= ", expiry date {$r['expiry_text']}" . ($r['days'] < 0 ? ' (already expired)' : " ({$r['days']} days left)");
        if ($ver) $f .= ', the caller is verified as the owner' . (!empty($r['online']) ? ', GPS online now' : (($r['last_ts'] ?? 0) ? ", GPS last online {$r['last']}" : ''));
        return $f . '.';
    }
    if ($r['status'] === 'none') return "vehicle $n is not in our records (expired earlier and removed). You offered re-adding the same GPS for 1700 rupees or a new GPS for 3500 rupees.";
    return '';
}

// The result in words, for her.
function gps_result_text(array $r, string $why = '', bool $ver = false): string {
    $n = $r['number'] ?? '';
    switch ($r['status']) {
        case 'found':
            if (!empty($r['near'])) return "$n itself is not on our server, but there is one very close number: {$r['plate']}. Ask the caller whether {$r['plate']} is their vehicle number, saying it letter by letter, and say nothing else in this reply. You do not know its expiry date or status yet, so do not say any. If their answer means yes, check {$r['plate']} the usual way: say you are checking and end the reply with [[VEHICLE: {$r['plate']}]]. If it means no, ask them to say the full number once more.";
            return "Vehicle $n is found on our server.\n" . gps_facts($r, $why, $ver);
        case 'many': return "More than one vehicle matches $n. Ask the caller to say the full vehicle number once more, slowly.";
        case 'down': return "The server could not be checked just now. Do not say the vehicle is missing. Say you could not check at the moment, note what they need, and say the team will call them.";
        default:
            // A missing or stolen vehicle is an emergency: never answer it with prices. The number was most likely misheard.
            if ($why === 'missing') return "Vehicle $n was not found on our servers, most likely because the number was not heard correctly. Say that you could not find this number and ask them to say the full vehicle number once more, slowly. If it is not found a second time, say our team will call them straight away to help. Do not mention re-adding, a new GPS or any price on this call.";
            return "Vehicle $n is not on any of our servers. Tell the caller that this vehicle number is not in our records, which means its GPS expired earlier and was removed from the server. Then explain the two choices: adding it again by re-installing the same GPS costs one thousand seven hundred rupees; or, if that GPS no longer works, a new GPS is better, which costs three thousand five hundred rupees. Ask which they prefer, once. When they choose either one, do not explain it again: say you have noted it, take their name and where the vehicle is, and say our team will call to fix the technician's visit. Only if the caller says the number you checked was wrong, take the right number and check that.";
    }
}

// ---------- after a phone call ----------
// TaskManager entry point that creates one unassigned task (ai-call-task.php in the TaskManager project).
const LIVE_VERSION = 32;   // must match VERSION in live/server.js; the dashboard warns when the VPS runs an older copy
const REC_URL = 'https://call.someswara.com/rec';   // where the call server serves a recording, with a short-lived pass
const TASK_URL = 'https://salmon-goldfish-110661.hostingersite.com/ai-call-task.php';

// The pass for TaskManager: made here once and kept in settings.json. TaskManager remembers the first key it is shown.
function task_key(): string {
    $s = settings();
    if (empty($s['task_key'])) { $s['task_key'] = bin2hex(random_bytes(32)); write_json('settings.json', $s); }
    return (string)$s['task_key'];
}

// Read the finished call once more and say what it was: which kind of request, and the details the caller gave.
function call_extract(array $c): array {
    $none = ['type' => 'none', 'name' => '', 'vehicle' => '', 'location' => '', 'product' => '', 'plan' => '', 'email' => '', 'summary' => ''];
    $talk = '';
    foreach ((array)$c['turns'] as $t) $talk .= (($t['who'] ?? '') === 'agent' ? 'Agent: ' : 'Caller: ') . $t['text'] . "\n";
    if (!array_filter((array)$c['turns'], fn($t) => ($t['who'] ?? '') === 'caller')) return $none;
    $ask = "Below is a phone call between the AI assistant of Bharat GPS Tracker (Agent) and a caller. Read it and reply with one JSON object only, no other words.\n"
        . "Fields:\n"
        . "\"type\": exactly one of these words.\n"
        . "  \"renewal\": an existing customer wants to renew their GPS subscription.\n"
        . "  \"troubleshoot\": an existing customer's GPS is not working or offline AND the agent said the team or a technician will follow up. If the agent only asked them to drive the vehicle and check, or said it is online, use \"none\".\n"
        . "  \"readd\": the vehicle is not in our records or expired long ago and the caller wants it added or activated again.\n"
        . "  \"new\": a new customer who agreed to buy or to book installation, or asked us to call them about buying.\n"
        . "  \"other\": anything else where the agent promised that the team will call back or do something.\n"
        . "  \"none\": only questions, a wrong number, or nothing for the team to do.\n"
        . "\"name\": the caller's name in English letters, or \"\".\n"
        . "\"vehicle\": the vehicle number in capital English letters and digits without spaces, or \"\".\n"
        . "\"location\": the place or area the caller gave, in English letters, or \"\".\n"
        . "\"product\": the product they want, or \"\".\n"
        . "\"plan\": for a renewal, \"3 months\", \"6 months\" or \"1 year\", or \"\".\n"
        . "\"email\": only if the agent offered to send the discount booking form by email, read an email ID back, and the caller clearly said it is correct: that email ID, rebuilt from what the agent last read back: join the letters and numbers she spelled out, turn the words at, dot, underscore and their Telugu spellings (ఎట్, డాట్) into the signs, and write it in small English letters with no spaces, like ravi123@gmail.com. In every other case \"\".\n"
        . "\"summary\": one short English sentence saying what the caller needs from the team.\n\nThe call:\n" . mb_substr($talk, -6000);
    $key = sarvam_key();
    $text = '';
    foreach ([['model' => 'sarvam-105b-conversations'], ['model' => 'sarvam-105b', 'reasoning_effort' => 'low']] as $m) {
        $r = http_post('https://api.sarvam.ai/v1/chat/completions', ["Authorization: Bearer $key", "api-subscription-key: $key", 'Content-Type: application/json'],
            json_encode($m + ['messages' => [['role' => 'user', 'content' => $ask]], 'temperature' => 0, 'max_tokens' => 1500], JSON_UNESCAPED_UNICODE), 20);
        $text = preg_replace('~<think>.*?(</think>|$)~su', '', (string)($r['data']['choices'][0]['message']['content'] ?? ''));
        if (preg_match('~\{.*\}~su', $text, $m2) && is_array($j = json_decode($m2[0], true))) {
            $out = $none;
            foreach ($none as $k => $v) $out[$k] = clean_line(is_scalar($j[$k] ?? '') ? (string)($j[$k] ?? '') : '', $k === 'summary' ? 300 : 120);
            $out['type'] = in_array(strtolower($out['type']), ['renewal', 'troubleshoot', 'readd', 'new', 'other', 'none'], true) ? strtolower($out['type']) : 'none';
            $out['email'] = strtolower(preg_replace('~\s+~', '', $out['email']));
            if (!filter_var($out['email'], FILTER_VALIDATE_EMAIL)) $out['email'] = '';
            $out['vehicle'] = plate_key($out['vehicle']);
            if ($out['vehicle'] === '' && !empty($c['vehicles'])) $out['vehicle'] = (string)end($c['vehicles']);
            return $out;
        }
    }
    return $none + ['failed' => true];
}

// The nearest technician to the caller's place, as a suggestion for whoever assigns the task. The task itself stays
// unassigned: a misheard place or a technician on leave must not send the work to the wrong person.
function tech_suggest(string $place): string {
    if (trim($place) === '') return 'none, the caller did not give a place';
    $a = area_check($place);
    switch ($a['status']) {
        case 'in': return "{$a['tech']}" . ($a['tech_place'] !== '' ? " ({$a['tech_place']})" : '') . ", about {$a['km']} km from {$a['label']}";
        case 'out': return "{$a['tech']}" . ($a['tech_place'] !== '' ? " ({$a['tech_place']})" : '') . " is the nearest, about {$a['km']} km from {$a['label']}, which is outside the {$a['limit']} km service area";
        case 'off': return 'none, no technician is added on the Service areas page of the call agent';
        case 'unknown': return "none, the place \"{$a['place']}\" was not found on the map";
        default: return 'none, the map could not be checked';
    }
}
// What the technician or the team has to do, in one line, for the task remarks.
function task_todo(array $x): string {
    $v = ($x['vehicle'] ?? '') !== '' ? " for vehicle {$x['vehicle']}" : '';
    $p = ($x['product'] ?? '') !== '' ? $x['product'] : 'GPS tracker';
    switch ($x['type']) {
        case 'new': return "Call the customer, confirm the time, then install or deliver: $p.";
        case 'troubleshoot': return "Check why the GPS is not working$v. Call the customer to fix the visit time.";
        case 'readd': return "Re-add the GPS$v: confirm re-installation of the same GPS or a new one with the customer.";
        default: return 'Call the customer back about their request.';
    }
}

// Everything that happens once a real phone call is over: understand it, raise a task in TaskManager for work
// that needs the team (never for renewals), and send the team one email. Returns the fields to keep on the call.
function call_wrap(array $c): array {
    $answered = !isset($c['answered']) || $c['answered'];
    $x = $answered ? call_extract($c) : ['type' => 'none'];
    $keep = ['outcome' => $x['type']];
    if (!empty($x['failed'])) $keep['extract_failed'] = 1;
    foreach (['name', 'vehicle', 'location', 'plan', 'summary'] as $k) if (($x[$k] ?? '') !== '') $keep[$k] = $x[$k];
    $from = (string)($c['from'] ?? '');
    // The caller confirmed an email ID for the complimentary discount: send the Quick Task booking form.
    $formSent = false;
    if (FORM_ON && ($x['email'] ?? '') !== '') {
        $formSent = form_mail($x['email'], (string)($x['name'] ?? ''), $from);
        $keep['form_email'] = $x['email'];
        $keep['form_sent'] = $formSent ? 1 : -1;
        $keep['summary'] = trim(($keep['summary'] ?? '') . ($formSent ? " Booking form with Rs " . FORM_DISCOUNT . " discount emailed to {$x['email']}." : " The booking form could NOT be emailed to {$x['email']}."));
    }
    // She tells the customer the booking is created, so the task is always made, even when the form was emailed too:
    // a booking must not be lost because the customer never opens the email. The task summary says the form was sent.
    if (in_array($x['type'], ['troubleshoot', 'readd', 'new', 'other'], true)) {
        if ($from === '') $keep['task_error'] = 'The caller number was hidden, so no task was made.';
        else {
            $note = '';
            if ($x['vehicle'] !== '' && gps_on()) {
                $g = gps_find($x['vehicle']);
                if ($g['status'] === 'found') $note = "{$g['name']} on {$g['host']}" . (($g['expiry'] ?? '') !== '' ? ", expiry {$g['expiry_text']}" : '') . (!empty($g['online']) ? ', online now' : (($g['last'] ?? '') !== '' ? ", last online {$g['last']}" : ''));
                elseif ($g['status'] === 'none') $note = 'not found on any server';
            }
            $talk = '';
            foreach ((array)$c['turns'] as $t) $talk .= (($t['who'] ?? '') === 'agent' ? 'AI: ' : 'Caller: ') . $t['text'] . "\n";
            $r = http_post(TASK_URL, [], ['key' => task_key(), 'type' => $x['type'], 'phone' => $from, 'call_id' => (string)$c['id'], 'name' => $x['name'], 'location' => $x['location'],
                'vehicle' => $x['vehicle'], 'product' => $x['product'], 'summary' => (string)($keep['summary'] ?? $x['summary']), 'server_note' => $note, 'transcript' => mb_substr($talk, 0, 2800),
                'suggest' => tech_suggest((string)$x['location']), 'todo' => task_todo($x)], 20);
            if ($r['code'] === 200 && !empty($r['data']['task_id'])) $keep['task_id'] = (string)$r['data']['task_id'];
            else $keep['task_error'] = (string)($r['data']['error'] ?? ($r['err'] ?: 'TaskManager did not answer (' . $r['code'] . ').'));
        }
    }
    $keep['mailed'] = MAIL_ON ? (lead_mail($c + $keep) ? 1 : -1) : 3;   // 3: finished, emails are switched off
    return $keep;
}

// ---------- booking form email to the customer ----------
// The same Quick Task enquiry form the team sends on WhatsApp from TaskManager. When the customer submits it,
// TaskManager creates the task, unassigned, with the discount applied.
const FORM_URL = 'https://salmon-goldfish-110661.hostingersite.com/enquiry.php';
const FORM_DISCOUNT = 200;
const FORM_ON = false;   // the owner stopped the email form and the discount (16 Nov 2026): hearing email IDs on a call was unreliable
function form_mail(string $email, string $name, string $phone): bool {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || preg_match('~[\r\n,;<>]~', $email)) return false;
    // First choice: TaskManager sends it from the company mailbox, like all its other emails, so it is not
    // dropped or put in spam the way a plain email from this website can be.
    $r = http_post(TASK_URL, [], ['key' => task_key(), 'form_email' => $email, 'name' => $name, 'phone' => $phone, 'discount' => FORM_DISCOUNT], 25);
    if ($r['code'] === 200 && !empty($r['data']['sent'])) return true;
    // TaskManager did not send it (not updated yet, or its mailbox failed): send a plain email from this website.
    $url = FORM_URL . '?name=' . rawurlencode($name) . '&phone=' . rawurlencode($phone) . '&disc=' . FORM_DISCOUNT;
    $body = 'Hello' . ($name !== '' ? " $name" : '') . "!\n\nThank you for your interest in Bharat GPS Tracker.\n\n"
        . 'Special offer: Rs.' . FORM_DISCOUNT . " OFF - applied automatically when you fill this form.\n\n"
        . "Please fill your details to book your GPS installation:\n\n$url\n\n"
        . "The form takes 2 minutes. Our team will contact you to schedule.\n\nFor help call: 9849849824\n\nBharat GPS Tracker";
    $host = preg_replace('~[^a-z0-9.-]~', '', strtolower(preg_replace('~^www\.|:\d+$~', '', (string)($_SERVER['HTTP_HOST'] ?? '')))) ?: 'someswara.com';
    $subject = '=?UTF-8?B?' . base64_encode('Bharat GPS Tracker: your booking form with Rs.' . FORM_DISCOUNT . ' discount') . '?=';
    $headers = "From: Bharat GPS Tracker <noreply@$host>\r\nReply-To: sales@bharatgps.com\r\nMIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64";
    return function_exists('mail') && @mail($email, $subject, chunk_split(base64_encode($body)), $headers);
}

// ---------- lead email ----------
// One email to the team after every real phone call: who called, what they need, and the full talk.
function lead_mail(array $c): bool {
    $from = (string)($c['from'] ?? '');
    $num = $from !== '' ? '+91 ' . substr($from, 0, 5) . ' ' . substr($from, 5) : 'Number not shown';
    $s = call_summary($c);
    $answered = !isset($c['answered']) || $c['answered'];
    $spoke = $s['asked'] !== '';
    $kind = ['renewal' => 'Renewal request', 'troubleshoot' => 'GPS complaint', 'readd' => 'Re-adding request', 'new' => 'New customer', 'other' => 'Customer request'][$c['outcome'] ?? ''] ?? '';
    $what = !$answered ? 'Missed call, she asked them to wait for a call back' : ($kind !== '' ? $kind : ($spoke ? 'Call, nothing to do' : 'Caller hung up without speaking'));
    $tail = trim((($c['vehicle'] ?? '') !== '' ? $c['vehicle'] : '') . (($c['plan'] ?? '') !== '' ? ', ' . $c['plan'] . ' plan' : ''), ', ');
    $body = "$what\n\nCaller: $num\nTime: " . date('j M Y, g:i A', (int)$c['started']) . "\nLength: {$s['seconds']} seconds\n";
    if ($from !== '') $body .= "Call back: tel:+91$from\nWhatsApp: https://wa.me/91$from\n";
    foreach (['name' => 'Name', 'vehicle' => 'Vehicle number', 'plan' => 'Plan chosen', 'location' => 'Location', 'summary' => 'What they need'] as $k => $label) if (($c[$k] ?? '') !== '') $body .= "$label: {$c[$k]}\n";
    if (($c['outcome'] ?? '') === 'renewal') $body .= "To do: send the payment link on WhatsApp and email. She told the caller it is coming.\n";
    if (($c['task_id'] ?? '') !== '') $body .= "Task created in TaskManager: {$c['task_id']} (not assigned)\n";
    if (($c['task_error'] ?? '') !== '') $body .= "A task could NOT be created in TaskManager: {$c['task_error']} Please create it by hand.\n";
    if ($spoke && $kind === '') $body .= "First thing they said: {$s['asked']}\n";
    $body .= "\nThe call:\n";
    foreach ($c['turns'] as $t) $body .= ($t['who'] === 'agent' ? 'Agent: ' : 'Caller: ') . $t['text'] . "\n";
    $host = preg_replace('~[^a-z0-9.-]~', '', strtolower(preg_replace('~^www\.|:\d+$~', '', (string)($_SERVER['HTTP_HOST'] ?? '')))) ?: 'someswara.com';
    $subject = '=?UTF-8?B?' . base64_encode($what . ($tail !== '' ? ": $tail" : '') . (($c['task_id'] ?? '') !== '' ? " [{$c['task_id']}]" : '') . " ($num)") . '?=';
    $to = MAIL_TO[$c['outcome'] ?? 'none'] ?? MAIL_TO['none'];
    $headers = "From: Bharat GPS call agent <noreply@$host>" . (MAIL_COPY !== '' ? "\r\nCc: " . MAIL_COPY : '') . "\r\nMIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64";
    return function_exists('mail') && @mail(implode(', ', $to), $subject, chunk_split(base64_encode($body)), $headers);
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
        'speaker' => $speaker, 'speech_sample_rate' => 24000, 'output_audio_codec' => 'mp3', 'pace' => 1.3];
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
