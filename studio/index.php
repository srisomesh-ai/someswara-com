<?php
// AI Video Studio — setup, login and the main screen.
require __DIR__ . '/lib.php';

$cfg = config();
$msg = '';
$view = 'app';

if (isset($_GET['logout'])) {
    $_SESSION = array();
    session_destroy();
    header('Location: ./');
    exit;
}

// First run: choose a password and paste the fal.ai key.
if (!is_setup_done() && !($cfg['password_hash'] !== '' && !is_logged_in())) {
    $view = 'setup';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['setup'])) {
        $pw = (string) $_POST['password'];
        $key = trim((string) $_POST['fal_key']);
        if ($cfg['password_hash'] === '' && strlen($pw) < 6) {
            $msg = 'Use a password of at least 6 characters.';
        } elseif ($key === '') {
            $msg = 'Paste your fal.ai API key.';
        } else {
            if ($cfg['password_hash'] === '') { $cfg['password_hash'] = password_hash($pw, PASSWORD_DEFAULT); }
            $cfg['fal_key'] = $key;
            save_config($cfg);
            $_SESSION['aivs_ok'] = true;
            header('Location: ./');
            exit;
        }
    }
} elseif (!is_logged_in()) {
    $view = 'login';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
        if (password_verify((string) $_POST['password'], $cfg['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['aivs_ok'] = true;
            header('Location: ./');
            exit;
        }
        sleep(1);
        $msg = 'Wrong password. Try again.';
    }
} elseif (isset($_GET['settings'])) {
    $view = 'settings';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['settings'])) {
        $key = trim((string) $_POST['fal_key']);
        $rate = (float) $_POST['inr_rate'];
        $pw = (string) $_POST['new_password'];
        if ($key !== '') { $cfg['fal_key'] = $key; }
        if ($rate > 0) { $cfg['inr_rate'] = $rate; }
        if ($pw !== '') {
            if (strlen($pw) < 6) { $msg = 'Use a password of at least 6 characters.'; }
            else { $cfg['password_hash'] = password_hash($pw, PASSWORD_DEFAULT); }
        }
        if ($msg === '') {
            save_config($cfg);
            header('Location: ./');
            exit;
        }
    }
}

$modelsForJs = array();
foreach (models() as $k => $m) {
    $modelsForJs[$k] = array('name' => $m['name'], 'price' => $m['price'], 'lengths' => $m['lengths'], 'aspects' => $m['aspects']);
}
$keyHint = $cfg['fal_key'] !== '' ? '••••' . substr($cfg['fal_key'], -4) : 'not set';
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex, nofollow">
<title>AI Video Studio</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.19.0/dist/tabler-icons.min.css">
<style>
:root{--bg:#f4f4f1;--card:#fff;--soft:#f7f7f4;--text:#1f1f1d;--muted:#6b6a65;--faint:#9c9b95;--line:rgba(0,0,0,.12);--line2:rgba(0,0,0,.22);--accent:#2463d1;--accent-bg:#e6effc;--accent-text:#1a4fa8;--danger:#b3261e;--radius:10px}
@media (prefers-color-scheme:dark){:root{--bg:#1a1a19;--card:#242423;--soft:#2c2c2a;--text:#ecebe6;--muted:#a9a8a2;--faint:#7d7c77;--line:rgba(255,255,255,.12);--line2:rgba(255,255,255,.24);--accent:#4a86ea;--accent-bg:#1d3357;--accent-text:#a9c8f7;--danger:#f08b84}}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--text);font:15px/1.5 -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif}
.wrap{max-width:520px;margin:0 auto;padding:12px 12px 40px}
.card{background:var(--card);border:.5px solid var(--line);border-radius:14px;padding:16px}
.top{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px}
.brand{display:flex;align-items:center;gap:8px;font-weight:600;font-size:17px}
.brand i{font-size:22px}
.top-right{display:flex;align-items:center;gap:6px}
.spent{font-size:12px;background:var(--accent-bg);color:var(--accent-text);padding:4px 10px;border-radius:var(--radius);white-space:nowrap}
.icon-btn{width:34px;height:34px;display:inline-flex;align-items:center;justify-content:center;border-radius:var(--radius);color:var(--muted);text-decoration:none;font-size:19px}
.label{font-size:13px;color:var(--muted);margin:0 0 6px}
textarea,input[type=text],input[type=password],input[type=number],select{width:100%;font:inherit;color:var(--text);background:var(--card);border:.5px solid var(--line2);border-radius:var(--radius);padding:10px}
textarea{min-height:88px;resize:vertical}
select{height:42px;padding:0 10px}
button,.btn{font:inherit;font-size:14px;color:var(--text);background:transparent;border:.5px solid var(--line2);border-radius:var(--radius);height:40px;padding:0 12px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;gap:6px;text-decoration:none}
button:active{transform:scale(.98)}
.row2{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px}
.mt{margin-top:14px}
.models .m{border:.5px solid var(--line);border-radius:var(--radius);padding:9px 10px;cursor:pointer;background:var(--card);text-align:left;height:auto;display:block;width:100%}
.models .m.sel{border:2px solid var(--accent);padding:8px 9px}
.m .n{font-size:14px;font-weight:600}
.m .p{font-size:12px;color:var(--muted)}
.check{display:flex;align-items:center;gap:8px;font-size:14px;margin:14px 0}
.check input{width:18px;height:18px}
.primary{width:100%;height:48px;background:var(--accent);color:#fff;border:none;font-size:16px;font-weight:600}
.primary[disabled]{opacity:.6}
.err{color:var(--danger);font-size:13px;margin:8px 0 0;min-height:0}
.photo{display:flex;align-items:center;gap:10px;margin-top:10px;font-size:13px;color:var(--muted)}
.photo img{width:52px;height:52px;object-fit:cover;border-radius:8px;border:.5px solid var(--line)}
.tpl-list{margin-top:8px;border:.5px solid var(--line);border-radius:var(--radius);overflow:hidden}
.tpl-list button{display:block;width:100%;text-align:left;border:none;border-bottom:.5px solid var(--line);border-radius:0;height:auto;padding:10px 12px;font-size:14px}
.tpl-list button:last-child{border-bottom:none}
.tpl-list small{display:block;color:var(--muted);font-size:12px}
.gallery-title{font-size:13px;color:var(--muted);margin:22px 0 8px}
.vid{border:.5px solid var(--line);border-radius:var(--radius);overflow:hidden;background:var(--card)}
.vid .media{aspect-ratio:9/16;max-height:260px;width:100%;background:var(--soft);display:flex;align-items:center;justify-content:center;flex-direction:column;gap:6px;color:var(--muted);font-size:12px;text-align:center;padding:0}
.vid.wide .media{aspect-ratio:16/9}
.vid.square .media{aspect-ratio:1/1}
.vid video{width:100%;height:100%;object-fit:cover;display:block;background:#000}
.vid .foot{padding:6px 8px;font-size:12px;display:flex;justify-content:space-between;align-items:center;gap:6px}
.vid .foot span{overflow:hidden;white-space:nowrap;text-overflow:ellipsis}
.vid .foot a{color:var(--text);font-size:17px;text-decoration:none}
.vid .sub{padding:0 8px 6px;font-size:11px;color:var(--faint)}
.vid .fail{color:var(--danger);padding:0 10px}
.spin{animation:spin 1s linear infinite;font-size:22px}
@keyframes spin{to{transform:rotate(360deg)}}
.empty{font-size:13px;color:var(--faint);grid-column:1/-1;padding:8px 0}
.auth{max-width:380px;margin:12vh auto 0}
.auth h1{font-size:20px;margin:0 0 4px}
.auth p{color:var(--muted);font-size:14px;margin:0 0 16px}
.auth label{display:block;font-size:13px;color:var(--muted);margin:12px 0 6px}
.auth a{color:var(--accent)}
.hide{display:none!important}
</style>
</head>
<body>
<div class="wrap">
<?php if ($view === 'setup'): ?>
  <form class="card auth" method="post">
    <h1>Set up AI Video Studio</h1>
    <p>One-time setup. Your key stays on this server.</p>
    <?php if ($cfg['password_hash'] === ''): ?>
    <label for="pw">Choose a login password</label>
    <input id="pw" type="password" name="password" minlength="6" required autocomplete="new-password">
    <?php else: ?><input type="hidden" name="password" value=""><?php endif; ?>
    <label for="fk">fal.ai API key</label>
    <input id="fk" type="text" name="fal_key" required autocomplete="off" placeholder="Paste key from fal.ai/dashboard/keys">
    <?php if ($msg): ?><p class="err"><?= h($msg) ?></p><?php endif; ?>
    <button class="primary mt" type="submit" name="setup" value="1">Save and start</button>
  </form>
<?php elseif ($view === 'login'): ?>
  <form class="card auth" method="post">
    <h1>AI Video Studio</h1>
    <p>Enter your password to continue.</p>
    <label for="pw">Password</label>
    <input id="pw" type="password" name="password" required autofocus autocomplete="current-password">
    <?php if ($msg): ?><p class="err"><?= h($msg) ?></p><?php endif; ?>
    <button class="primary mt" type="submit" name="login" value="1">Log in</button>
  </form>
<?php elseif ($view === 'settings'): ?>
  <form class="card auth" method="post">
    <h1>Settings</h1>
    <p>Leave a field empty to keep it as it is.</p>
    <label for="fk">fal.ai API key (current: <?= h($keyHint) ?>)</label>
    <input id="fk" type="text" name="fal_key" autocomplete="off">
    <label for="rate">Dollar to rupee rate for cost estimates</label>
    <input id="rate" type="number" step="0.01" name="inr_rate" value="<?= h($cfg['inr_rate']) ?>">
    <label for="npw">New password</label>
    <input id="npw" type="password" name="new_password" autocomplete="new-password">
    <?php if ($msg): ?><p class="err"><?= h($msg) ?></p><?php endif; ?>
    <button class="primary mt" type="submit" name="settings" value="1">Save</button>
    <p class="mt"><a href="./">Back</a> · <a href="?logout=1">Log out</a></p>
  </form>
<?php else: ?>
  <div class="card">
    <div class="top">
      <div class="brand"><i class="ti ti-movie" aria-hidden="true"></i>AI Video Studio</div>
      <div class="top-right">
        <span class="spent" id="spent">Spent ₹0</span>
        <a class="icon-btn" href="?settings=1" aria-label="Settings"><i class="ti ti-settings"></i></a>
      </div>
    </div>

    <p class="label">Describe your ad</p>
    <textarea id="prompt" placeholder="A car on a highway at night, phone screen shows live GPS location, text &quot;BharatGPS – Track anytime&quot;"></textarea>

    <div class="row2" style="margin-top:10px">
      <button type="button" id="photoBtn"><i class="ti ti-photo" aria-hidden="true"></i>Add product photo</button>
      <button type="button" id="tplBtn"><i class="ti ti-template" aria-hidden="true"></i>Templates</button>
    </div>
    <input type="file" id="photoInput" accept="image/jpeg,image/png,image/webp" class="hide">
    <div class="photo hide" id="photoPrev"><img id="photoImg" alt="Selected product photo"><span>Photo added — the video starts from it.</span><button type="button" id="photoX" aria-label="Remove photo" style="height:32px;padding:0 8px"><i class="ti ti-x"></i></button></div>
    <div class="tpl-list hide" id="tplList"></div>

    <p class="label mt">Model</p>
    <div class="row2 models" id="models"></div>

    <div class="row2 mt">
      <div><p class="label">Format</p><select id="aspect"></select></div>
      <div><p class="label">Length</p><select id="length"></select></div>
    </div>

    <label class="check"><input type="checkbox" id="audio" checked> Add sound and voice</label>

    <button class="primary" id="genBtn" type="button">Generate video</button>
    <p class="err" id="err"></p>

    <p class="gallery-title">Your videos</p>
    <div class="row2" id="gallery"><div class="empty">Your generated videos will appear here.</div></div>
  </div>
<?php endif; ?>
</div>

<?php if ($view === 'app'): ?>
<script>
var MODELS = <?= json_encode($modelsForJs) ?>;
var RATE = <?= json_encode((float) $cfg['inr_rate']) ?>;
var ASPECT_LABEL = {'9:16': '9:16 Reels', '16:9': '16:9 YouTube', '1:1': '1:1 Feed'};
var TEMPLATES = [
  {t: 'BharatGPS', p: 'A car driving on a highway at night, close-up of a phone screen showing the car live on a GPS map, calm confident voiceover: "BharatGPS – track your vehicle anytime, anywhere." Cinematic lighting.'},
  {t: 'ScanPlay', p: 'A person points a phone at a printed wedding photo on a table and the photo comes alive as a video on the phone screen, family smiling, warm lighting, text "ScanPlay – photos that play".'},
  {t: 'HamaraService', p: 'A friendly technician in uniform arrives at an Indian home door, fixes an AC with a smile, happy family, bright daylight, text "HamaraService – trusted help at home".'},
  {t: 'HamaraStaff', p: 'A business owner in an office watches field staff moving as live dots on a map on a laptop, staff on bikes in the city, text "HamaraStaff – know where your team is".'},
  {t: 'Product showcase', p: 'Slow 360-degree rotation of the product on a clean studio background, soft shadows, premium lighting, subtle camera push-in.'}
];

var state = {model: 'kling3', aspect: '9:16', length: 8, audio: true, image: null, busy: false};
var jobs = [];
var videoCache = {};
var pollTimer = null;
var $ = function (id) { return document.getElementById(id); };

function inr(usd) { return Math.round(usd * RATE); }
function cost(modelKey, len, audio) { return MODELS[modelKey].price[audio ? 1 : 0] * len; }

function renderModels() {
  var box = $('models');
  box.innerHTML = '';
  Object.keys(MODELS).forEach(function (k) {
    var m = MODELS[k];
    var len = m.lengths.indexOf(state.length) >= 0 ? state.length : m.lengths[m.lengths.length - 1];
    var b = document.createElement('button');
    b.type = 'button';
    b.className = 'm' + (k === state.model ? ' sel' : '');
    b.innerHTML = '<div class="n"></div><div class="p"></div>';
    b.querySelector('.n').textContent = m.name;
    b.querySelector('.p').textContent = '~₹' + inr(cost(k, len, state.audio)) + ' / ' + len + 's';
    b.onclick = function () { state.model = k; syncOptions(); render(); };
    box.appendChild(b);
  });
}

function syncOptions() {
  var m = MODELS[state.model];
  if (m.aspects.indexOf(state.aspect) < 0) state.aspect = m.aspects[0];
  if (m.lengths.indexOf(state.length) < 0) state.length = m.lengths[m.lengths.length - 1];
  var a = $('aspect');
  a.innerHTML = '';
  m.aspects.forEach(function (x) { var o = new Option(ASPECT_LABEL[x], x); a.add(o); });
  a.value = state.aspect;
  var l = $('length');
  l.innerHTML = '';
  m.lengths.forEach(function (x) { l.add(new Option(x + ' seconds', x)); });
  l.value = String(state.length);
}

function render() {
  renderModels();
  $('genBtn').textContent = state.busy ? 'Starting…' : 'Generate video · ~₹' + inr(cost(state.model, state.length, state.audio));
  $('genBtn').disabled = state.busy;
}

$('aspect').onchange = function () { state.aspect = this.value; render(); };
$('length').onchange = function () { state.length = parseInt(this.value, 10); render(); };
$('audio').onchange = function () { state.audio = this.checked; render(); };
$('prompt').oninput = function () { $('err').textContent = ''; };

$('tplBtn').onclick = function () { $('tplList').classList.toggle('hide'); };
(function () {
  var list = $('tplList');
  TEMPLATES.forEach(function (t) {
    var b = document.createElement('button');
    b.type = 'button';
    b.innerHTML = '<span></span><small></small>';
    b.querySelector('span').textContent = t.t;
    b.querySelector('small').textContent = t.p.slice(0, 70) + '…';
    b.onclick = function () { $('prompt').value = t.p; list.classList.add('hide'); $('err').textContent = ''; };
    list.appendChild(b);
  });
})();

$('photoBtn').onclick = function () { $('photoInput').click(); };
$('photoX').onclick = function () { state.image = null; $('photoPrev').classList.add('hide'); $('photoInput').value = ''; };
$('photoInput').onchange = function () {
  var f = this.files[0];
  if (!f) return;
  var reader = new FileReader();
  reader.onload = function () {
    var img = new Image();
    img.onload = function () {
      var max = 1920, w = img.width, h = img.height, s = Math.min(1, max / Math.max(w, h));
      var c = document.createElement('canvas');
      c.width = Math.round(w * s); c.height = Math.round(h * s);
      c.getContext('2d').drawImage(img, 0, 0, c.width, c.height);
      state.image = c.toDataURL('image/jpeg', 0.9);
      $('photoImg').src = state.image;
      $('photoPrev').classList.remove('hide');
    };
    img.onerror = function () { $('err').textContent = 'That file could not be read as a photo.'; };
    img.src = reader.result;
  };
  reader.readAsDataURL(f);
};

function api(action, body) {
  var opt = {method: body ? 'POST' : 'GET', headers: {'X-AIVS': '1'}, credentials: 'same-origin'};
  if (body) { opt.headers['Content-Type'] = 'application/json'; opt.body = JSON.stringify(body); }
  return fetch('api.php?action=' + action, opt).then(function (r) {
    if (r.status === 401) { location.reload(); throw new Error('Session expired'); }
    return r.json();
  });
}

$('genBtn').onclick = function () {
  var prompt = $('prompt').value.trim();
  if (!prompt) { $('err').textContent = 'Describe your ad first.'; $('prompt').focus(); return; }
  state.busy = true; render();
  api('generate', {prompt: prompt, model: state.model, aspect: state.aspect, length: state.length, audio: state.audio, image: state.image})
    .then(function (res) {
      if (res.error) { $('err').textContent = res.error; return; }
      jobs.unshift(res.job);
      renderGallery();
      schedulePoll();
    })
    .catch(function (e) { $('err').textContent = 'Could not reach the server. Check your connection and try again.'; })
    .then(function () { state.busy = false; render(); });
};

function fmtElapsed(sec) { var m = Math.floor(sec / 60), s = sec % 60; return m + ':' + (s < 10 ? '0' : '') + s; }
function title(p) { return p.length > 28 ? p.slice(0, 28) + '…' : p; }

function renderGallery() {
  var g = $('gallery');
  g.innerHTML = '';
  if (!jobs.length) { g.innerHTML = '<div class="empty">Your generated videos will appear here.</div>'; return; }
  var now = Math.floor(Date.now() / 1000);
  jobs.forEach(function (j) {
    var d = document.createElement('div');
    d.className = 'vid' + (j.aspect === '16:9' ? ' wide' : j.aspect === '1:1' ? ' square' : '');
    var media = document.createElement('div');
    media.className = 'media';
    if (j.status === 'done') {
      var v = videoCache[j.id];
      if (!v) {
        v = document.createElement('video');
        v.src = j.video; v.controls = true; v.playsInline = true; v.preload = 'metadata';
        videoCache[j.id] = v;
      }
      media.appendChild(v);
    } else if (j.status === 'failed') {
      media.innerHTML = '<i class="ti ti-alert-triangle" style="font-size:22px" aria-hidden="true"></i><span class="fail"></span>';
      media.querySelector('.fail').textContent = j.error || 'Failed';
    } else {
      media.innerHTML = '<i class="ti ti-loader-2 spin" aria-hidden="true"></i><span class="elapsed"></span>';
      var el = media.querySelector('.elapsed');
      el.setAttribute('data-created', j.created);
      el.setAttribute('data-label', j.status === 'queued' ? 'In queue… ' : 'Generating… ');
      el.textContent = el.getAttribute('data-label') + fmtElapsed(Math.max(0, now - j.created));
    }
    d.appendChild(media);
    var foot = document.createElement('div');
    foot.className = 'foot';
    var t = document.createElement('span'); t.textContent = title(j.prompt); t.title = j.prompt;
    foot.appendChild(t);
    if (j.status === 'done') {
      var a = document.createElement('a');
      a.href = j.video; a.setAttribute('download', 'ad-' + j.id + '.mp4'); a.setAttribute('aria-label', 'Download');
      a.innerHTML = '<i class="ti ti-download"></i>';
      foot.appendChild(a);
    }
    d.appendChild(foot);
    var sub = document.createElement('div');
    sub.className = 'sub';
    sub.textContent = j.model_name + ' · ' + j.length + 's' + (j.status === 'done' ? ' · ~₹' + inr(j.cost_usd) : '');
    d.appendChild(sub);
    g.appendChild(d);
  });
}

function applyList(res) {
  if (!res || res.error) return;
  jobs = res.jobs || [];
  $('spent').textContent = 'Spent ₹' + Number(res.spent_inr || 0).toLocaleString('en-IN');
  renderGallery();
  schedulePoll();
}

function hasPending() { return jobs.some(function (j) { return j.status === 'queued' || j.status === 'running'; }); }

function schedulePoll() {
  clearTimeout(pollTimer);
  if (!hasPending()) return;
  pollTimer = setTimeout(function () {
    api('refresh').then(applyList).catch(function () { schedulePoll(); });
  }, 6000);
}

setInterval(function () {
  var now = Math.floor(Date.now() / 1000);
  Array.prototype.forEach.call(document.querySelectorAll('.elapsed'), function (el) {
    el.textContent = el.getAttribute('data-label') + fmtElapsed(Math.max(0, now - parseInt(el.getAttribute('data-created'), 10)));
  });
}, 1000);

syncOptions();
render();
api('list').then(applyList).catch(function () {});
</script>
<?php endif; ?>
</body>
</html>
