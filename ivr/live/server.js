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
const { WebSocketServer, WebSocket } = require('ws');

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
async function ivr(action, fields) {
  const r = await fetch(IVR_API + '?action=' + action, { method: 'POST', body: new URLSearchParams(fields), signal: AbortSignal.timeout(8000) });
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

async function tts(cfg, text, signal) {
  const body = {
    text: speechText(text).slice(0, 2400), language_code: cfg.lang_code,
    model: cfg.voice.includes('_') ? 'bulbul:v4-flash' : 'bulbul:v3',
    speaker: cfg.voice, speech_sample_rate: cfg.pcm_rate || 24000, pace: 1.15,
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
  return String((await r.json()).transcript || '').trim();
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

const cleanReply = (raw) => raw.replace(/<think>[\s\S]*?(<\/think>|$)/g, '').replace(/[*#`_]/g, '');

// ---------- one call ----------
function send(S, msg) {
  if (S.out) return S.out(msg);              // a phone call has its own way of sending (see phoneOut)
  if (S.ws.readyState === WebSocket.OPEN) S.ws.send(JSON.stringify(msg));
}

async function syncCall(S, ended) {
  try { await ivr('live_sync', { token: S.token, turns: JSON.stringify(S.turns), ended: ended ? '1' : '0' }); }
  catch (e) { log('sync failed:', e.message); }
}

// Streaming speech-to-text: audio goes up while the caller talks, so the words are ready when they stop.
function openStt(S) {
  if (S.closed || (S.stt && S.stt.readyState <= WebSocket.OPEN)) return;
  const q = new URLSearchParams({ 'language-code': S.cfg.lang_code, model: 'saaras:v3', mode: 'codemix', sample_rate: String(S.rate), input_audio_codec: 'pcm_s16le', flush_signal: 'true' });
  const stt = new WebSocket(S_WS + '/speech-to-text/ws?' + q, { headers: { 'api-subscription-key': S.cfg.key } });
  S.stt = stt;
  stt.on('message', (raw) => {
    let m; try { m = JSON.parse(raw.toString()); } catch (e) { return; }
    if (m.type === 'data') {
      const t = String(m.data?.transcript || '').trim();
      if (t) S.parts.push(t);
      if (S.flushed) S.afterFlush = true;
    } else if (m.type === 'error') log('stt error:', JSON.stringify(m.data));
  });
  stt.on('error', (e) => log('stt socket:', e.message));
}

function speechStart(S) {
  S.gen++;                                   // anything she was about to say is cancelled
  if (S.abort) S.abort.abort();
  S.pcm = []; S.pcmBytes = 0; S.parts = []; S.flushed = false; S.afterFlush = false;
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
    try { text = await sttRest(S.cfg, pcm, S.rate); }
    catch (e) { if (g === S.gen) send(S, { type: 'error', message: e.message }); return; }
  }
  if (g !== S.gen) { if (text) S.carry = (S.carry + ' ' + text).trim(); return; }   // they started talking again: keep the words for the next turn
  text = (S.carry + ' ' + text).trim(); S.carry = '';
  if (!text) { send(S, { type: 'nothing' }); return; }
  const hearMs = Date.now() - t0;
  await S.replying;                          // let a cancelled reply finish recording what it said
  if (g !== S.gen) { S.carry = text; return; }
  S.turns.push({ who: 'caller', text });
  send(S, { type: 'heard', text });
  S.replying = reply(S, hearMs).catch((e) => log('reply crashed:', e.message));
}

async function reply(S, hearMs) {
  const g = S.gen, t1 = Date.now();
  const ctrl = new AbortController(); S.abort = ctrl;
  const messages = [{ role: 'system', content: S.cfg.prompt }, { role: 'user', content: '(The call has just connected.)' },
    ...S.turns.map((t) => ({ role: t.who === 'agent' ? 'assistant' : 'user', content: t.text }))];
  let raw = '', done = 0, queued = 0, first = true, tThink = 0, problem = null;
  const said = [];
  let chain = Promise.resolve();

  // Start turning a sentence into speech straight away, but deliver the sentences in order.
  const speak = (sentence) => {
    queued++;
    if (!tThink) tThink = Date.now();
    const tS = Date.now();
    const audio = tts(S.cfg, sentence, ctrl.signal).catch((e) => { problem = problem || e; return null; });
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
      const m = rest.match(/^([\s\S]{12,}?[.?!।])\s+/);
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
  if (!queued && g === S.gen) {              // nothing came from the quick model: ask the main one
    try { raw = await chatOnce(S.cfg, messages, ctrl.signal); done = 0; emit(true); problem = queued ? null : problem; }
    catch (e) { problem = problem || e; }
  }
  await chain;

  const text = said.join(' ');
  if (text) {
    S.turns.push({ who: 'agent', text });
    await syncCall(S, false);
    send(S, { type: 'reply_done', text, turn: S.turns.length - 1 });
  } else if (g === S.gen) {
    send(S, { type: 'error', message: problem ? problem.message : 'Thinking failed: the model sent an empty reply.' });
  }
}

function newState(ws, token, cfg, rate) {
  return { ws, token, cfg, rate, gen: 0, abort: null, turns: [{ who: 'agent', text: cfg.greeting }], carry: '', out: null,
    pcm: [], pcmBytes: 0, parts: [], flushed: false, afterFlush: false, capturing: false, sttLive: false, stt: null, closed: false, replying: Promise.resolve() };
}

function greet(S) {
  send(S, { type: 'ready', call_id: S.cfg.call_id, text: S.cfg.greeting });
  const ctrl = new AbortController(); S.abort = ctrl;
  const g = S.gen;
  S.replying = tts(S.cfg, S.cfg.greeting, ctrl.signal)
    .then((a) => { if (g === S.gen) send(S, { type: 'audio', audio: a }); })
    .catch((e) => { if (g === S.gen) send(S, { type: 'error', message: e.message }); });
}

async function endState(S) {
  if (S.closed) return;
  S.closed = true; S.gen++;
  if (S.abort) S.abort.abort();
  if (S.stt) try { S.stt.close(); } catch (e) { /* already closed */ }
  await S.replying;
  await syncCall(S, true);
  log('call', S.cfg.call_id, 'ended,', S.turns.length, 'lines');
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
      tts(S.cfg, S.cfg.retry_text, AbortSignal.timeout(15000)).then((a) => phoneOut(S, { type: 'audio', audio: a })).catch(() => {}).finally(() => { S.retrying = false; });
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
      if (S.ws.readyState === WebSocket.OPEN) S.ws.send(JSON.stringify({ event: 'clear', stream_sid: S.sid }));   // stop her mid-sentence
      S.playUntil = 0;
      speechStart(S);
      for (const p of V.pre) audioIn(S, p);
      V.pre = []; V.preMs = 0;
    }
    return;
  }
  audioIn(S, pcm); V.total += ms;
  if (rms > limit) { V.speech += ms; V.silent = 0; } else V.silent += ms;
  if (V.silent >= 600 || V.total >= 25000) {
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
      log('call', cfg.call_id, 'phone call started,', cfg.allowed ? 'answering' : 'not a test number', cfg.lang_code, cfg.voice, rate + ' Hz');
      if (ws.readyState !== WebSocket.OPEN) { endState(S); return; }
      if (cfg.allowed) openStt(S);
      greet(S);
      // Not a number she answers yet: say the call-back message, then hang up.
      if (!cfg.allowed) S.replying.then(() => setTimeout(() => ws.close(), Math.max(0, S.playUntil - Date.now()) + 800));
    } else if (m.event === 'media' && S && S.cfg.allowed && !S.closed) {
      phoneAudio(S, Buffer.from((m.media && m.media.payload) || '', 'base64'));
    } else if (m.event === 'stop') ws.close();
  });
  ws.on('close', () => { clearTimeout(limit); if (S) endState(S); });
}

// ---------- server ----------
const server = http.createServer((req, res) => {
  if (req.url === '/health') { res.writeHead(200, { 'Content-Type': 'text/plain' }); res.end('ok'); return; }
  res.writeHead(404); res.end();
});
const wss = new WebSocketServer({ noServer: true, maxPayload: 256 * 1024 });

server.on('upgrade', (req, socket, head) => {
  const url = new URL(req.url, 'http://x');
  const token = url.searchParams.get('token') || '';
  const refuse = (code) => { socket.write('HTTP/1.1 ' + code + '\r\n\r\n'); socket.destroy(); };
  if (url.pathname === '/phone') {            // Exotel: no browser origin, the secret key in the address is the pass
    const key = url.searchParams.get('key') || '';
    if (!/^[a-f0-9]{32}$/.test(key)) return refuse('400 Bad Request');
    if (wss.clients.size >= MAX_CALLS) return refuse('503 Busy');
    return wss.handleUpgrade(req, socket, head, (ws) => phoneCall(ws, key));
  }
  if (url.pathname !== '/live' || !/^[a-f0-9]{48}$/.test(token)) return refuse('400 Bad Request');
  if (!ORIGINS.includes(req.headers.origin || '')) return refuse('403 Forbidden');
  if (wss.clients.size >= MAX_CALLS) return refuse('503 Busy');
  wss.handleUpgrade(req, socket, head, (ws) => startCall(ws, token).catch((e) => { log('call failed:', e.message); ws.close(); }));
});

server.listen(PORT, '127.0.0.1', () => log('live call server listening on', PORT));
