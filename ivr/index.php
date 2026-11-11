<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>AI Call Agent | Bharat GPS Tracker</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Noto+Sans+Telugu:wght@400;500&display=swap">
<style>
:root{--accent:#C8102E;--accent-dark:#8E0B20;--accent-soft:#F3E3E5;--bg:#F6F6F3;--card:#FFFFFF;--line:#E4E4DF;--line2:#C9C9C3;--text:#1A1A1A;--muted:#5A5F66;--soft:#EEEEEA}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--text);font:15px/1.45 Inter,"Noto Sans Telugu",system-ui,sans-serif}
a{color:var(--text)}
h1{margin:0;font-size:26px;font-weight:700}
h2{margin:0;font-size:18px;font-weight:700}
button,input,select{font:inherit;color:var(--text)}
button{cursor:pointer}
button[disabled]{opacity:.55;cursor:default}
.hide{display:none!important}
.btn{min-height:44px;padding:0 18px;border-radius:8px;border:1px solid var(--line2);background:#fff;font-weight:600}
.btn-primary{min-height:52px;width:100%;border-radius:10px;border:none;background:var(--accent);color:#fff;font-weight:700;font-size:16px;display:flex;align-items:center;justify-content:center;gap:10px}
.btn-outline{border-color:var(--accent);color:var(--accent-dark);font-weight:700}
.field{display:flex;flex-direction:column;gap:6px}
.field label,.lbl{font-size:13px;font-weight:600;color:var(--muted)}
.field input,.field select{min-height:44px;padding:0 12px;border-radius:8px;border:1px solid var(--line2);background:#fff;width:100%}
.hint{font-size:13px;color:var(--muted)}
.err{font-size:14px;color:#A01225;font-weight:600;min-height:20px}
.card{background:var(--card);border:1px solid var(--line);border-radius:12px;padding:22px}

.auth{max-width:420px;margin:8vh auto;padding:0 16px}
.auth .card{display:flex;flex-direction:column;gap:14px}

.shell{display:flex;flex-wrap:wrap;min-height:100vh}
.side{flex:1 1 220px;background:#fff;border-right:1px solid var(--line);padding:24px 16px;display:flex;flex-direction:column;gap:4px}
.brand{display:flex;align-items:center;gap:10px;padding:0 8px 20px}
.brand .logo{width:34px;height:34px;border-radius:8px;background:var(--accent);display:flex;align-items:center;justify-content:center;flex:none}
.brand b{display:block;font-size:15px}
.brand span{font-size:12px;color:var(--muted)}
.nav{display:flex;flex-direction:column;gap:4px}
.nav button{display:block;width:100%;text-align:left;padding:11px 12px;border-radius:8px;border:none;background:transparent;font-weight:500;white-space:nowrap}
.nav button.on{background:var(--accent-soft);color:var(--accent-dark);font-weight:600}
.main{flex:999 1 560px;min-width:0;padding:28px 32px 40px;display:flex;flex-direction:column;gap:24px}
.head{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:16px}
.head .sub{color:var(--muted);font-size:14px;margin-top:4px}
.head .right{display:flex;flex-wrap:wrap;align-items:center;gap:12px}
.mode{display:inline-flex;align-items:center;gap:8px;padding:8px 14px;border-radius:999px;background:#FFF1D6;color:#6B4700;font-weight:600;font-size:13px}
.mode i{width:8px;height:8px;border-radius:50%;background:#B97A00}
.row{display:flex;flex-wrap:wrap;gap:20px}
.row>.card{flex:1 1 320px;min-width:0;display:flex;flex-direction:column;gap:16px}
.two{display:flex;flex-wrap:wrap;gap:12px}
.two>.field{flex:1 1 140px}
.rule{height:1px;background:var(--line)}
.inline{display:flex;flex-wrap:wrap;gap:10px}
.inline input{flex:1 1 160px;min-width:0;min-height:44px;padding:0 12px;border-radius:8px;border:1px solid var(--line2)}
.thead{display:flex;align-items:center;justify-content:space-between;gap:12px}
.lines{display:flex;flex-direction:column;gap:10px;min-height:150px;max-height:380px;overflow-y:auto}
.line{max-width:88%;border-radius:12px;padding:10px 14px}
.line small{display:block;font-size:12px;font-weight:700}
.line.agent{align-self:flex-start;background:var(--accent-soft)}
.line.agent small{color:var(--accent-dark)}
.line.caller{align-self:flex-end;background:var(--soft)}
.line.caller small{color:var(--muted)}
.line.flag{outline:2px solid var(--accent)}
.empty{color:var(--muted);font-size:14px}
.status{font-size:14px;font-weight:600;color:var(--accent-dark);min-height:20px}
.tiles{display:flex;flex-wrap:wrap;gap:16px}
.tile{flex:1 1 160px;background:#fff;border:1px solid var(--line);border-radius:12px;padding:18px}
.tile b{display:block;font-size:30px;font-weight:700;margin-top:6px}
.scroll{overflow-x:auto}
table{width:100%;min-width:680px;border-collapse:collapse;font-size:14px}
th{text-align:left;color:var(--muted);font-weight:600;padding:10px 12px;border-bottom:1px solid var(--line)}
td{padding:12px;border-bottom:1px solid var(--soft);vertical-align:top}
tr:last-child td{border-bottom:none}
.pill{display:inline-block;padding:4px 10px;border-radius:999px;font-weight:600;font-size:13px;background:var(--soft);color:#3A3F45;white-space:nowrap}
.pill.bad{background:var(--accent-soft);color:var(--accent-dark)}
.link{border:none;background:none;padding:8px 0;font-weight:600;text-decoration:underline;min-height:44px}
.toast{position:fixed;left:50%;bottom:24px;transform:translateX(-50%);background:#1A1A1A;color:#fff;padding:12px 18px;border-radius:10px;font-size:14px;max-width:90vw;z-index:9}
.form{display:flex;flex-direction:column;gap:14px;max-width:460px}
@media (max-width:760px){
  .side{border-right:none;border-bottom:1px solid var(--line);padding:16px 12px 8px}
  .brand{padding-bottom:12px}
  .nav{flex-direction:row;overflow-x:auto;padding-bottom:8px}
  .nav button{width:auto}
  .main{padding:20px 16px 32px}
}
</style>
</head>
<body>

<!-- Set up / log in -->
<div id="v-auth" class="auth hide">
  <div class="card">
    <div class="brand" style="padding:0">
      <div class="logo" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/></svg></div>
      <div><b>Bharat GPS Tracker</b><span>AI Call Agent</span></div>
    </div>
    <h2 id="auth-title">Admin login</h2>
    <p id="auth-hint" class="hint hide" style="margin:0">First visit. Choose the admin password for this dashboard.</p>
    <form id="auth-form" class="form">
      <div class="field"><label for="a-pw">Password</label><input id="a-pw" type="password" autocomplete="current-password" required></div>
      <div id="a-key-wrap" class="field hide"><label for="a-key">Sarvam API key (you can add it later in Settings)</label><input id="a-key" autocomplete="off"></div>
      <div id="auth-err" class="err" role="alert"></div>
      <button id="auth-btn" class="btn-primary" type="submit">Log in</button>
    </form>
  </div>
</div>

<!-- Dashboard -->
<div id="v-app" class="shell hide">
  <div class="side">
    <div class="brand">
      <div class="logo" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/></svg></div>
      <div><b>Bharat GPS Tracker</b><span>AI Call Agent</span></div>
    </div>
    <nav class="nav" aria-label="Dashboard sections">
      <button type="button" data-view="overview" class="on">Overview</button>
      <button type="button" data-view="overview" data-scroll="test">Test calls</button>
      <button type="button" data-view="history">Call history</button>
      <button type="button" data-view="soon" data-step="4" data-name="Leads">Leads</button>
      <button type="button" data-view="soon" data-step="2" data-name="Sales script">Sales script</button>
      <button type="button" data-view="soon" data-step="2" data-name="Products and prices">Products and prices</button>
      <button type="button" data-view="settings">Voice and language</button>
      <button type="button" data-view="soon" data-step="4" data-name="Staff transfer">Staff transfer</button>
      <button type="button" data-view="settings">Settings</button>
    </nav>
  </div>

  <main class="main">

    <!-- Overview -->
    <div id="view-overview" style="display:flex;flex-direction:column;gap:24px">
      <div class="head">
        <div><h1>Overview</h1><div class="sub">Test calls made from this dashboard</div></div>
        <div class="right">
          <span class="mode"><i aria-hidden="true"></i>Test mode: not answering customers</span>
          <button type="button" class="btn" id="go-live">Go live</button>
        </div>
      </div>

      <div class="row" id="test">
        <div class="card">
          <h2>Start a test call</h2>
          <div class="two">
            <div class="field"><label for="lang">Language</label>
              <select id="lang"><option>Telugu</option><option disabled>Hindi (later)</option><option disabled>English (later)</option></select></div>
            <div class="field"><label for="voice">Voice</label><select id="voice"></select></div>
          </div>
          <button type="button" class="btn-primary" id="talk">
            <svg aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="3" width="6" height="11" rx="3"/><path d="M5 11a7 7 0 0 0 14 0"/><path d="M12 18v3"/></svg>
            <span id="talk-label">Talk to the agent here</span></button>
          <div class="hint">Uses this device's microphone. No phone number needed.</div>
          <div id="call-err" class="err" role="alert"></div>
          <div class="rule"></div>
          <div class="field">
            <label for="myphone">Or ring a phone</label>
            <div class="inline">
              <input id="myphone" type="tel" placeholder="Your mobile number" disabled>
              <button type="button" class="btn btn-outline" id="call-me">Call me now</button>
            </div>
            <div class="hint">Real phone line, so you hear exactly what a customer hears. Opens in step 3.</div>
          </div>
        </div>

        <div class="card">
          <div class="thead"><h2>Live transcript</h2><span class="hint" id="speed"></span></div>
          <div class="status" id="status" role="status"></div>
          <div class="lines" id="lines"><div class="empty">Start a test call and the conversation appears here.</div></div>
          <div class="inline" style="margin-top:auto">
            <button type="button" class="btn" id="end" disabled>End call</button>
            <button type="button" class="btn" id="flag" disabled>Mark a wrong answer</button>
          </div>
        </div>
      </div>

      <div class="tiles">
        <div class="tile"><span class="lbl">Calls answered today</span><b id="s-calls">0</b></div>
        <div class="tile"><span class="lbl">Leads captured</span><b id="s-leads">0</b></div>
        <div class="tile"><span class="lbl">Sent to staff</span><b id="s-staff">0</b></div>
        <div class="tile"><span class="lbl">Average call length</span><b id="s-avg">0:00</b></div>
      </div>

      <div class="card" style="display:flex;flex-direction:column;gap:14px">
        <div class="thead"><h2>Recent calls</h2><button type="button" class="link" data-goto="history">See all calls</button></div>
        <div class="scroll"><table>
          <thead><tr><th scope="col">Time</th><th scope="col">Caller</th><th scope="col">Asked about</th><th scope="col">Result</th><th scope="col">Length</th><th scope="col">Review</th></tr></thead>
          <tbody id="recent"></tbody>
        </table></div>
      </div>
    </div>

    <!-- Call history -->
    <div id="view-history" class="hide" style="display:flex;flex-direction:column;gap:24px">
      <div class="head"><div><h1>Call history</h1><div class="sub">The last 50 calls</div></div></div>
      <div class="card hide" id="detail" style="display:flex;flex-direction:column;gap:14px">
        <div class="thead"><h2 id="detail-title">Call</h2><button type="button" class="btn" id="detail-close">Close</button></div>
        <div class="lines" id="detail-lines" style="max-height:none"></div>
      </div>
      <div class="card"><div class="scroll"><table>
        <thead><tr><th scope="col">Time</th><th scope="col">Caller</th><th scope="col">Asked about</th><th scope="col">Result</th><th scope="col">Length</th><th scope="col">Review</th></tr></thead>
        <tbody id="all"></tbody>
      </table></div></div>
    </div>

    <!-- Settings -->
    <div id="view-settings" class="hide" style="display:flex;flex-direction:column;gap:24px">
      <div class="head"><div><h1>Settings</h1><div class="sub">Voice, language and keys</div></div>
        <div class="right"><button type="button" class="btn" id="logout">Log out</button></div></div>
      <div class="card">
        <form id="set-form" class="form">
          <div class="field"><label for="set-lang">Language</label>
            <select id="set-lang"><option>Telugu</option><option disabled>Hindi (later)</option><option disabled>English (later)</option></select></div>
          <div class="field"><label for="set-voice">Voice</label><select id="set-voice"></select></div>
          <div class="field"><label for="set-key">Sarvam API key</label>
            <input id="set-key" autocomplete="off" placeholder="Paste your Sarvam API key">
            <span class="hint" id="key-state"></span></div>
          <div class="field"><label for="set-pw">New admin password</label>
            <input id="set-pw" type="password" autocomplete="new-password" placeholder="Leave empty to keep the current one"></div>
          <div id="set-err" class="err" role="alert"></div>
          <button class="btn-primary" type="submit">Save</button>
        </form>
      </div>
    </div>

    <!-- Not built yet -->
    <div id="view-soon" class="hide" style="display:flex;flex-direction:column;gap:24px">
      <div class="head"><div><h1 id="soon-title">Coming soon</h1></div></div>
      <div class="card"><p id="soon-text" style="margin:0"></p></div>
    </div>

  </main>
</div>

<div id="toast" class="toast hide" role="status"></div>

<script>
const $ = (id) => document.getElementById(id);
const esc = (s) => String(s == null ? '' : s);

async function api(action, body) {
  const opt = { method: body ? 'POST' : 'GET', credentials: 'same-origin', headers: {} };
  let url = 'api.php?action=' + action;
  if (body && body.__get) { url += '&' + new URLSearchParams(body.__get); opt.method = 'GET'; }
  else if (body) { opt.headers['X-IVR'] = '1'; opt.body = body instanceof FormData ? body : new URLSearchParams(body); }
  const r = await fetch(url, opt);
  let d;
  try { d = await r.json(); } catch (e) { throw new Error('Server error (' + r.status + ')'); }
  if (!r.ok) { const e = new Error(d.error || 'Something went wrong'); e.code = r.status; throw e; }
  return d;
}

let toastTimer;
function toast(msg) {
  const t = $('toast'); t.textContent = msg; t.classList.remove('hide');
  clearTimeout(toastTimer); toastTimer = setTimeout(() => t.classList.add('hide'), 3500);
}

// ---------- login ----------
let needSetup = false;
async function boot() {
  const s = await api('state');
  if (s.logged_in) return showApp();
  needSetup = !s.setup;
  $('auth-title').textContent = needSetup ? 'Set up the dashboard' : 'Admin login';
  $('auth-btn').textContent = needSetup ? 'Create admin login' : 'Log in';
  $('auth-hint').classList.toggle('hide', !needSetup);
  $('a-key-wrap').classList.toggle('hide', !needSetup);
  $('a-pw').autocomplete = needSetup ? 'new-password' : 'current-password';
  $('v-auth').classList.remove('hide');
}
$('auth-form').addEventListener('submit', async (e) => {
  e.preventDefault();
  $('auth-err').textContent = '';
  try {
    const body = { password: $('a-pw').value };
    if (needSetup) body.sarvam_key = $('a-key').value;
    await api(needSetup ? 'setup' : 'login', body);
    $('a-pw').value = '';
    $('v-auth').classList.add('hide');
    showApp();
  } catch (err) { $('auth-err').textContent = err.message; }
});
$('logout').addEventListener('click', async () => { await endCall(); await api('logout', {}); location.reload(); });

// ---------- views ----------
function show(view) {
  for (const v of ['overview', 'history', 'settings', 'soon']) $('view-' + v).classList.toggle('hide', v !== view);
}
document.querySelectorAll('.nav button').forEach((b) => b.addEventListener('click', () => {
  document.querySelectorAll('.nav button').forEach((x) => x.classList.remove('on'));
  b.classList.add('on');
  show(b.dataset.view);
  if (b.dataset.view === 'soon') {
    $('soon-title').textContent = b.dataset.name;
    $('soon-text').textContent = b.dataset.name + ' is step ' + b.dataset.step + ' of the build. It is not built yet.';
  }
  if (b.dataset.scroll) $(b.dataset.scroll).scrollIntoView({ behavior: 'smooth' });
  else window.scrollTo(0, 0);
}));
document.querySelector('[data-goto="history"]').addEventListener('click', () => document.querySelector('.nav [data-view="history"]').click());
$('go-live').addEventListener('click', () => toast('Go live opens after the phone line is connected (step 3).'));
$('call-me').addEventListener('click', () => toast('Calling a phone opens after the phone line is connected (step 3).'));

// ---------- overview ----------
const mmss = (s) => Math.floor(s / 60) + ':' + String(s % 60).padStart(2, '0');
const when = (ts) => new Date(ts * 1000).toLocaleString('en-IN', { day: 'numeric', month: 'short', hour: 'numeric', minute: '2-digit' });

function cell(text) { const td = document.createElement('td'); td.textContent = text; return td; }
function fillTable(tbody, calls) {
  tbody.textContent = '';
  if (!calls.length) {
    const tr = document.createElement('tr'); const td = cell('No calls yet.'); td.colSpan = 6; td.className = 'empty';
    tr.appendChild(td); tbody.appendChild(tr); return;
  }
  for (const c of calls) {
    const tr = document.createElement('tr');
    tr.appendChild(cell(when(c.started)));
    tr.appendChild(cell('Browser test'));
    tr.appendChild(cell(c.asked || 'Caller did not speak'));
    const res = document.createElement('td'); const pill = document.createElement('span');
    pill.className = 'pill' + (c.flagged ? ' bad' : ''); pill.textContent = c.flagged ? 'Wrong answer marked' : 'Test call';
    res.appendChild(pill); tr.appendChild(res);
    tr.appendChild(cell(mmss(c.seconds)));
    const rev = document.createElement('td'); const btn = document.createElement('button');
    btn.type = 'button'; btn.className = 'link'; btn.textContent = 'View';
    btn.addEventListener('click', () => openCall(c.id));
    rev.appendChild(btn); tr.appendChild(rev);
    tbody.appendChild(tr);
  }
}

function fillVoices(sel, voices, current) {
  sel.textContent = '';
  for (const v of voices) {
    const o = document.createElement('option'); o.value = v;
    const name = v.split('_')[0]; o.textContent = name.charAt(0).toUpperCase() + name.slice(1) + (v.includes('_') ? ' (conversation)' : '');
    if (v === current) o.selected = true; sel.appendChild(o);
  }
}

let keyMask = '';
$('set-key').addEventListener('focus', (e) => { if (e.target.value === keyMask) e.target.value = ''; });
$('set-key').addEventListener('blur', (e) => { if (e.target.value.trim() === '') e.target.value = keyMask; });

async function loadOverview() {
  const d = await api('overview');
  $('s-calls').textContent = d.stats.calls;
  $('s-leads').textContent = d.stats.leads;
  $('s-staff').textContent = d.stats.staff;
  $('s-avg').textContent = mmss(d.stats.avg_seconds);
  fillTable($('recent'), d.calls.slice(0, 5));
  fillTable($('all'), d.calls);
  fillVoices($('voice'), d.voices, d.voice);
  fillVoices($('set-voice'), d.voices, d.voice);
  // Show the saved key masked, so it is clear it is still stored. The full key never leaves the server.
  keyMask = d.has_key ? '••••••••••••' + d.key_hint : '';
  $('set-key').value = keyMask;
  $('key-state').textContent = d.has_key ? 'Key saved on the server, ending in ' + d.key_hint + '. Paste a new key only to replace it.' : 'No key saved yet. Test calls need one.';
}
async function showApp() {
  $('v-app').classList.remove('hide');
  try { await loadOverview(); } catch (err) { toast(err.message); }
}

async function saveVoice(v) { try { await api('save_settings', { voice: v }); $('voice').value = v; $('set-voice').value = v; } catch (err) { toast(err.message); } }
$('voice').addEventListener('change', (e) => saveVoice(e.target.value));

$('set-form').addEventListener('submit', async (e) => {
  e.preventDefault();
  $('set-err').textContent = '';
  try {
    const typed = $('set-key').value.trim();
    await api('save_settings', { voice: $('set-voice').value, sarvam_key: typed === keyMask ? '' : typed, password: $('set-pw').value });
    $('set-key').value = ''; $('set-pw').value = '';
    await loadOverview();
    toast('Saved.');
  } catch (err) { $('set-err').textContent = err.message; }
});

function addLine(box, who, text, flagged) {
  const empty = box.querySelector('.empty'); if (empty) empty.remove();
  const div = document.createElement('div'); div.className = 'line ' + who + (flagged ? ' flag' : '');
  const s = document.createElement('small'); s.textContent = who === 'agent' ? 'Agent' : 'Caller';
  div.appendChild(s); div.appendChild(document.createTextNode(esc(text)));
  box.appendChild(div); box.scrollTop = box.scrollHeight;
  return div;
}

async function openCall(id) {
  try {
    const d = await api('call_get', { __get: { id } });
    document.querySelector('.nav [data-view="history"]').click();
    $('detail-title').textContent = 'Call on ' + when(d.call.started) + ', ' + mmss(d.call.seconds);
    $('detail-lines').textContent = '';
    for (const l of d.call.lines) addLine($('detail-lines'), l.who, l.text, l.flagged);
    $('detail').classList.remove('hide');
    $('detail').scrollIntoView({ behavior: 'smooth' });
  } catch (err) { toast(err.message); }
}
$('detail-close').addEventListener('click', () => $('detail').classList.add('hide'));

// ---------- browser test call ----------
// One turn at a time: the agent speaks, then the microphone listens until the caller pauses.
let ctx = null, stream = null, proc = null, callId = null, flagId = null, lastAgent = null;
let phase = 'idle';            // idle | busy | speaking | listening
let pre = [], rec = [], inSpeech = false, silentMs = 0, speechMs = 0, noise = 0.005;

function setPhase(p, msg) { phase = p; $('status').textContent = msg || ''; }

function onAudio(e) {
  if (phase !== 'listening') return;
  const buf = new Float32Array(e.inputBuffer.getChannelData(0));
  let sum = 0; for (let i = 0; i < buf.length; i++) sum += buf[i] * buf[i];
  const rms = Math.sqrt(sum / buf.length);
  const ms = buf.length / ctx.sampleRate * 1000;
  const limit = Math.max(0.015, noise * 3.5);
  if (!inSpeech) {
    pre.push(buf); if (pre.length > 4) pre.shift();
    if (rms > limit) { inSpeech = true; rec = pre.slice(); pre = []; speechMs = ms; silentMs = 0; }
    else noise = noise * 0.95 + rms * 0.05;
    return;
  }
  rec.push(buf);
  if (rms > limit) { speechMs += ms; silentMs = 0; } else silentMs += ms;
  if (silentMs >= 700 || rec.length * ms >= 25000) {
    // Drop most of the trailing silence so the upload is smaller.
    const chunks = rec.slice(0, rec.length - Math.max(0, Math.floor((silentMs - 170) / ms))), enough = speechMs >= 250;
    inSpeech = false; rec = []; pre = [];
    if (enough) sendTurn(chunks);
  }
}

function toWav(chunks, rate) {
  let len = 0; for (const c of chunks) len += c.length;
  const all = new Float32Array(len); let o = 0; for (const c of chunks) { all.set(c, o); o += c.length; }
  const ratio = rate / 16000, n = Math.floor(all.length / ratio);
  const view = new DataView(new ArrayBuffer(44 + n * 2));
  const str = (p, s) => { for (let i = 0; i < s.length; i++) view.setUint8(p + i, s.charCodeAt(i)); };
  str(0, 'RIFF'); view.setUint32(4, 36 + n * 2, true); str(8, 'WAVE'); str(12, 'fmt ');
  view.setUint32(16, 16, true); view.setUint16(20, 1, true); view.setUint16(22, 1, true);
  view.setUint32(24, 16000, true); view.setUint32(28, 32000, true); view.setUint16(32, 2, true); view.setUint16(34, 16, true);
  str(36, 'data'); view.setUint32(40, n * 2, true);
  for (let i = 0; i < n; i++) {
    const a = Math.floor(i * ratio), b = Math.max(a + 1, Math.floor((i + 1) * ratio));
    let s = 0; for (let j = a; j < b && j < all.length; j++) s += all[j];
    s = Math.max(-1, Math.min(1, s / (b - a)));
    view.setInt16(44 + i * 2, s < 0 ? s * 0x8000 : s * 0x7FFF, true);
  }
  return new Blob([view], { type: 'audio/wav' });
}

async function speak(b64, startedAt, ms) {
  const bin = atob(b64); const bytes = new Uint8Array(bin.length);
  for (let i = 0; i < bin.length; i++) bytes[i] = bin.charCodeAt(i);
  const audio = await ctx.decodeAudioData(bytes.buffer);
  if (!callId) return;
  const node = ctx.createBufferSource(); node.buffer = audio; node.connect(ctx.destination);
  if (startedAt) {
    const sec = (n) => (n / 1000).toFixed(1);
    let t = 'Reply speed ' + sec(performance.now() - startedAt) + ' s';
    if (ms) {
      const net = Math.max(0, performance.now() - startedAt - ms.hear - ms.think - ms.speak);
      t += ' (hear ' + sec(ms.hear) + ', think ' + sec(ms.think) + ', speak ' + sec(ms.speak) + ', network ' + sec(net) + ')';
    }
    $('speed').textContent = t;
  }
  setPhase('speaking', 'Agent is speaking');
  node.onended = () => { if (callId) { pre = []; setPhase('listening', 'Listening. Speak now.'); } };
  node.start();
}

async function sendTurn(chunks) {
  const t0 = performance.now();
  setPhase('busy', 'Thinking');
  try {
    const fd = new FormData();
    fd.append('call_id', callId);
    fd.append('audio', toWav(chunks, ctx.sampleRate), 'caller.wav');
    const d = await api('call_turn', fd);
    if (!callId) return;
    if (!d.reply) return setPhase('listening', 'Did not catch that. Please say it again.');
    addLine($('lines'), 'caller', d.heard);
    lastAgent = addLine($('lines'), 'agent', d.reply);
    $('flag').disabled = false;
    await speak(d.audio, t0, d.ms);
  } catch (err) {
    $('call-err').textContent = err.message;
    if (callId) setPhase('listening', 'Listening. Speak now.');
  }
}

async function startCall() {
  $('call-err').textContent = '';
  if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) { $('call-err').textContent = 'This browser cannot use the microphone.'; return; }
  $('talk').disabled = true; $('talk-label').textContent = 'Connecting';
  try {
    stream = await navigator.mediaDevices.getUserMedia({ audio: { echoCancellation: true, noiseSuppression: true, channelCount: 1 } });
    ctx = new (window.AudioContext || window.webkitAudioContext)();
    await ctx.resume();
    const src = ctx.createMediaStreamSource(stream);
    proc = ctx.createScriptProcessor(4096, 1, 1);
    const mute = ctx.createGain(); mute.gain.value = 0;
    proc.onaudioprocess = onAudio;
    src.connect(proc); proc.connect(mute); mute.connect(ctx.destination);

    setPhase('busy', 'Connecting');
    const d = await api('call_start', {});
    callId = flagId = d.call_id;
    $('lines').textContent = ''; $('speed').textContent = '';
    lastAgent = addLine($('lines'), 'agent', d.text);
    $('talk-label').textContent = 'Call in progress';
    $('end').disabled = false; $('flag').disabled = false;
    await speak(d.audio);
  } catch (err) {
    $('call-err').textContent = err.name === 'NotAllowedError' ? 'Microphone permission was refused. Allow it and try again.' : err.message;
    await endCall();
  }
}

async function endCall() {
  const id = callId; callId = null;
  if (proc) { proc.onaudioprocess = null; proc.disconnect(); proc = null; }
  if (stream) { stream.getTracks().forEach((t) => t.stop()); stream = null; }
  if (ctx) { try { await ctx.close(); } catch (e) {} ctx = null; }
  inSpeech = false; rec = []; pre = [];
  setPhase('idle', id ? 'Call ended.' : '');
  $('talk').disabled = false; $('talk-label').textContent = 'Talk to the agent here';
  $('end').disabled = true;
  if (id) {
    try { await api('call_end', { call_id: id }); await loadOverview(); } catch (e) {}
  }
}

$('talk').addEventListener('click', startCall);
$('end').addEventListener('click', endCall);
$('flag').addEventListener('click', async () => {
  if (!lastAgent || !flagId) return toast('Start a call first.');
  try {
    await api('call_flag', { call_id: flagId });
    lastAgent.classList.add('flag');
    toast('Marked. It shows in the call history.');
  } catch (err) { toast(err.message); }
});

boot().catch((err) => { document.body.textContent = err.message; });
</script>
</body>
</html>
