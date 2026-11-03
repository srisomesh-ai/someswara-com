<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex, nofollow">
<title>Previs engine</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.19.0/dist/tabler-icons.min.css">
<style>
:root{--bg:#f6f5f1;--card:#fff;--soft:#efede6;--text:#1f1e1c;--muted:#6b6963;--line:rgba(0,0,0,.12);--line2:rgba(0,0,0,.25);
--ok-bg:#eaf3de;--ok:#3b6d11;--warn-bg:#faeeda;--warn:#854f0b;--bad-bg:#fcebeb;--bad:#a32d2d;--accent:#185fa5;--accent-bg:#e6f1fb}
@media (prefers-color-scheme:dark){:root{--bg:#1a1a19;--card:#262624;--soft:#30302e;--text:#f1efe8;--muted:#a3a19a;--line:rgba(255,255,255,.12);--line2:rgba(255,255,255,.25);
--ok-bg:#27500a;--ok:#c0dd97;--warn-bg:#633806;--warn:#fac775;--bad-bg:#791f1f;--bad:#f7c1c1;--accent:#85b7eb;--accent-bg:#0c447c}}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--text);font:15px/1.5 system-ui,-apple-system,"Segoe UI",Roboto,sans-serif}
.wrap{max-width:560px;margin:0 auto;padding:16px 16px 40px}
.card{background:var(--card);border:.5px solid var(--line);border-radius:12px;padding:14px}
.row{display:flex;gap:8px;align-items:center}
.grid2{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px}
.metric{background:var(--card);border-radius:8px;padding:10px}
.metric small{display:block;font-size:12px;color:var(--muted)}
.metric b{font-size:18px;font-weight:500}
.badge{font-size:12px;padding:3px 10px;border-radius:8px;white-space:nowrap}
.b-ok{background:var(--ok-bg);color:var(--ok)}.b-warn{background:var(--warn-bg);color:var(--warn)}.b-bad{background:var(--bad-bg);color:var(--bad)}.b-off{background:var(--soft);color:var(--muted)}
button{font:inherit;color:var(--text);background:transparent;border:.5px solid var(--line2);border-radius:8px;padding:9px 12px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;gap:6px}
button:active{transform:scale(.98)}button[disabled]{opacity:.5}
.primary{background:var(--text);color:var(--bg);border-color:var(--text)}
.danger{color:var(--bad)}
input,textarea,select{font:inherit;color:var(--text);background:var(--card);border:.5px solid var(--line2);border-radius:8px;padding:9px 10px;width:100%}
textarea{resize:vertical}
label{font-size:13px;color:var(--muted);display:block;margin:10px 0 4px}
.drop{border:1px dashed var(--line2);border-radius:8px;padding:14px;text-align:center;font-size:13px;color:var(--muted);cursor:pointer;position:relative;overflow:hidden}
.drop img{max-width:100%;max-height:180px;border-radius:6px;display:block;margin:0 auto}
.chip{font-size:12px;padding:4px 10px;border-radius:8px}
.chip.on{background:var(--accent-bg);color:var(--accent);border-color:var(--accent)}
.err{color:var(--bad);font-size:13px;margin-top:6px;min-height:0}
.hint{font-size:13px;color:var(--muted)}
.clip{background:var(--card);border-radius:8px;padding:8px}
.clip .media{aspect-ratio:832/480;background:var(--soft);border-radius:6px;display:flex;align-items:center;justify-content:center;overflow:hidden;position:relative}
.clip video,.clip img{width:100%;height:100%;object-fit:cover;display:block}
.clip .cap{font-size:12px;margin-top:6px;display:flex;justify-content:space-between;gap:6px;align-items:center}
.clip .p{font-size:12px;color:var(--muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.bar{position:absolute;left:0;bottom:0;height:3px;background:var(--accent)}
.icon-btn{padding:4px 6px;border:none}
.spin{animation:s 1s linear infinite}@keyframes s{to{transform:rotate(360deg)}}
.hide{display:none!important}
a{color:var(--accent)}
</style>
</head>
<body>
<div class="wrap">

<!-- First-time setup -->
<div id="v-setup" class="card hide">
  <div style="font-size:18px;font-weight:500">Set up your previs engine</div>
  <p class="hint" style="margin:4px 0 0">One-time setup. These stay on your server.</p>
  <label>RunPod API key</label>
  <input id="s-key" autocomplete="off" placeholder="rpa_XXXXXXXX">
  <label>Network volume ID (optional, keeps models between sessions)</label>
  <input id="s-vol" autocomplete="off" placeholder="leave empty if you don't have one">
  <label>Panel password</label>
  <input id="s-pw" type="password" autocomplete="new-password" placeholder="at least 6 characters">
  <div id="s-err" class="err"></div>
  <button class="primary" style="width:100%;margin-top:12px" onclick="doSetup()">Save and continue</button>
</div>

<!-- Login -->
<div id="v-login" class="card hide">
  <div style="font-size:18px;font-weight:500">Previs engine</div>
  <label>Password</label>
  <input id="l-pw" type="password" autocomplete="current-password" onkeydown="if(event.key==='Enter')doLogin()">
  <div id="l-err" class="err"></div>
  <button class="primary" style="width:100%;margin-top:12px" onclick="doLogin()">Log in</button>
</div>

<!-- Main panel -->
<div id="v-main" class="hide" style="background:var(--soft);border-radius:12px;padding:14px">
  <div class="row" style="justify-content:space-between;margin-bottom:12px">
    <span style="font-size:16px;font-weight:500">Previs engine</span>
    <span id="badge" class="badge b-off">GPU off</span>
  </div>

  <div class="grid2" style="margin-bottom:8px">
    <div class="metric"><small id="m-gpu">GPU</small><b id="m-rate">–</b></div>
    <div class="metric"><small>Running · spent</small><b id="m-spent">–</b></div>
  </div>
  <div id="bootmsg" class="hint" style="margin:0 0 8px;min-height:20px"></div>

  <div class="row" style="margin-bottom:14px">
    <button id="btn-start" style="flex:1" onclick="startGpu()"><i class="ti ti-player-play"></i> Start GPU</button>
    <button id="btn-stop" class="danger" style="flex:1" onclick="stopGpu()"><i class="ti ti-power"></i> Stop GPU</button>
  </div>
  <div id="g-err" class="err" style="margin:-8px 0 10px"></div>

  <div class="card" style="margin-bottom:14px">
    <div class="hint" style="margin-bottom:6px">New shot</div>
    <div id="drop" class="drop" onclick="document.getElementById('file').click()">
      <div id="drop-empty"><i class="ti ti-photo-up" style="font-size:22px"></i><br>Add reference photo</div>
      <img id="preview" class="hide" alt="Reference photo">
    </div>
    <input id="file" type="file" accept="image/*" class="hide">
    <textarea id="prompt" rows="3" style="margin-top:8px" placeholder="Actress at a rain-soaked window, moody light"></textarea>
    <div id="chips" class="row" style="flex-wrap:wrap;gap:6px;margin:8px 0"></div>
    <div class="row" style="margin-bottom:10px">
      <select id="seconds" style="flex:1"><option value="5">Length: 5 s</option><option value="3">Length: 3 s</option></select>
      <select id="quality" style="flex:1"><option value="normal">Quality: normal</option><option value="high">Quality: high</option></select>
    </div>
    <button id="btn-render" class="primary" style="width:100%" onclick="renderShot()"><i class="ti ti-movie"></i> Render shot</button>
    <div id="r-err" class="err"></div>
  </div>

  <div class="hint" style="margin-bottom:6px">Your clips</div>
  <div id="clips" class="grid2"></div>
  <div id="noclips" class="hint" style="padding:8px 0">Rendered shots appear here and stay saved after the GPU is off.</div>

  <div class="row" style="justify-content:space-between;margin-top:18px">
    <button class="icon-btn hint" onclick="toggleSettings()"><i class="ti ti-settings"></i> Settings</button>
    <button class="icon-btn hint" onclick="logout()"><i class="ti ti-logout"></i> Log out</button>
  </div>

  <div id="v-settings" class="card hide" style="margin-top:10px">
    <label>RunPod API key <span id="k-hint"></span></label>
    <input id="e-key" autocomplete="off" placeholder="leave empty to keep current key">
    <label>Network volume ID</label>
    <input id="e-vol" autocomplete="off">
    <label>Auto-shutdown after idle (minutes)</label>
    <input id="e-idle" type="number" min="5" max="120" step="1">
    <label>New password</label>
    <input id="e-pw" type="password" autocomplete="new-password" placeholder="leave empty to keep current">
    <div id="e-err" class="err"></div>
    <button class="primary" style="width:100%;margin-top:12px" onclick="saveSettings()">Save settings</button>
    <p class="hint" style="margin:8px 0 0">Changes apply the next time you start the GPU.</p>
  </div>
</div>

</div>
<script>
const $ = id => document.getElementById(id);
const CAMERA = {"Dolly in":"slow dolly-in camera move","Pan left":"camera pans slowly to the left","Tracking":"smooth tracking shot following the subject","Crane up":"crane shot rising upward","Handheld":"handheld camera, subtle natural shake","Static":"static locked-off shot","Close-up":"close-up shot, 85mm lens, shallow depth of field","Wide":"wide establishing shot, 24mm lens"};
let imageBlob = null, gpuOn = false, gpuReady = false, statusTimer = null, jobsTimer = null;

async function api(action, data, method) {
  let opt = {method: method || (data ? 'POST' : 'GET'), credentials: 'same-origin'};
  if (data) { const f = data instanceof FormData ? data : new FormData(); if (!(data instanceof FormData)) for (const k in data) f.append(k, data[k]); f.append('action', action); opt.body = f; }
  const r = await fetch('api.php' + (data ? '' : '?action=' + action), opt);
  const j = await r.json().catch(() => ({error: 'The server sent an unexpected reply.'}));
  if (r.status === 401 && action !== 'login') { show('login'); throw new Error(j.error); }
  if (!r.ok || j.error) throw new Error(j.error || 'Something went wrong.');
  return j;
}
function show(v) { ['setup','login','main'].forEach(x => $('v-'+x).classList.toggle('hide', x !== v)); if (v === 'main') startPolling(); else stopPolling(); }
function setErr(id, msg) { $(id).textContent = msg || ''; }

async function boot() {
  const s = await api('state');
  show(!s.setup ? 'setup' : s.logged_in ? 'main' : 'login');
}
async function doSetup() {
  setErr('s-err');
  try { await api('setup', {api_key: $('s-key').value, volume_id: $('s-vol').value, password: $('s-pw').value}); show('main'); }
  catch (e) { setErr('s-err', e.message); }
}
async function doLogin() {
  setErr('l-err');
  try { await api('login', {password: $('l-pw').value}); $('l-pw').value = ''; show('main'); }
  catch (e) { setErr('l-err', e.message); }
}
async function logout() { await api('logout', {}); show('login'); }

function startPolling() { refreshStatus(); refreshJobs(); stopPolling(); statusTimer = setInterval(refreshStatus, 8000); jobsTimer = setInterval(refreshJobs, 6000); }
function stopPolling() { clearInterval(statusTimer); clearInterval(jobsTimer); }

async function refreshStatus() {
  let s; try { s = await api('status'); } catch (e) { return; }
  gpuOn = s.on; gpuReady = s.on && s.phase === 'ready';
  const b = $('badge');
  if (!s.on) { b.className = 'badge b-off'; b.innerHTML = 'GPU off'; $('m-gpu').textContent = 'GPU'; $('m-rate').textContent = '–'; $('m-spent').textContent = '–'; $('bootmsg').textContent = s.note || ''; }
  else {
    if (s.phase === 'ready') { b.className = 'badge b-ok'; b.innerHTML = '<i class="ti ti-circle-check"></i> GPU ready'; }
    else if (s.phase === 'error') { b.className = 'badge b-bad'; b.innerHTML = '<i class="ti ti-alert-triangle"></i> Problem'; }
    else { b.className = 'badge b-warn'; b.innerHTML = '<i class="ti ti-loader-2 spin"></i> Starting'; }
    $('m-gpu').textContent = s.gpu || 'GPU';
    $('m-rate').textContent = s.cost_hr ? '$' + Number(s.cost_hr).toFixed(2) + '/hr' : '–';
    $('m-spent').textContent = s.minutes + ' min · $' + Number(s.spent).toFixed(2);
    $('bootmsg').textContent = s.phase === 'ready' ? 'Auto-shuts down after idle time, so you can walk away.' : s.phase === 'error' ? s.msg + '. Stop the GPU and start again.' : s.msg + '…';
  }
  $('btn-start').disabled = s.on; $('btn-stop').disabled = !s.on;
}
async function startGpu() {
  setErr('g-err'); $('btn-start').disabled = true; $('btn-start').innerHTML = '<i class="ti ti-loader-2 spin"></i> Starting';
  try { await api('start', {}); } catch (e) { setErr('g-err', e.message); }
  $('btn-start').innerHTML = '<i class="ti ti-player-play"></i> Start GPU'; refreshStatus();
}
async function stopGpu() {
  if (!confirm('Stop the GPU now? Any shot still rendering will be lost.')) return;
  setErr('g-err');
  try { await api('stop', {}); } catch (e) { setErr('g-err', e.message); }
  refreshStatus(); refreshJobs();
}

// Reference photo: shrink on the phone before upload
$('file').addEventListener('change', e => { const f = e.target.files[0]; if (f) loadImage(f); });
['dragover','drop'].forEach(ev => $('drop').addEventListener(ev, e => { e.preventDefault(); if (ev === 'drop' && e.dataTransfer.files[0]) loadImage(e.dataTransfer.files[0]); }));
function loadImage(file) {
  setErr('r-err');
  if (!file.type.startsWith('image/')) return setErr('r-err', 'Choose an image file.');
  const img = new Image();
  img.onload = () => {
    const max = 1280, sc = Math.min(1, max / Math.max(img.width, img.height));
    const c = document.createElement('canvas'); c.width = Math.round(img.width * sc); c.height = Math.round(img.height * sc);
    c.getContext('2d').drawImage(img, 0, 0, c.width, c.height);
    c.toBlob(b => { imageBlob = b; $('preview').src = URL.createObjectURL(b); $('preview').classList.remove('hide'); $('drop-empty').classList.add('hide'); }, 'image/jpeg', 0.9);
  };
  img.onerror = () => setErr('r-err', "That image couldn't be opened. Try a JPG or PNG.");
  img.src = URL.createObjectURL(file);
}

// Camera chips add film language to the prompt
for (const name in CAMERA) {
  const b = document.createElement('button'); b.className = 'chip'; b.textContent = name; b.type = 'button';
  b.onclick = () => { b.classList.toggle('on'); };
  $('chips').appendChild(b);
}
$('prompt').addEventListener('input', () => setErr('r-err'));

async function renderShot() {
  setErr('r-err');
  const text = $('prompt').value.trim();
  if (!imageBlob) return setErr('r-err', 'Add a reference photo first.');
  if (!text) return setErr('r-err', 'Describe the shot first.');
  if (!gpuOn) return setErr('r-err', 'Start the GPU first.');
  if (!gpuReady) return setErr('r-err', 'The GPU is still getting ready. Try again in a moment.');
  const moves = [...document.querySelectorAll('.chip.on')].map(b => CAMERA[b.textContent]);
  const fd = new FormData();
  fd.append('image', imageBlob, 'ref.jpg');
  fd.append('prompt', [text, ...moves].join(', '));
  fd.append('seconds', $('seconds').value); fd.append('quality', $('quality').value);
  const btn = $('btn-render'); btn.disabled = true; btn.innerHTML = '<i class="ti ti-loader-2 spin"></i> Sending';
  try { await api('render', fd); refreshJobs(); }
  catch (e) { setErr('r-err', e.message); }
  btn.disabled = false; btn.innerHTML = '<i class="ti ti-movie"></i> Render shot';
}

const esc = s => s.replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
let lastJobs = '';
async function refreshJobs() {
  let jobs; try { jobs = await api('jobs'); } catch (e) { return; }
  const key = JSON.stringify(jobs); if (key === lastJobs) return; lastJobs = key;
  $('noclips').classList.toggle('hide', jobs.length > 0);
  $('clips').innerHTML = jobs.map(j => {
    const f = 'api.php?action=file&id=' + j.id;
    let media, cap;
    if (j.status === 'done') {
      media = `<video src="${f}" poster="${f}&type=thumb" controls playsinline preload="none"></video>`;
      cap = `<span>${j.seconds} s · ${j.quality}</span><span><a href="${f}&dl=1" aria-label="Download"><i class="ti ti-download"></i></a> <button class="icon-btn" aria-label="Delete" onclick="del('${j.id}')"><i class="ti ti-trash"></i></button></span>`;
    } else if (j.status === 'failed') {
      media = `<img src="${f}&type=thumb" alt="" style="opacity:.4">`;
      cap = `<span style="color:var(--bad)">${esc(j.error || 'Failed')}</span><button class="icon-btn" aria-label="Delete" onclick="del('${j.id}')"><i class="ti ti-trash"></i></button>`;
    } else {
      media = `<img src="${f}&type=thumb" alt="" style="opacity:.5"><i class="ti ti-loader-2 spin" style="position:absolute;font-size:22px"></i><div class="bar" style="width:${j.pct||0}%"></div>`;
      cap = `<span>${j.status === 'queued' ? 'Waiting in queue' : 'Rendering · ' + (j.pct||0) + '%'}</span>`;
    }
    return `<div class="clip"><div class="media">${media}</div><div class="cap">${cap}</div><div class="p" title="${esc(j.prompt)}">${esc(j.prompt)}</div></div>`;
  }).join('');
}
async function del(id) { if (!confirm('Delete this clip?')) return; await api('delete', {id}); lastJobs = ''; refreshJobs(); }

async function toggleSettings() {
  const el = $('v-settings'); el.classList.toggle('hide');
  if (!el.classList.contains('hide')) { const s = await api('settings'); $('k-hint').textContent = '(current ' + s.key_hint + ')'; $('e-vol').value = s.volume_id; $('e-idle').value = s.idle_minutes; $('e-key').value = ''; $('e-pw').value = ''; }
}
async function saveSettings() {
  setErr('e-err');
  try { await api('save_settings', {api_key: $('e-key').value, volume_id: $('e-vol').value, idle_minutes: $('e-idle').value, password: $('e-pw').value}); $('v-settings').classList.add('hide'); }
  catch (e) { setErr('e-err', e.message); }
}
boot();
</script>
</body>
</html>
