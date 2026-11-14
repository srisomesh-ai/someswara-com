'use strict';
// Bharat GPS Tracker: live call server.
// Keeps one open connection per call so the agent can hear, think and speak without the
// stop-and-wait of the web-hosting version: the caller's audio streams to Sarvam speech-to-text
// while they talk, the answer streams back from the chat model, and each sentence is spoken as
// soon as it is written. The caller can interrupt at any time.
//
// It holds no settings of its own. For each call the dashboard gives the browser a one-time
// token; this server exchanges it with the dashboard (IVR_API) for the Sarvam key, voice,
// language, greeting and the agent's instructions, and sends the transcript back the same way.

const http = require('http');
const fs = require('fs');
const path = require('path');
const { WebSocketServer, WebSocket } = require('ws');

// Goes up by one whenever this file changes. The dashboard compares it with LIVE_VERSION in lib.php and tells the
// owner when the copy running on the VPS is older than the website, so a forgotten installer run is noticed.
const VERSION = 14;
const PORT = +(process.env.PORT || 8790);
const IVR_API = process.env.IVR_API || 'https://someswara.com/ivr/api.php';
const S_HTTP = process.env.SARVAM_HTTP || 'https://api.sarvam.ai';
const S_WS = process.env.SARVAM_WS || 'wss://api.sarvam.ai';
const ORIGINS = (process.env.ALLOW_ORIGINS || 'https://someswara.com,https://www.someswara.com').split(',');
const MAX_CALLS = 5;
const MAX_CALL_MS = 20 * 60 * 1000;
const MAX_UTTERANCE_BYTES = 16000 * 2 * 30;   // 30 seconds of 16 kHz 16-bit audio

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
const log = (...a) => console.log(new Date().toISOString(), ...a);

// ---------- dashboard ----------
async function ivr(action, fields, ms) {
  const r = await fetch(IVR_API + '?action=' + action + '&sv=' + VERSION, { method: 'POST', body: new URLSearchParams(fields), signal: AbortSignal.timeout(ms || 8000) });
  const d = await r.json().catch(() => ({}));
  if (!r.ok) throw new Error(d.error || 'Dashboard answered ' + r.status);
  return d;
}

// ---------- Sarvam ----------
// Text shown in the transcript is not always safe to read aloud; this makes a clean copy for the voice.
function speechText(t) {
  t = t.replace(/[\u{1F000}-\u{1FAFF}\u{2600}-\u{27BF}\u{FE0F}\u{200D}]/gu, '');
  for (const [a, b] of [['₹', 'rupees '], ['Rs.', 'rupees '], ['Rs ', 'rupees '], ['%', ' percent'], ['&', ' and '], ['@', ' at ']]) t = t.split(a).join(b);
  t = t.replace(/["“”‘’()\[\]{}<>|~^=]/g, ' ').replace(/…|\.\.\.|\.\./g, '.');
  t = t.replace(/\s+[-–—]+\s+/g, ', ').replace(/[–—]/g, ', ').replace(/[\/\\]/g, ' ').replace(/[;:]/g, ',');
  t = t.replace(/\s+([,.?!])/g, '$1').replace(/([,.?!])[,.?!]+/g, '$1');
  t = t.replace(/\s+/g, ' ').replace(/^[ ,]+|[ ,]+$/g, '');
  if (t && !/[.?!]$/.test(t)) t += '.';
  return t;
}

async function tts(cfg, text, signal, lang) {
  const voice = (cfg.voices && cfg.voices[lang]) || cfg.voice;      // each language has its own voice
  const code = (cfg.codes && cfg.codes[lang]) || cfg.lang_code;
  const body = {
    text: speechText(text).slice(0, 2400), language_code: code,
    model: voice.includes('_') ? 'bulbul:v4-flash' : 'bulbul:v3',
    speaker: voice, speech_sample_rate: cfg.pcm_rate || 24000, pace: 1.15,
  };
  if (!cfg.pcm_rate) body.output_audio_codec = 'mp3';   // browser gets MP3; a phone line gets the default WAV
  const post = () => fetch(S_HTTP + '/text-to-speech', {
    method: 'POST', headers: { 'Content-Type': 'application/json', 'api-subscription-key': cfg.key },
    body: JSON.stringify(body), signal: AbortSignal.any([signal, AbortSignal.timeout(15000)]),
  });
  let r = await post();
  if (r.status === 400 || r.status === 422) {   // compressed audio refused: fall back to WAV
    delete body.output_audio_codec;
    const r2 = await post();
    if (r2.ok) r = r2;
  }
  if (!r.ok) throw new Error('Speaking failed (' + r.status + ')');
  const audio = ((await r.json()).audios || [])[0];
  if (!audio) throw new Error('Speaking failed: no audio came back');
  return audio;
}

function wav(pcm, rate) {
  const h = Buffer.alloc(44);
  h.write('RIFF', 0); h.writeUInt32LE(36 + pcm.length, 4); h.write('WAVEfmt ', 8);
  h.writeUInt32LE(16, 16); h.writeUInt16LE(1, 20); h.writeUInt16LE(1, 22);
  h.writeUInt32LE(rate, 24); h.writeUInt32LE(rate * 2, 28); h.writeUInt16LE(2, 32); h.writeUInt16LE(16, 34);
  h.write('data', 36); h.writeUInt32LE(pcm.length, 40);
  return Buffer.concat([h, pcm]);
}

// Whole-utterance speech-to-text, used when the streaming connection is not available.
async function sttRest(cfg, pcm, rate) {
  const fd = new FormData();
  fd.append('model', 'saaras:v3'); fd.append('mode', 'codemix');
  fd.append('file', new Blob([wav(pcm, rate)], { type: 'audio/wav' }), 'caller.wav');
  const r = await fetch(S_HTTP + '/speech-to-text', { method: 'POST', headers: { 'api-subscription-key': cfg.key }, body: fd, signal: AbortSignal.timeout(15000) });
  if (!r.ok) throw new Error('Listening failed (' + r.status + ')');
  const d = await r.json();
  return { text: String(d.transcript || '').trim(), lang: String(d.language_code || '') };
}

// Which of her languages did the caller just speak? Telugu and Hindi are recognised by their script.
// English needs a few words, so a stray "hello" or "okay" does not flip a Telugu or Hindi call.
function detectLang(text, sttCode, current) {
  const te = (text.match(/[\u0C00-\u0C7F]/g) || []).length, hi = (text.match(/[\u0900-\u097F]/g) || []).length;
  if (te || hi) return te >= hi ? 'te' : 'hi';
  const heard = { 'te-IN': 'te', 'hi-IN': 'hi', 'en-IN': 'en' }[sttCode];
  const words = (text.match(/[A-Za-z]{2,}/g) || []).length;
  if (heard === 'en' || !heard) return words >= 4 ? 'en' : current;
  return heard;
}

const chatHeaders = (cfg) => ({ 'Content-Type': 'application/json', Authorization: 'Bearer ' + cfg.key, 'api-subscription-key': cfg.key });

// The answer as it is being written, piece by piece.
async function* chatStream(cfg, messages, signal) {
  const r = await fetch(S_HTTP + '/v1/chat/completions', {
    method: 'POST', headers: chatHeaders(cfg), signal: AbortSignal.any([signal, AbortSignal.timeout(20000)]),
    body: JSON.stringify({ model: 'sarvam-105b-conversations', messages, temperature: 0.4, max_tokens: 2000, stream: true }),
  });
  if (!r.ok) throw new Error('Thinking failed (' + r.status + ')');
  const dec = new TextDecoder();
  let buf = '';
  for await (const chunk of r.body) {
    buf += dec.decode(chunk, { stream: true });
    let i;
    while ((i = buf.indexOf('\n')) >= 0) {
      const line = buf.slice(0, i).trim();
      buf = buf.slice(i + 1);
      if (!line.startsWith('data:')) continue;
      const data = line.slice(5).trim();
      if (data === '[DONE]') return;
      try { const c = JSON.parse(data).choices?.[0]?.delta?.content; if (c) yield c; } catch (e) { /* skip a broken piece */ }
    }
  }
}

// Backup: the main model, whole answer at once.
async function chatOnce(cfg, messages, signal) {
  const r = await fetch(S_HTTP + '/v1/chat/completions', {
    method: 'POST', headers: chatHeaders(cfg), signal: AbortSignal.any([signal, AbortSignal.timeout(20000)]),
    body: JSON.stringify({ model: 'sarvam-105b', messages, temperature: 0.4, max_tokens: 2000, reasoning_effort: 'low' }),
  });
  if (!r.ok) throw new Error('Thinking failed (' + r.status + ')');
  return String((await r.json()).choices?.[0]?.message?.content || '');
}

// What she may say aloud: her private thinking, a "remember this rule" tag and a "check this vehicle" tag are taken out.
// Whatever brackets, colon or hidden characters the model types, a tag must never be heard or shown.
const plain = (raw) => raw.normalize('NFKC').replace(/[​-‍⁠﻿\\]/g, '').replace(/<think>[\s\S]*?(<\/think>|$)/g, '');
const cleanReply = (raw) => plain(raw).replace(/[\[【〔〚⟦]{2}[\s\S]*?([\]】〕〛⟧]{2}|$)/g, '')
  .replace(/[\[【〔〚⟦(]\s*(RULE|VEHICLE)\b[\s\S]*?([\]】〕〛⟧)]|$)/gi, '').replace(/\S*\b(VEHICLE|RULE)\s*[:=]\s*[A-Za-z0-9 .-]*[^\s\w.?!,]*/g, '').replace(/[*#`_]/g, '');
// The vehicle number inside her "check this vehicle" tag. VEHICLE in capitals followed by a colon is only ever the tag.
function vehicleTag(raw) {
  const m = plain(raw).match(/\bVEHICLE\s*[:=]\s*([A-Za-z0-9 .-]{1,30})/);
  return m ? m[1].replace(/[^A-Za-z0-9]/g, '').toUpperCase() : '';
}
// A whole number plate: AP39BP0042, TS09EK1111, or the newer 22BH1234AA. Four digits alone are not enough to find a vehicle.
const fullPlate = (n) => /^[A-Z]{2}\d{1,2}[A-Z]{0,3}\d{4}$/.test(n) || /^\d{2}BH\d{4}[A-Z]{1,2}$/.test(n);

// ---------- sir or madam ----------
// How high the caller's voice is, measured on short slices of what they said. A phone line removes the
// lowest tones, so the repeat pattern of the sound wave is used instead; that survives the line.
function voicePitch(pcm, rate) {
  const step = rate >= 16000 ? 2 : 1, sr = rate / step, n = Math.min(pcm.length >> 1, rate * 2) / step | 0;   // the first two seconds are enough
  const x = new Float32Array(n);
  for (let i = 0; i < n; i++) x[i] = pcm.readInt16LE(i * step * 2) / 32768;
  const W = Math.round(sr * 0.04), H = Math.round(sr * 0.03), lo = Math.floor(sr / 300), hi = Math.ceil(sr / 75);
  const out = [], r = new Float32Array(hi + 2);
  for (let s = 0; s + W + hi < n; s += H) {
    let e = 0;
    for (let i = 0; i < W; i++) e += x[s + i] * x[s + i];
    if (e / W < 0.0004) continue;                         // too quiet to be speech
    let best = 0;
    for (let lag = lo; lag <= hi + 1; lag++) {
      let a = 0, b = 0;
      for (let i = 0; i < W; i++) { const y = x[s + i + lag] || 0; a += x[s + i] * y; b += y * y; }
      r[lag] = a / Math.sqrt(e * b + 1e-9);
      if (lag <= hi && r[lag] > best) best = r[lag];
    }
    if (best < 0.6) continue;                             // not a clear voiced sound
    for (let lag = lo + 1; lag <= hi; lag++) {
      if (r[lag] >= 0.9 * best && r[lag] >= r[lag - 1] && r[lag] >= r[lag + 1]) { out.push(sr / lag); break; }
    }
  }
  return out;
}

// Decide only when the voice is clearly low or clearly high; in between she stays with the neutral word.
function hearGender(S, pcm) {
  try {
    S.pitch.push(...voicePitch(pcm, S.rate));
    if (S.pitch.length > 600) S.pitch = S.pitch.slice(-600);
    if (S.pitch.length < 40) return;
    const sorted = [...S.pitch].sort((a, b) => a - b), mid = sorted[sorted.length >> 1], low = sorted[sorted.length >> 2];
    const was = S.gender;
    // Many men speak as high as 160 to 170 on a call. Madam only when the voice is clearly high all through;
    // calling a man madam is worse than the neutral word.
    S.gender = mid < 168 ? 'm' : (mid > 200 && low > 178) ? 'f' : '';
    if (S.gender !== was) log('call', S.cfg.call_id, 'caller voice about', Math.round(mid), 'Hz:', S.gender === 'm' ? 'sir' : S.gender === 'f' ? 'madam' : 'not sure');
  } catch (e) { /* never let this stop a call */ }
}

// ---------- quick acknowledgement ----------
// The moment the caller stops, she says a tiny "ok sir" while the real answer is still being prepared,
// so there is no dead silence. These few clips are made once and kept ready.
const ACKS = {
  te: { m: ['ఓకే సర్', 'సరే సర్', 'అలాగే సర్'], f: ['ఓకే మేడమ్', 'సరే మేడమ్', 'అలాగే మేడమ్'], '': ['ఓకే అండి', 'సరే అండి', 'అలాగే అండి'] },
  hi: { m: ['ओके सर', 'जी सर', 'ठीक है सर'], f: ['ओके मैडम', 'जी मैडम', 'ठीक है मैडम'], '': ['ओके जी', 'जी', 'ठीक है जी'] },
  en: { m: ['Okay sir', 'Sure sir', 'Alright sir'], f: ['Okay madam', 'Sure madam', 'Alright madam'], '': ['Okay', 'Sure', 'Alright'] },
};
const clips = new Map();
const clipKey = (cfg, lang, text) => [(cfg.voices && cfg.voices[lang]) || cfg.voice, cfg.pcm_rate || 'mp3', text].join('|');

// Made one after another, the ones needed first at the front. Asking for all of them at once was being
// refused by the voice service, so the quick "ok" was often missing in the first minute of a call.
let warming = Promise.resolve();
function warmAcks(S, lang) {
  if (!ACKS[lang] || !S.cfg.prompts) return;
  if (clips.size > 300) clips.clear();
  const order = [S.gender, '', 'm', 'f'].filter((g, i, all) => all.indexOf(g) === i);
  for (const g of order) for (const text of ACKS[lang][g]) {
    const k = clipKey(S.cfg, lang, text);
    if (clips.has(k)) continue;
    clips.set(k, null);                                   // being made
    const cfg = S.cfg;
    warming = warming.then(() => tts(cfg, text, AbortSignal.timeout(15000), lang)).then((a) => { clips.set(k, a); }).catch(() => { clips.delete(k); });
  }
}

// A reply that opens with the same kind of reaction she has just spoken ("ok sir. సరే అండి, ...") sounds like a repeat.
const SAME_START = /^\s*(ఓకే|ఒకే|సరే|అలాగే|హా|ok|okay|sure|alright|ओके|जी|ठीक है|अच्छा)(\s+(అండి|సర్|మేడమ్|మేడం|sir|madam|सर|मैडम|जी))?\s*[,.!।]\s*/i;

// Plays one if it is ready and returns the words; never waits for one.
function sayAck(S) {
  const list = ACKS[S.lang] && ACKS[S.lang][S.gender];
  if (!list || !S.cfg.prompts) return '';
  const text = list[S.ackN++ % list.length], clip = clips.get(clipKey(S.cfg, S.lang, text));
  if (!clip) { warmAcks(S, S.lang); return ''; }
  send(S, { type: 'audio', audio: clip });
  return text;
}

// ---------- call recordings ----------
// Every answered phone call is kept as one sound file with both voices, for the owner to listen to from the
// dashboard. Files stay in the rec folder beside this program for 30 days. The caller's audio arrives in real
// time, so it is the clock; her audio is placed at the moment it was sent to the phone line.
const REC_DIR = path.join(__dirname, 'rec'), REC_DAYS = 30;
const recBytes = (S, ms) => Math.floor(ms * S.rate / 1000) * 2;
function recStart(S) {
  S.rec = { t0: Date.now(), caller: [], callerEnd: 0, agent: [], agentEnd: 0, max: recBytes(S, MAX_CALL_MS + 60000) };
}
function recCaller(S, pcm) {
  const R = S.rec; if (!R || R.callerEnd >= R.max) return;
  const now = recBytes(S, Date.now() - R.t0) - pcm.length;
  if (now - R.callerEnd > S.rate * 2 * 0.3) R.callerEnd = now;          // the line was silent for a while: leave the gap
  R.caller.push({ at: R.callerEnd, pcm }); R.callerEnd += pcm.length;
}
function recAgent(S, pcm) {
  const R = S.rec; if (!R || R.agentEnd >= R.max) return;
  const at = Math.max(recBytes(S, Date.now() - R.t0), R.agentEnd);     // clips queue up one after another on the line
  R.agent.push({ at, pcm }); R.agentEnd = at + pcm.length;
}
function recCut(S) {                                                    // the caller interrupted: the rest of her audio was dropped
  const R = S.rec; if (!R) return;
  const now = recBytes(S, Date.now() - R.t0);
  R.agent = R.agent.filter((c) => c.at < now).map((c) => (c.at + c.pcm.length > now ? { at: c.at, pcm: c.pcm.subarray(0, now - c.at) } : c));
  R.agentEnd = Math.min(R.agentEnd, now);
}
// Mix both voices into one WAV file. Returns its length in seconds, or 0 when nothing worth keeping was said.
async function recSave(S) {
  const R = S.rec; S.rec = null;
  if (!R || !R.caller.length) return 0;
  const total = Math.min(Math.max(R.callerEnd, R.agentEnd), R.max), n = total >> 1;
  if (n < S.rate * 3) return 0;
  const mix = new Int16Array(n);
  for (const track of [R.caller, R.agent]) for (const c of track) {
    const start = c.at >> 1, len = Math.min(c.pcm.length >> 1, n - start);
    for (let i = 0; i < len; i++) { const v = mix[start + i] + c.pcm.readInt16LE(i * 2); mix[start + i] = v > 32767 ? 32767 : v < -32768 ? -32768 : v; }
  }
  const head = Buffer.alloc(44), data = Buffer.from(mix.buffer, mix.byteOffset, n * 2);
  head.write('RIFF', 0); head.writeUInt32LE(36 + data.length, 4); head.write('WAVEfmt ', 8); head.writeUInt32LE(16, 16); head.writeUInt16LE(1, 20); head.writeUInt16LE(1, 22);
  head.writeUInt32LE(S.rate, 24); head.writeUInt32LE(S.rate * 2, 28); head.writeUInt16LE(2, 32); head.writeUInt16LE(16, 34); head.write('data', 36); head.writeUInt32LE(data.length, 40);
  await fs.promises.mkdir(REC_DIR, { recursive: true });
  await fs.promises.writeFile(path.join(REC_DIR, S.cfg.call_id + '.wav'), Buffer.concat([head, data]));
  return Math.round(n / S.rate);
}
async function recClean() {
  try {
    const old = Date.now() - REC_DAYS * 86400000;
    for (const f of await fs.promises.readdir(REC_DIR)) {
      const file = path.join(REC_DIR, f);
      if (/\.wav$/.test(f) && (await fs.promises.stat(file)).mtimeMs < old) await fs.promises.unlink(file);
    }
  } catch (e) { /* no recordings yet */ }
}
recClean(); setInterval(recClean, 6 * 3600000).unref();

// The dashboard hands the browser a short-lived pass for one recording; this server checks it with the dashboard.
const recPass = new Map();
async function recServe(req, res, url) {
  const id = (url.pathname.match(/^\/rec\/([a-f0-9]{16})\.wav$/) || [])[1], t = url.searchParams.get('t') || '';
  const no = (code) => { res.writeHead(code, { 'Content-Type': 'text/plain' }); res.end(); };
  if (!id || !/^[a-f0-9]{48}$/.test(t) || (req.method !== 'GET' && req.method !== 'HEAD')) return no(404);
  const known = recPass.get(t);
  if (!known || known.exp < Date.now() || known.id !== id) {
    try { await ivr('rec_check', { t, id }); } catch (e) { return no(403); }
    if (recPass.size > 500) recPass.clear();
    recPass.set(t, { id, exp: Date.now() + 30 * 60000 });
  }
  const file = path.join(REC_DIR, id + '.wav');
  let size; try { size = (await fs.promises.stat(file)).size; } catch (e) { return no(404); }
  const h = { 'Content-Type': 'audio/wav', 'Accept-Ranges': 'bytes', 'Cache-Control': 'private, max-age=600' };
  const m = /^bytes=(\d*)-(\d*)$/.exec(req.headers.range || '');
  let from = 0, to = size - 1, code = 200;
  if (m && (m[1] || m[2])) {
    if (m[1]) { from = +m[1]; if (m[2]) to = Math.min(+m[2], size - 1); } else from = Math.max(0, size - +m[2]);
    if (from > to || from >= size) { res.writeHead(416, { 'Content-Range': 'bytes */' + size }); return res.end(); }
    code = 206; h['Content-Range'] = 'bytes ' + from + '-' + to + '/' + size;
  }
  h['Content-Length'] = to - from + 1;
  res.writeHead(code, h);
  if (req.method === 'HEAD') return res.end();
  fs.createReadStream(file, { start: from, end: to }).on('error', () => res.destroy()).pipe(res);
}

// Why an existing customer is calling, from their own words: 'trouble' (GPS not working), 'renew' (validity, renewal),
// or '' when it is not clear. She is then given only that part of what the GPS server shows.
const TROUBLE_WORDS = /offline|ఆఫ్\s?లైన్|not working|పని\s?చేయ|work అవ్వ|రావడం లేదు|రావట్లేదు|చూపించ(డం|ట్లే)|complaint|కంప్లైంట్|problem|ప్రాబ్లం|काम नहीं|बंद है|ऑफलाइन|शिकायत|दिख नहीं/i;
const RENEW_WORDS = /renew|రెన్యూ|రిన్యూ|రెన్యువల్|expir|validity|వాలిడిటీ|licen[sc]e|లైసెన్స్|recharge|రీఛార్జ్|రీచార్జ్|नवीनीकरण|रिन्यू|रिचार्ज|वैलिडिटी/i;
function whyCalling(S) {
  const said = S.turns.filter((t) => t.who === 'caller').map((t) => t.text).join(' ');
  const t = TROUBLE_WORDS.test(said), r = RENEW_WORDS.test(said);
  return t && !r ? 'trouble' : r && !t ? 'renew' : '';
}

// ---------- only the instructions this call needs ----------
// The dashboard marks two parts of her instructions: what is only for new customers (selling steps, the product
// list) and what is only for existing ones (complaints, renewals, the vehicle check). Once the caller's words show
// clearly which they are, the other part is left out, which makes every reply cheaper. When it is not clear, or the
// caller touches both, she gets everything.
const OLD_WORDS = /renew|రెన్యూ|రిన్యూ|రెన్యువల్|expir|validity|వాలిడిటీ|licen[sc]e|లైసెన్స్|recharge|రీఛార్జ్|రీచార్జ్|offline|ఆఫ్\s?లైన్|not working|పని\s?చేయ|work అవ్వ|రావడం లేదు|రావట్లేదు|complaint|కంప్లైంట్|already|ఆల్రెడీ|नवीनीकरण|रिन्यू|रिचार्ज|काम नहीं|बंद है|ऑफलाइन|शिकायत/i;
const NEW_WORDS = /కొత్త|\bnew\b|price|ధర|\brate\b|రేట్|రేటు|ఎంత|\bcost\b|\bbuy\b|కొనా|కొనుక్కో|purchase|कीमत|दाम|नया|नई|खरीद|कितन/i;
function noteKind(S, text) {
  if (S.kind === 'all') return;
  const o = OLD_WORDS.test(text), n = NEW_WORDS.test(text);
  if (o && n) S.kind = 'all';
  else if (o) S.kind = S.kind === 'new' ? 'all' : 'old';
  else if (n) S.kind = S.kind === 'old' ? 'all' : 'new';
}
function promptFor(S, lang) {
  const full = (S.cfg.prompts && S.cfg.prompts[lang]) || S.cfg.prompt || '';
  const key = lang + '|' + S.kind;
  if (S.promptCache[key] === undefined) {
    let p = full;
    if (S.kind === 'new') p = p.replace(/<<OLD>>[\s\S]*?<<\/OLD>>\n?/g, '');
    if (S.kind === 'old') p = p.replace(/<<NEW>>[\s\S]*?<<\/NEW>>\n?/g, '');
    S.promptCache[key] = p.replace(/<<\/?(NEW|OLD)>>\n?/g, '').replace(/\n{3,}/g, '\n\n');
  }
  return S.promptCache[key];
}

// ---------- one call ----------
function send(S, msg) {
  if (S.out) return S.out(msg);              // a phone call has its own way of sending (see phoneOut)
  if (S.ws.readyState === WebSocket.OPEN) S.ws.send(JSON.stringify(msg));
}

async function syncCall(S, ended, recSecs) {
  try { await ivr('live_sync', { token: S.token, turns: JSON.stringify(S.turns), ended: ended ? '1' : '0', rec_secs: String(recSecs || 0) }); }
  catch (e) { log('sync failed:', e.message); }
}

// Streaming speech-to-text: audio goes up while the caller talks, so the words are ready when they stop.
function openStt(S) {
  if (S.closed || (S.stt && S.stt.readyState <= WebSocket.OPEN)) return;
  const q = new URLSearchParams({ 'language-code': S.cfg.prompts ? 'unknown' : S.cfg.lang_code, model: 'saaras:v3', mode: 'codemix', sample_rate: String(S.rate), input_audio_codec: 'pcm_s16le', flush_signal: 'true' });
  const stt = new WebSocket(S_WS + '/speech-to-text/ws?' + q, { headers: { 'api-subscription-key': S.cfg.key } });
  S.stt = stt;
  stt.on('message', (raw) => {
    let m; try { m = JSON.parse(raw.toString()); } catch (e) { return; }
    if (m.type === 'data') {
      const t = String(m.data?.transcript || '').trim();
      if (t) S.parts.push(t);
      if (t && m.data?.language_code) S.sttLang = m.data.language_code;
      if (S.flushed) S.afterFlush = true;
    } else if (m.type === 'error') log('stt error:', JSON.stringify(m.data));
  });
  stt.on('error', (e) => log('stt socket:', e.message));
}

function speechStart(S) {
  S.gen++;                                   // anything she was about to say is cancelled
  if (S.abort) S.abort.abort();
  S.pcm = []; S.pcmBytes = 0; S.parts = []; S.flushed = false; S.afterFlush = false; S.sttLang = '';
  S.sttLive = !!S.stt && S.stt.readyState === WebSocket.OPEN;   // only trust streaming if it hears the whole utterance
  if (!S.sttLive) openStt(S);
  S.capturing = true;
}

function audioIn(S, buf) {
  if (!S.capturing || S.pcmBytes + buf.length > MAX_UTTERANCE_BYTES) return;
  S.pcm.push(buf); S.pcmBytes += buf.length;
  if (S.sttLive && S.stt.readyState === WebSocket.OPEN) {
    S.stt.send(JSON.stringify({ audio: { data: buf.toString('base64'), sample_rate: String(S.rate), encoding: 'audio/wav' } }));
  }
}

async function speechEnd(S) {
  if (!S.capturing) return;
  S.capturing = false;
  const g = S.gen, t0 = Date.now(), pcm = Buffer.concat(S.pcm);
  S.pcm = []; S.pcmBytes = 0;
  let text = '';
  if (S.sttLive && S.stt.readyState === WebSocket.OPEN) {
    S.flushed = true;
    S.stt.send(JSON.stringify({ type: 'flush' }));
    // Wait for the words after the flush; if some already arrived, a short wait is enough.
    while (!S.afterFlush && Date.now() - t0 < 1200 && !(S.parts.length && Date.now() - t0 > 350)) await sleep(25);
    text = S.parts.join(' ').trim();
  }
  if (!text && pcm.length >= S.rate / 2) {
    try { const r = await sttRest(S.cfg, pcm, S.rate); text = r.text; S.sttLang = r.lang; }
    catch (e) { if (g === S.gen) send(S, { type: 'error', message: e.message }); return; }
  }
  if (g !== S.gen) { if (text) S.carry = (S.carry + ' ' + text).trim(); return; }   // they started talking again: keep the words for the next turn
  text = (S.carry + ' ' + text).trim(); S.carry = '';
  if (!text) { send(S, { type: 'nothing' }); return; }
  const hearMs = Date.now() - t0;
  await S.replying;                          // let a cancelled reply finish recording what it said
  if (g !== S.gen) { S.carry = text; return; }
  if (S.cfg.prompts) {
    // Follow the caller's language, but do not jump on one short or misheard word, because each language has
    // its own voice: change on a clear sentence (four words or more), or when two turns in a row are in it.
    const want = detectLang(text, S.sttLang, S.lang);
    if (want === S.lang) S.langWant = '';
    else {
      const re = { te: /[\u0C00-\u0C7F]+/g, hi: /[\u0900-\u097F]+/g, en: /[A-Za-z]{2,}/g }[want];
      if ((text.match(re) || []).length >= 4 || S.langWant === want) { S.lang = want; S.langWant = ''; }
      else S.langWant = want;
    }
  }
  S.turns.push({ who: 'caller', text });
  send(S, { type: 'heard', text });
  const ack = sayAck(S);                     // first, so nothing delays it
  hearGender(S, pcm);
  noteKind(S, text);
  S.replying = reply(S, hearMs, ack).catch((e) => log('reply crashed:', e.message));
}

async function reply(S, hearMs, ack, afterCheck) {
  const g = S.gen, t1 = Date.now(), lang = S.lang;
  const ctrl = new AbortController(); S.abort = ctrl;
  // Her instructions stay word for word the same through the call, and the things that change from reply to reply
  // go at the very end. Sarvam charges less for text it has already read, so this keeps the cost of each reply down.
  const notes = [];
  if (S.gender) notes.push("The caller's voice sounds like a " + (S.gender === 'm' ? 'man. Address him as sir' : 'woman. Address her as madam') + ', unless their words show otherwise.');
  if (ack) notes.push('You have just said "' + ack + '" aloud to show you heard them. Do not begin this reply with another reaction word or with sir or madam again; go straight to the answer.');
  // A check result is news once. After she has told the caller, it stays only as background, otherwise she keeps
  // repeating it or asking the same question again on every later reply.
  const freshResult = S.vehicle && !S.vehicleTold;
  if (S.vehicle && S.vehicleNear) notes.push(S.vehicle);
  else if (freshResult) notes.push('Result of the vehicle check you asked for, from our GPS servers just now: ' + S.vehicle + ' Do not write the VEHICLE tag for this same number again.');
  else if (S.vehicle) notes.push('Background only: earlier in this call you checked a vehicle and you have ALREADY told the caller the result. Do not say it again, do not ask about that vehicle number again, and carry on with what the caller says now. The result was: ' + S.vehicle);
  const messages = [{ role: 'system', content: promptFor(S, lang) }, { role: 'user', content: '(The call has just connected.)' },
    ...S.turns.map((t) => ({ role: t.who === 'agent' ? 'assistant' : 'user', content: t.text }))];
  if (afterCheck) messages.push({ role: 'user', content: '(The vehicle check has finished. Tell the caller the result now.)' });
  if (notes.length) {
    const last = messages[messages.length - 1], note = '(Notes for you from the system, not said by the caller. ' + notes.join(' ') + ')';
    if (last.role === 'user') last.content += '\n\n' + note; else messages.push({ role: 'user', content: note });
  }
  let raw = '', done = 0, queued = 0, first = true, tThink = 0, problem = null, onlyAck = false;
  const said = [];
  let chain = Promise.resolve();

  // Start turning a sentence into speech straight away, but deliver the sentences in order.
  const speak = (sentence) => {
    if (ack && !queued) {                    // she has just said "ok sir" aloud: do not open the answer with it again
      for (let i = 0; i < 2; i++) sentence = sentence.replace(SAME_START, '');
      sentence = sentence.trim();
      if (sentence.replace(/[\s,.!?।]/g, '').length < 2) { onlyAck = true; return; }
      sentence = sentence[0].toUpperCase() + sentence.slice(1);
    }
    queued++;
    if (!tThink) tThink = Date.now();
    const tS = Date.now();
    const audio = tts(S.cfg, sentence, ctrl.signal, lang).catch((e) => { problem = problem || e; return null; });
    chain = chain.then(async () => {
      const a = await audio;
      if (!a || g !== S.gen) return;
      said.push(sentence);
      send(S, { type: 'audio', audio: a, ms: first ? { hear: hearMs, think: tThink - t1, speak: Date.now() - tS } : undefined });
      first = false;
    });
  };
  const emit = (final) => {
    let rest = cleanReply(raw).slice(done);
    for (;;) {
      // The very first piece may also end at a comma, so her voice starts sooner; after that, whole sentences.
      const m = rest.match(!queued && !done ? /^([\s\S]{12,}?[.?!।,])\s+/ : /^([\s\S]{12,}?[.?!।])\s+/);
      if (!m) break;
      done += m[0].length; rest = rest.slice(m[0].length);
      const s = m[1].replace(/\s+/g, ' ').trim();
      if (s) speak(s);
    }
    if (final) {
      done += rest.length;
      const s = rest.replace(/\s+/g, ' ').trim();
      if (s) speak(s);
    }
  };

  try {
    for await (const piece of chatStream(S.cfg, messages, ctrl.signal)) { if (g !== S.gen) break; raw += piece; emit(false); }
    if (g === S.gen) emit(true);
  } catch (e) { problem = problem || e; }
  if (!queued && !onlyAck && g === S.gen) {  // nothing came from the quick model: ask the main one
    try { raw = await chatOnce(S.cfg, messages, ctrl.signal); done = 0; emit(true); problem = queued ? null : problem; }
    catch (e) { problem = problem || e; }
  }
  // The owner taught her something on this call and said yes: save it as a rule.
  const rule = (raw.replace(/<think>[\s\S]*?(<\/think>|$)/g, '').match(/\[\[RULE:\s*([\s\S]*?)\]\]/) || [])[1];
  if (rule && rule.trim() && S.cfg.trainer) {
    ivr('live_rule', { token: S.token, rule: rule.trim() }).then(() => log('call', S.cfg.call_id, 'learned a rule')).catch((e) => log('rule not saved:', e.message));
  }
  await chain;

  const text = said.join(' ');
  if (text) {
    S.turns.push({ who: 'agent', text });
    if (freshResult) S.vehicleTold = true;
    await syncCall(S, false);
    send(S, { type: 'reply_done', text, turn: S.turns.length - 1 });
  } else if (g === S.gen && !onlyAck) {
    send(S, { type: 'error', message: problem ? problem.message : 'Thinking failed: the model sent an empty reply.' });
  }

  // She asked for a vehicle to be checked: search the GPS servers, then let her tell the caller what was found.
  const number = vehicleTag(raw);
  if (number && S.kind !== 'all') S.kind = S.kind === 'new' ? 'all' : 'old';   // a vehicle check means an existing customer
  if (number && !afterCheck) {
    S.vehicleNear = false;
    if (!fullPlate(number)) {
      log('call', S.cfg.call_id, 'vehicle check skipped, not a full number:', number);
      S.vehicle = 'You tried to check "' + number + '", which is only part of a vehicle number, so nothing was checked. Ask the caller for the full vehicle number starting with the state letters, like A P three nine B P zero zero four two. Do not say you are checking until you have the full number.';
    } else if (S.checks >= 4) {
      S.vehicle = 'No more checks can be made on this call. Say you could not check right now and that the team will call them back.';
    } else {
      S.checks++;
      let found;
      try { found = await ivr('live_vehicle', { token: S.token, number, why: whyCalling(S) }, 22000); log('call', S.cfg.call_id, 'vehicle check', number, found.status); }
      catch (e) { log('vehicle check failed:', e.message); found = { text: 'The server could not be checked just now. Do not say the vehicle is missing. Say you could not check at the moment and continue without the expiry date.' }; }
      S.vehicle = found.text || '';
      S.vehicleNear = !!found.near;                         // she still has to ask whether the close number is theirs
    }
    S.vehicleTold = false;
    if (g === S.gen && !S.closed) await reply(S, 0, '', true);   // if the caller has started talking, the result is used in the next reply
  }
}

function newState(ws, token, cfg, rate) {
  return { ws, token, cfg, rate, lang: cfg.lang || 'te', sttLang: '', gen: 0, abort: null, turns: [{ who: 'agent', text: cfg.greeting }], carry: '', out: null,
    pcm: [], pcmBytes: 0, parts: [], flushed: false, afterFlush: false, capturing: false, sttLive: false, stt: null, closed: false, replying: Promise.resolve(),
    pitch: [], gender: '', ackN: 0, langWant: '', vehicle: '', checks: 0, kind: cfg.trainer ? 'all' : '', promptCache: {}, vehicleTold: false, vehicleNear: false };
}

function greet(S) {
  send(S, { type: 'ready', call_id: S.cfg.call_id, text: S.cfg.greeting });
  if (S.cfg.allowed !== false) warmAcks(S, S.lang);
  const ctrl = new AbortController(); S.abort = ctrl;
  const g = S.gen;
  // The greeting is the same on every call, so its sound is made once and kept, like the quick "ok" clips.
  const k = clipKey(S.cfg, S.lang, 'greeting:' + S.cfg.greeting), kept = clips.get(k);
  S.replying = (kept ? Promise.resolve(kept) : tts(S.cfg, S.cfg.greeting, ctrl.signal, S.lang).then((a) => { clips.set(k, a); return a; }))
    .then((a) => { if (g === S.gen) send(S, { type: 'audio', audio: a }); })
    .catch((e) => { if (g === S.gen) send(S, { type: 'error', message: e.message }); });
}

async function endState(S) {
  if (S.closed) return;
  S.closed = true; S.gen++;
  if (S.abort) S.abort.abort();
  if (S.stt) try { S.stt.close(); } catch (e) { /* already closed */ }
  await S.replying;
  let secs = 0;
  try { secs = await recSave(S); } catch (e) { log('recording not saved:', e.message); }
  await syncCall(S, true, secs);
  log('call', S.cfg.call_id, 'ended,', S.turns.length, 'lines' + (secs ? ', recorded ' + secs + ' s' : ''));
}

// ---------- a test call from the dashboard ----------
async function startCall(ws, token) {
  let cfg;
  try { cfg = await ivr('live_config', { token }); }
  catch (e) { ws.send(JSON.stringify({ type: 'error', message: 'Could not start the live call: ' + e.message })); ws.close(); return; }

  const S = newState(ws, token, cfg, 16000);
  log('call', cfg.call_id, 'started,', cfg.lang_code, cfg.voice);
  openStt(S);
  greet(S);

  const limit = setTimeout(() => ws.close(), MAX_CALL_MS);
  ws.on('message', (data, isBinary) => {
    if (isBinary) return audioIn(S, data);
    let m; try { m = JSON.parse(data.toString()); } catch (e) { return; }
    if (m.type === 'speech_start') speechStart(S);
    else if (m.type === 'speech_end') speechEnd(S).catch((e) => log('turn crashed:', e.message));
    else if (m.type === 'speech_cancel') { S.capturing = false; S.pcm = []; S.pcmBytes = 0; }
    else if (m.type === 'end') ws.close();
  });
  ws.on('close', () => { clearTimeout(limit); endState(S); });
}

// ---------- a real phone call (Exotel Voicebot applet) ----------
// Exotel opens a connection per call and sends the caller's voice as small pieces of plain audio.
// There is no browser here to notice when the caller starts and stops talking, so this server does it.
function wavPcm(b64) {
  const b = Buffer.from(b64, 'base64');
  const i = b.indexOf('data');
  return b.toString('ascii', 0, 4) === 'RIFF' && i >= 0 ? b.subarray(i + 8) : b;
}

function phoneOut(S, msg) {
  if (msg.type === 'audio') {
    let pcm = wavPcm(msg.audio);
    recAgent(S, pcm);
    const pad = (320 - pcm.length % 320) % 320;          // Exotel wants pieces in multiples of 320 bytes, at least 3200
    if (pad) pcm = Buffer.concat([pcm, Buffer.alloc(pad)]);
    if (pcm.length < 3200) pcm = Buffer.concat([pcm, Buffer.alloc(3200 - pcm.length)]);
    for (let i = 0; i < pcm.length;) {
      const end = pcm.length - (i + 3200) < 3200 ? pcm.length : i + 3200;
      if (S.ws.readyState === WebSocket.OPEN) S.ws.send(JSON.stringify({ event: 'media', stream_sid: S.sid, media: { payload: pcm.subarray(i, end).toString('base64') } }));
      i = end;
    }
    S.playUntil = Math.max(Date.now(), S.playUntil) + pcm.length / (S.rate * 2) * 1000;
  } else if (msg.type === 'error') {
    log('call', S.cfg.call_id, 'problem:', msg.message);
    if (S.cfg.retry_text && !S.retrying) {               // do not leave the caller in silence
      S.retrying = true;
      tts(S.cfg, (S.cfg.retry && S.cfg.retry[S.lang]) || S.cfg.retry_text, AbortSignal.timeout(15000), S.lang).then((a) => phoneOut(S, { type: 'audio', audio: a })).catch(() => {}).finally(() => { S.retrying = false; });
    }
  }
}

function phoneAudio(S, pcm) {
  const n = pcm.length >> 1, V = S.vad;
  if (!n) return;
  let sum = 0;
  for (let i = 0; i < n; i++) { const v = pcm.readInt16LE(i * 2) / 32768; sum += v * v; }
  const rms = Math.sqrt(sum / n), ms = n / S.rate * 1000;
  const talking = Date.now() < S.playUntil;              // while she speaks, only a clearly louder voice interrupts her
  const limit = talking ? Math.max(0.06, V.noise * 8) : Math.max(0.02, V.noise * 3.5);
  if (!V.on) {
    V.pre.push(pcm); V.preMs += ms;
    while (V.preMs > 400 && V.pre.length > 1) V.preMs -= (V.pre.shift().length >> 1) / S.rate * 1000;
    if (rms > limit) V.loudMs += ms; else { V.loudMs = 0; if (!talking) V.noise = V.noise * 0.95 + rms * 0.05; }
    if (V.loudMs >= (talking ? 240 : 60)) {
      V.on = true; V.loudMs = 0; V.speech = ms; V.silent = 0; V.total = 0;
      S.clear();                                         // stop her mid-sentence
      recCut(S);
      S.playUntil = 0;
      speechStart(S);
      for (const p of V.pre) audioIn(S, p);
      V.pre = []; V.preMs = 0;
    }
    return;
  }
  audioIn(S, pcm); V.total += ms;
  if (rms > limit) { V.speech += ms; V.silent = 0; } else V.silent += ms;
  if (V.silent >= 350 || V.total >= 25000) {
    V.on = false;
    if (V.speech >= 250) speechEnd(S).catch((e) => log('turn crashed:', e.message));
    else { S.capturing = false; S.pcm = []; S.pcmBytes = 0; }
  }
}

function phoneCall(ws, key) {
  let S = null, starting = false;
  const limit = setTimeout(() => ws.close(), MAX_CALL_MS);
  ws.on('message', async (data) => {
    let m; try { m = JSON.parse(data.toString()); } catch (e) { return; }
    if (m.event === 'start' && !S && !starting) {
      starting = true;
      const st = m.start || {};
      const rate = +(st.media_format && st.media_format.sample_rate) === 16000 ? 16000 : 8000;
      let cfg;
      try { cfg = await ivr('phone_config', { key, from: String(st.from || ''), to: String(st.to || ''), call_sid: String(st.call_sid || '') }); }
      catch (e) { log('phone call refused:', e.message); ws.close(); return; }
      cfg.pcm_rate = rate;
      S = newState(ws, cfg.token, cfg, rate);
      S.sid = m.stream_sid || st.stream_sid; S.playUntil = 0; S.retrying = false;
      S.vad = { on: false, noise: 0.005, loudMs: 0, speech: 0, silent: 0, total: 0, pre: [], preMs: 0 };
      S.out = (msg) => phoneOut(S, msg);
      S.clear = () => { if (ws.readyState === WebSocket.OPEN) ws.send(JSON.stringify({ event: 'clear', stream_sid: S.sid })); };
      log('call', cfg.call_id, 'phone call started,', cfg.allowed ? 'answering' : 'not a test number', cfg.lang_code, cfg.voice, rate + ' Hz');
      if (ws.readyState !== WebSocket.OPEN) { endState(S); return; }
      if (cfg.allowed) { openStt(S); recStart(S); }
      greet(S);
      // Not a number she answers yet: say the call-back message, then hang up.
      if (!cfg.allowed) S.replying.then(() => setTimeout(() => ws.close(), Math.max(0, S.playUntil - Date.now()) + 800));
    } else if (m.event === 'media' && S && S.cfg.allowed && !S.closed) {
      const pcm = Buffer.from((m.media && m.media.payload) || '', 'base64');
      recCaller(S, pcm);
      phoneAudio(S, pcm);
    } else if (m.event === 'stop') ws.close();
  });
  ws.on('close', () => { clearTimeout(limit); if (S) endState(S); else log('phone stream closed before the call was set up'); });
}

// ---------- a real phone call (Vobiz) ----------
// Vobiz first asks an "Answer URL" what to do with a call. We answer: stream it to this server.
// The caller's voice then arrives in the telephone format (mu-law) and her voice goes back as plain audio.
const vobizPending = new Map();              // call id -> { from, to } between the Answer URL and the stream

const MULAW = (() => {                        // mu-law byte -> 16-bit sample
  const t = new Int16Array(256);
  for (let i = 0; i < 256; i++) {
    const u = ~i & 0xff, v = (((u & 0x0f) << 3) + 0x84) << ((u & 0x70) >> 4);
    t[i] = (u & 0x80) ? 0x84 - v : v - 0x84;
  }
  return t;
})();
function mulawToPcm(buf) {
  const out = Buffer.alloc(buf.length * 2);
  for (let i = 0; i < buf.length; i++) out.writeInt16LE(MULAW[buf[i]], i * 2);
  return out;
}

const xmlSafe = (v) => String(v).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');

function vobizAnswer(req, res, url) {
  let body = '';
  req.on('data', (c) => { if (body.length < 10000) body += c; });
  req.on('end', () => {
    const key = url.searchParams.get('key') || '';
    if (!/^[a-f0-9]{32}$/.test(key)) { log('Vobiz Answer URL refused: the key in the address is missing or malformed'); res.writeHead(404); res.end(); return; }
    let form = new URLSearchParams(body);
    try { if (body.trim().startsWith('{')) form = new URLSearchParams(Object.entries(JSON.parse(body)).map(([k, v]) => [k, String(v)])); } catch (e) { /* not JSON */ }
    const pick = (...names) => {             // field names differ in letter case between providers
      for (const src of [form, url.searchParams]) for (const [k, v] of src) if (names.includes(k.toLowerCase())) return v;
      return '';
    };
    const from = pick('from'), to = pick('to'), call = pick('calluuid', 'call_uuid', 'callid');
    if (call) {
      vobizPending.set(call, { from, to });
      setTimeout(() => vobizPending.delete(call), 60000);
    }
    log('Vobiz call from', from || '(no number)', '- telling Vobiz to stream it here');
    const ws = 'wss://' + (req.headers.host || 'call.someswara.com') + '/vobiz?' + new URLSearchParams({ key, from, to, call });
    res.writeHead(200, { 'Content-Type': 'application/xml' });
    res.end('<?xml version="1.0" encoding="UTF-8"?><Response><Stream bidirectional="true" audioTrack="inbound" keepCallAlive="true" contentType="audio/x-mulaw;rate=8000">'
      + xmlSafe(ws) + '</Stream><Hangup/></Response>');
  });
}

function vobizOut(S, msg) {
  if (msg.type === 'audio') {
    const pcm = wavPcm(msg.audio), step = S.rate * 2 * 20 / 1000;   // 20 ms pieces
    recAgent(S, pcm);
    for (let i = 0; i < pcm.length; i += step) {
      if (S.ws.readyState !== WebSocket.OPEN) return;
      S.ws.send(JSON.stringify({ event: 'playAudio', streamId: S.sid, media: { contentType: 'audio/x-l16', sampleRate: S.rate, payload: pcm.subarray(i, i + step).toString('base64') } }));
    }
    S.ws.send(JSON.stringify({ event: 'checkpoint', streamId: S.sid, name: 'tts' }));
    S.playUntil = Math.max(Date.now(), S.playUntil) + pcm.length / (S.rate * 2) * 1000;
  } else if (msg.type === 'error') {
    log('call', S.cfg.call_id, 'problem:', msg.message);
    if (S.cfg.retry_text && !S.retrying) {
      S.retrying = true;
      tts(S.cfg, (S.cfg.retry && S.cfg.retry[S.lang]) || S.cfg.retry_text, AbortSignal.timeout(15000), S.lang).then((a) => vobizOut(S, { type: 'audio', audio: a })).catch(() => {}).finally(() => { S.retrying = false; });
    }
  }
}

function vobizCall(ws, key, q) {
  let S = null, starting = false, mulaw = true;
  const limit = setTimeout(() => ws.close(), MAX_CALL_MS);
  ws.on('message', async (data) => {
    let m; try { m = JSON.parse(data.toString()); } catch (e) { return; }
    if (m.event === 'start' && !S && !starting) {
      starting = true;
      const st = m.start || {}, fmt = st.mediaFormat || {};
      mulaw = !/l16/i.test(String(fmt.encoding || 'audio/x-mulaw'));
      const rate = +fmt.sampleRate === 16000 ? 16000 : 8000;
      const known = vobizPending.get(st.callId || q.call) || {};
      let cfg;
      try { cfg = await ivr('phone_config', { key, from: q.from || known.from || '', to: q.to || known.to || '', call_sid: String(st.callId || q.call || '') }); }
      catch (e) { log('phone call refused:', e.message); ws.close(); return; }
      cfg.pcm_rate = rate;
      S = newState(ws, cfg.token, cfg, rate);
      S.sid = m.streamId || st.streamId; S.playUntil = 0; S.retrying = false;
      S.vad = { on: false, noise: 0.005, loudMs: 0, speech: 0, silent: 0, total: 0, pre: [], preMs: 0 };
      S.out = (msg) => vobizOut(S, msg);
      S.clear = () => { if (ws.readyState === WebSocket.OPEN) ws.send(JSON.stringify({ event: 'clearAudio', streamId: S.sid })); };
      log('call', cfg.call_id, 'Vobiz call started,', cfg.allowed ? 'answering' : 'not a test number', cfg.lang_code, cfg.voice, rate + ' Hz', mulaw ? 'mu-law' : 'L16');
      if (ws.readyState !== WebSocket.OPEN) { endState(S); return; }
      if (cfg.allowed) { openStt(S); recStart(S); }
      greet(S);
      if (!cfg.allowed) S.replying.then(() => setTimeout(() => ws.close(), Math.max(0, S.playUntil - Date.now()) + 800));
    } else if (m.event === 'media' && S && S.cfg.allowed && !S.closed) {
      const raw = Buffer.from((m.media && m.media.payload) || '', 'base64'), pcm = mulaw ? mulawToPcm(raw) : raw;
      recCaller(S, pcm);
      phoneAudio(S, pcm);
    } else if (m.event === 'stop') ws.close();
  });
  ws.on('close', () => { clearTimeout(limit); if (S) endState(S); else log('phone stream closed before the call was set up'); });
}

// ---------- server ----------
const server = http.createServer((req, res) => {
  const url = new URL(req.url, 'http://x');
  if (url.pathname === '/health') { res.writeHead(200, { 'Content-Type': 'text/plain' }); res.end('ok v' + VERSION); return; }
  if (url.pathname === '/vobiz/answer') return vobizAnswer(req, res, url);
  if (url.pathname === '/vobiz/hangup') { req.resume(); res.writeHead(204); res.end(); return; }
  if (url.pathname.startsWith('/rec/')) return void recServe(req, res, url).catch(() => { try { res.writeHead(500); res.end(); } catch (e) { /* already sent */ } });
  res.writeHead(404); res.end();
});
const wss = new WebSocketServer({ noServer: true, maxPayload: 256 * 1024 });
// A dropped line can raise an error on its connection; without a listener that would stop the whole server.
function guard(ws) { ws.on('error', (e) => log('call connection error:', e.message)); return ws; }

server.on('upgrade', (req, socket, head) => {
  const url = new URL(req.url, 'http://x');
  const token = url.searchParams.get('token') || '';
  const refuse = (code) => { socket.write('HTTP/1.1 ' + code + '\r\n\r\n'); socket.destroy(); };
  if (url.pathname === '/phone') {            // Exotel: no browser origin, the secret key in the address is the pass
    const key = url.searchParams.get('key') || '';
    if (!/^[a-f0-9]{32}$/.test(key)) return refuse('400 Bad Request');
    if (wss.clients.size >= MAX_CALLS) return refuse('503 Busy');
    return wss.handleUpgrade(req, socket, head, (ws) => phoneCall(guard(ws), key));
  }
  if (url.pathname === '/vobiz') {
    const key = url.searchParams.get('key') || '';
    if (!/^[a-f0-9]{32}$/.test(key)) return refuse('400 Bad Request');
    if (wss.clients.size >= MAX_CALLS) return refuse('503 Busy');
    const q = { from: url.searchParams.get('from') || '', to: url.searchParams.get('to') || '', call: url.searchParams.get('call') || '' };
    return wss.handleUpgrade(req, socket, head, (ws) => vobizCall(guard(ws), key, q));
  }
  if (url.pathname !== '/live' || !/^[a-f0-9]{48}$/.test(token)) return refuse('400 Bad Request');
  if (!ORIGINS.includes(req.headers.origin || '')) return refuse('403 Forbidden');
  if (wss.clients.size >= MAX_CALLS) return refuse('503 Busy');
  wss.handleUpgrade(req, socket, head, (ws) => startCall(guard(ws), token).catch((e) => { log('call failed:', e.message); ws.close(); }));
});

// One bad call must never take the server, and every other call, down with it.
process.on('uncaughtException', (e) => log('unexpected error (server kept running):', e && e.stack ? e.stack.split('\n').slice(0, 3).join(' | ') : e));
process.on('unhandledRejection', (e) => log('unexpected failure (server kept running):', e && e.message ? e.message : e));

server.listen(PORT, '127.0.0.1', () => log('live call server listening on', PORT));
