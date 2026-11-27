<?php header('X-Frame-Options: DENY'); header('Referrer-Policy: same-origin'); ?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Student Tracker</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
<style>
:root{--accent:#1F4FD1;--accent-dark:#163A9C;--accent-soft:#E6ECFB;--bg:#F6F6F3;--card:#fff;--line:#E4E4DF;--line2:#C9C9C3;--text:#1A1A1A;--muted:#5A5F66;--good:#127A3E;--good-soft:#E2F3E8;--warn:#8A5A00;--warn-soft:#FFF1D6;--bad:#A01225}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--text);font:15px/1.45 Inter,system-ui,sans-serif}
h1{margin:0;font-size:24px;font-weight:700}
h2{margin:0;font-size:17px;font-weight:700}
button,input,select,textarea{font:inherit;color:var(--text)}
button{cursor:pointer}
.hide{display:none!important}
.btn{min-height:42px;padding:0 16px;border-radius:8px;border:1px solid var(--line2);background:#fff;font-weight:600;white-space:nowrap}
.btn-p{background:var(--accent);border-color:var(--accent);color:#fff}
.btn-d{color:var(--bad);border-color:#E7B7BE}
.btn-sm{min-height:32px;padding:0 10px;font-size:13px}
.field{display:flex;flex-direction:column;gap:5px;flex:1 1 180px;min-width:0}
.field label{font-size:13px;font-weight:600;color:var(--muted)}
.field input,.field select,.field textarea{min-height:42px;padding:8px 11px;border-radius:8px;border:1px solid var(--line2);background:#fff;width:100%}
.field textarea{min-height:70px;resize:vertical}
.grid{display:flex;flex-wrap:wrap;gap:12px}
.hint{font-size:13px;color:var(--muted)}
.err{font-size:14px;color:var(--bad);font-weight:600;min-height:18px}
.card{background:var(--card);border:1px solid var(--line);border-radius:12px;padding:20px;display:flex;flex-direction:column;gap:14px;min-width:0}
.auth{max-width:420px;margin:8vh auto;padding:0 16px}
.shell{display:flex;flex-wrap:wrap;min-height:100vh}
.side{flex:1 1 200px;background:#fff;border-right:1px solid var(--line);padding:22px 14px;display:flex;flex-direction:column;gap:4px}
.brand{padding:0 8px 18px}
.brand b{display:block;font-size:16px}
.brand span{font-size:12px;color:var(--muted)}
.nav button{display:block;width:100%;text-align:left;padding:10px 12px;border-radius:8px;border:none;background:transparent;font-weight:500}
.nav button.on{background:var(--accent-soft);color:var(--accent-dark);font-weight:600}
.nav .count{float:right;background:var(--warn-soft);color:var(--warn);border-radius:999px;padding:0 8px;font-size:12px;font-weight:700}
.main{flex:999 1 560px;min-width:0;padding:26px 28px 40px;display:flex;flex-direction:column;gap:20px}
.head{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px}
.head .sub{color:var(--muted);font-size:14px;margin-top:3px}
.row{display:flex;flex-wrap:wrap;gap:16px}
.row>.card{flex:1 1 300px}
.stat{flex:1 1 150px;background:#fff;border:1px solid var(--line);border-radius:12px;padding:16px}
.stat b{display:block;font-size:28px;font-variant-numeric:tabular-nums}
.stat span{font-size:13px;color:var(--muted)}
.tablewrap{overflow-x:auto;border:1px solid var(--line);border-radius:12px;background:#fff}
table{border-collapse:collapse;width:100%;min-width:720px}
th,td{text-align:left;padding:10px 12px;border-bottom:1px solid var(--line);font-size:14px;vertical-align:top}
th{font-size:12px;color:var(--muted);font-weight:600;background:#FAFAF8;cursor:pointer;user-select:none;white-space:nowrap}
tr.click{cursor:pointer}
tr.click:hover td{background:#FAFAF8}
td.num,th.num{text-align:right;font-variant-numeric:tabular-nums}
.pill{display:inline-block;padding:1px 8px;border-radius:999px;font-size:12px;font-weight:600;background:#EEEEEA;color:var(--muted)}
.pill.active{background:var(--good-soft);color:var(--good)}
.pill.pending{background:var(--warn-soft);color:var(--warn)}
.pill.star{background:var(--accent-soft);color:var(--accent-dark)}
.up{color:var(--good)} .down{color:var(--bad)}
.filters{display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end}
.filters .field{flex:1 1 150px}
.kv{display:grid;grid-template-columns:minmax(110px,max-content) 1fr;gap:6px 16px;font-size:14px}
.kv dt{color:var(--muted)} .kv dd{margin:0;overflow-wrap:anywhere}
.chart{width:100%;height:auto;display:block}
.empty{color:var(--muted);text-align:center;padding:24px}
.big{font-size:40px;font-weight:700;line-height:1;font-variant-numeric:tabular-nums}
.notice{background:var(--warn-soft);color:var(--warn);border-radius:8px;padding:10px 12px;font-size:14px}
@media (max-width:640px){.main{padding:18px 16px 32px}.side{border-right:none;border-bottom:1px solid var(--line)}.nav{display:flex;flex-wrap:wrap;gap:4px}.nav button{width:auto}}
</style>
</head>
<body>

<div id="auth" class="auth hide">
  <div class="card">
    <h1 id="authTitle">Log in</h1>
    <p class="hint" id="authHint"></p>
    <div class="field hide" id="orgWrap"><label>Organisation name</label><input id="orgName" placeholder="e.g. Bright Futures Academy"></div>
    <div class="field"><label>Admin password</label><input id="pw" type="password" autocomplete="current-password"></div>
    <div class="err" id="authErr"></div>
    <button class="btn btn-p" id="authBtn">Log in</button>
  </div>
</div>

<div id="app" class="shell hide">
  <aside class="side">
    <div class="brand"><b id="orgTitle">Student Tracker</b><span>Education &amp; performance tracking</span></div>
    <nav class="nav">
      <button data-page="dash" class="on">Overview</button>
      <button data-page="list">All students</button>
      <button data-page="pending">New sign-ups <span class="count hide" id="pendCount"></span></button>
      <button data-page="best">Best candidates</button>
      <button data-page="settings">Settings</button>
      <button id="logout">Log out</button>
    </nav>
  </aside>
  <main class="main" id="main"></main>
</div>

<script>
const $ = s => document.querySelector(s);
const esc = v => String(v ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
let DATA = {students: [], settings: {}}, PAGE = 'dash', SORT = {key: 'score', dir: -1};

async function api(action, body, query = '') {
  const opt = {};
  if (body) { opt.method = 'POST'; opt.headers = {'X-Stu': '1'}; opt.body = new FormData(); for (const [k, v] of Object.entries(body)) opt.body.append(k, v ?? ''); }
  const r = await fetch('api.php?action=' + action + query, opt);
  const j = await r.json().catch(() => ({error: 'Server error.'}));
  if (r.status === 401 && action !== 'login') { showAuth(false); throw new Error(j.error); }
  if (j.error) throw new Error(j.error);
  return j;
}

// ---------- login ----------
let SETUP = false;
function showAuth(needsSetup) {
  SETUP = needsSetup;
  $('#app').classList.add('hide'); $('#auth').classList.remove('hide');
  $('#authTitle').textContent = needsSetup ? 'Set up Student Tracker' : 'Log in';
  $('#authHint').textContent = needsSetup ? 'Choose the admin password. Only someone with this password can see student data.' : '';
  $('#orgWrap').classList.toggle('hide', !needsSetup);
  $('#authBtn').textContent = needsSetup ? 'Create and open' : 'Log in';
  $('#pw').autocomplete = needsSetup ? 'new-password' : 'current-password';
}
$('#authBtn').onclick = async () => {
  $('#authErr').textContent = '';
  try {
    await api(SETUP ? 'setup' : 'login', {password: $('#pw').value, org_name: $('#orgName').value});
    $('#pw').value = ''; start();
  } catch (e) { $('#authErr').textContent = e.message; }
};
$('#pw').onkeydown = e => { if (e.key === 'Enter') $('#authBtn').click(); };
$('#logout').onclick = async () => { await api('logout', {}); showAuth(false); };

async function start() {
  const st = await (await fetch('api.php?action=state')).json();
  if (!st.setup) return showAuth(true);
  if (!st.logged_in) return showAuth(false);
  $('#auth').classList.add('hide'); $('#app').classList.remove('hide');
  await reload(); go(PAGE);
}
async function reload() {
  DATA = await api('list');
  $('#orgTitle').textContent = DATA.settings.org_name;
  document.title = DATA.settings.org_name + ' | Student Tracker';
  const p = DATA.students.filter(s => s.status === 'pending').length;
  $('#pendCount').textContent = p; $('#pendCount').classList.toggle('hide', !p);
}

document.querySelectorAll('.nav [data-page]').forEach(b => b.onclick = () => go(b.dataset.page));
function go(page, arg) {
  PAGE = page;
  document.querySelectorAll('.nav [data-page]').forEach(b => b.classList.toggle('on', b.dataset.page === page));
  ({dash, list: () => listPage('list'), pending: () => listPage('pending'), best: () => listPage('best'), settings: settingsPage, student: studentPage, edit: editPage})[page](arg);
  window.scrollTo(0, 0);
}

// ---------- helpers ----------
const fmtScore = v => v == null ? '–' : v.toFixed(1);
const fmtTrend = v => v == null ? '' : `<span class="${v >= 0 ? 'up' : 'down'}">${v >= 0 ? '▲' : '▼'} ${Math.abs(v).toFixed(1)}</span>`;
const fmtDate = d => d ? new Date(d + 'T00:00').toLocaleDateString('en-IN', {day: 'numeric', month: 'short', year: 'numeric'}) : '–';
const statusPill = s => `<span class="pill ${esc(s.status)}">${esc(s.status)}</span>` + (s.shortlisted ? ' <span class="pill star">shortlisted</span>' : '');

function table(rows, opts = {}) {
  if (!rows.length) return `<div class="tablewrap"><div class="empty">${opts.empty || 'No students yet.'}</div></div>`;
  const cols = [['name', 'Name'], ['course', 'Course / class'], ['institution', 'Institution'], ['status', 'Status'], ['record_count', 'Records', 1], ['score', 'Score', 1], ['trend', 'Trend', 1], ['track_ends', 'Tracking until']];
  const head = cols.map(([k, t, n]) => `<th data-k="${k}" class="${n ? 'num' : ''}">${t}${SORT.key === k ? (SORT.dir < 0 ? ' ↓' : ' ↑') : ''}</th>`).join('');
  const body = rows.map(s => `<tr class="click" data-id="${esc(s.id)}">
    <td><b>${esc(s.name)}</b><div class="hint">${esc(s.phone)}${s.age != null ? ' · ' + s.age + ' yrs' : ''}</div></td>
    <td>${esc(s.course)}${s.current_year ? '<div class="hint">' + esc(s.current_year) + '</div>' : ''}</td>
    <td>${esc(s.institution)}${s.city ? '<div class="hint">' + esc(s.city) + '</div>' : ''}</td>
    <td>${statusPill(s)}</td><td class="num">${s.record_count}</td>
    <td class="num"><b>${fmtScore(s.score)}</b></td><td class="num">${fmtTrend(s.trend)}</td>
    <td>${fmtDate(s.track_ends)}${s.track_over ? ' <span class="pill">ended</span>' : ''}</td></tr>`).join('');
  return `<div class="tablewrap"><table><thead><tr>${head}</tr></thead><tbody>${body}</tbody></table></div>`;
}
function wireTable(root, redraw) {
  root.querySelectorAll('th[data-k]').forEach(th => th.onclick = () => {
    SORT = {key: th.dataset.k, dir: SORT.key === th.dataset.k ? -SORT.dir : (['score', 'trend', 'record_count'].includes(th.dataset.k) ? -1 : 1)};
    redraw();
  });
  root.querySelectorAll('tr[data-id]').forEach(tr => tr.onclick = () => go('student', tr.dataset.id));
}
function sortRows(rows) {
  const {key, dir} = SORT;
  return rows.slice().sort((a, b) => {
    const x = a[key], y = b[key];
    if (x == null && y == null) return 0; if (x == null) return 1; if (y == null) return -1;
    return (typeof x === 'number' ? x - y : String(x).localeCompare(String(y))) * dir;
  });
}

// ---------- overview ----------
function dash() {
  const S = DATA.students, act = S.filter(s => s.status === 'active');
  const scored = act.filter(s => s.score != null);
  const avg = scored.length ? scored.reduce((a, s) => a + s.score, 0) / scored.length : null;
  const due = act.filter(s => !s.record_count);
  const top = scored.slice().sort((a, b) => b.score - a.score).slice(0, 5);
  $('#main').innerHTML = `
    <div class="head"><div><h1>Overview</h1><div class="sub">Every student is followed for ${DATA.settings.track_years} years from the day they join.</div></div>
      <div class="grid"><button class="btn" id="exp">Download spreadsheet</button><button class="btn btn-p" id="add">Add student</button></div></div>
    <div class="row">
      <div class="stat"><b>${act.length}</b><span>Active students</span></div>
      <div class="stat"><b>${S.filter(s => s.status === 'pending').length}</b><span>New sign-ups to approve</span></div>
      <div class="stat"><b>${S.filter(s => s.shortlisted).length}</b><span>Shortlisted</span></div>
      <div class="stat"><b>${fmtScore(avg)}</b><span>Average score</span></div>
    </div>
    <div class="row">
      <div class="card"><h2>Top 5 right now</h2>${top.length ? top.map((s, i) => `<div class="grid" style="justify-content:space-between;align-items:center;cursor:pointer" data-id="${esc(s.id)}"><div><b>${i + 1}. ${esc(s.name)}</b><div class="hint">${esc(s.course || s.institution)}</div></div><div><b>${fmtScore(s.score)}</b> ${fmtTrend(s.trend)}</div></div>`).join('') : '<div class="hint">Add performance records to see rankings.</div>'}</div>
      <div class="card"><h2>No records yet</h2>${due.length ? due.slice(0, 8).map(s => `<div style="cursor:pointer" data-id="${esc(s.id)}"><b>${esc(s.name)}</b> <span class="hint">joined ${fmtDate(s.joined)}</span></div>`).join('') + (due.length > 8 ? `<div class="hint">and ${due.length - 8} more</div>` : '') : '<div class="hint">Every active student has at least one record.</div>'}</div>
    </div>`;
  $('#main').querySelectorAll('[data-id]').forEach(el => el.onclick = () => go('student', el.dataset.id));
  $('#add').onclick = () => go('edit');
  $('#exp').onclick = () => location.href = 'api.php?action=export';
}

// ---------- lists ----------
const F = {q: '', course: '', status: '', min: ''};
function listPage(kind) {
  const title = {list: 'All students', pending: 'New sign-ups', best: 'Best candidates'}[kind];
  const sub = {list: 'Search, filter and open any student.', pending: 'Students who filled in the public form. Open one to check the details, then mark them Active.',
    best: 'Active students ranked by score. Use the filters to find the right person for an opportunity.'}[kind];
  if (kind === 'best') SORT = {key: 'score', dir: -1};
  const courses = [...new Set(DATA.students.map(s => s.course).filter(Boolean))].sort();
  $('#main').innerHTML = `
    <div class="head"><div><h1>${title}</h1><div class="sub">${sub}</div></div>${kind === 'list' ? '<button class="btn btn-p" id="add">Add student</button>' : ''}</div>
    ${kind === 'pending' ? `<div class="notice">Share this sign-up link with students: <b>${esc(location.origin + location.pathname.replace(/[^/]*$/, ''))}register.php</b>${DATA.settings.public_form ? '' : ' (closed now, open it in Settings)'}</div>` : ''}
    <div class="filters">
      <div class="field"><label>Search</label><input id="fq" placeholder="Name, phone, institution, interests" value="${esc(F.q)}"></div>
      <div class="field"><label>Course</label><select id="fc"><option value="">All</option>${courses.map(c => `<option ${c === F.course ? 'selected' : ''}>${esc(c)}</option>`).join('')}</select></div>
      ${kind === 'list' ? `<div class="field"><label>Status</label><select id="fs"><option value="">All</option>${['active', 'pending', 'completed', 'withdrawn'].map(s => `<option ${s === F.status ? 'selected' : ''}>${s}</option>`).join('')}</select></div>` : ''}
      ${kind !== 'pending' ? `<div class="field"><label>Minimum score</label><input id="fm" type="number" min="0" max="100" value="${esc(F.min)}"></div>` : ''}
    </div>
    <div id="tbl"></div>`;
  const draw = () => {
    const q = F.q.toLowerCase();
    let rows = DATA.students.filter(s => {
      if (kind === 'pending' && s.status !== 'pending') return false;
      if (kind === 'best' && s.status !== 'active') return false;
      if (kind === 'list' && F.status && s.status !== F.status) return false;
      if (F.course && s.course !== F.course) return false;
      if (kind !== 'pending' && F.min !== '' && (s.score ?? -1) < +F.min) return false;
      return !q || [s.name, s.phone, s.institution, s.course, s.stream, s.city].join(' ').toLowerCase().includes(q);
    });
    if (kind === 'best') rows = rows.filter(s => s.score != null);
    $('#tbl').innerHTML = table(sortRows(rows), {empty: kind === 'pending' ? 'No new sign-ups.' : 'No students match.'});
    wireTable($('#tbl'), draw);
  };
  const bind = (id, k) => { const el = $(id); if (el) el.oninput = () => { F[k] = el.value; draw(); }; };
  bind('#fq', 'q'); bind('#fc', 'course'); bind('#fs', 'status'); bind('#fm', 'min');
  if ($('#add')) $('#add').onclick = () => go('edit');
  draw();
}

// ---------- one student ----------
function chart(recs) {
  const pts = recs.filter(r => r.score != null);
  if (pts.length < 2) return '<div class="hint">The score chart appears once there are two or more records.</div>';
  const W = 600, H = 200, P = 28, step = (W - 2 * P) / (pts.length - 1);
  const y = v => H - P - (v / 100) * (H - 2 * P);
  const line = (key, scale = 1) => pts.map((r, i) => r[key] == null ? null : [P + i * step, y(r[key] * scale)]).filter(Boolean);
  const path = p => p.map((q, i) => (i ? 'L' : 'M') + q[0].toFixed(1) + ' ' + q[1].toFixed(1)).join(' ');
  const grid = [0, 25, 50, 75, 100].map(v => `<line x1="${P}" x2="${W - P}" y1="${y(v)}" y2="${y(v)}" stroke="#E4E4DF"/><text x="${P - 6}" y="${y(v) + 4}" font-size="10" text-anchor="end" fill="#5A5F66">${v}</text>`).join('');
  const sc = line('score');
  return `<svg class="chart" viewBox="0 0 ${W} ${H}" role="img" aria-label="Score over time">${grid}
    <path d="${path(line('marks'))}" fill="none" stroke="#9AA8C9" stroke-width="1.5" stroke-dasharray="4 3"/>
    <path d="${path(sc)}" fill="none" stroke="#1F4FD1" stroke-width="2.5"/>
    ${sc.map((q, i) => `<circle cx="${q[0]}" cy="${q[1]}" r="3.5" fill="#1F4FD1"><title>${esc(pts[i].period || pts[i].date)}: ${pts[i].score}</title></circle>`).join('')}
    ${pts.map((r, i) => `<text x="${P + i * step}" y="${H - 8}" font-size="10" text-anchor="middle" fill="#5A5F66">${esc((r.period || r.date).slice(0, 14))}</text>`).join('')}
  </svg><div class="hint"><b style="color:#1F4FD1">━</b> Score &nbsp; <b style="color:#9AA8C9">┅</b> Marks</div>`;
}

async function studentPage(id) {
  $('#main').innerHTML = '<div class="hint">Loading…</div>';
  const {student: s, summary: m} = await api('get', null, '&id=' + encodeURIComponent(id));
  const kv = (pairs) => '<dl class="kv">' + pairs.filter(([, v]) => v).map(([k, v]) => `<dt>${k}</dt><dd>${esc(v)}</dd>`).join('') + '</dl>';
  const recs = s.records.slice().reverse();
  $('#main').innerHTML = `
    <div class="head"><div><h1>${esc(s.name)}</h1><div class="sub">${statusPill(s)} · joined ${fmtDate(s.joined)} · tracked until ${fmtDate(m.track_ends)}${m.track_over ? ' (period over)' : ''}</div></div>
      <div class="grid"><button class="btn" id="back">Back</button><button class="btn" id="star">${s.shortlisted ? 'Remove from shortlist' : 'Shortlist'}</button>
      ${s.status === 'pending' ? '<button class="btn btn-p" id="approve">Approve</button>' : ''}<button class="btn" id="edit">Edit</button></div></div>
    ${m.minor && !s.consent_date ? '<div class="notice">This student is under 18. Record the parent or guardian\'s consent (Edit → Consent) before keeping their data.</div>' : ''}
    <div class="row">
      <div class="card" style="flex:1 1 200px"><span class="hint">Overall score</span><div class="big">${fmtScore(m.score)}</div><div>${fmtTrend(m.trend)} <span class="hint">${m.trend != null ? 'since first record' : ''}</span></div><div class="hint">${m.record_count} record${m.record_count === 1 ? '' : 's'}</div></div>
      <div class="card" style="flex:3 1 400px"><h2>Progress</h2>${chart(s.records)}</div>
    </div>
    <div class="row">
      <div class="card"><h2>Personal</h2>${kv([['Date of birth', s.dob ? fmtDate(s.dob) + (m.age != null ? ` (${m.age} yrs)` : '') : ''], ['Gender', s.gender], ['Phone', s.phone], ['Email', s.email], ['Address', [s.address, s.city].filter(Boolean).join(', ')], ['Guardian', [s.guardian_name, s.guardian_relation, s.guardian_phone].filter(Boolean).join(' · ')]])}</div>
      <div class="card"><h2>Education</h2>${kv([['Institution', s.institution], ['Course', s.course], ['Stream / subjects', s.stream], ['Current year', s.current_year], ['Roll no.', s.roll_no], ['Interests', s.interests], ['Career goal', s.goal], ['Notes', s.notes]])}
        <div class="hint">Consent: ${s.consent_date ? fmtDate(s.consent_date) + ' · ' + esc(s.consent_by) : '<b style="color:var(--bad)">not recorded</b>'}</div></div>
    </div>
    <div class="card"><h2>Add a performance record</h2>
      <div class="grid">
        <div class="field"><label>Period</label><input id="r_period" placeholder="e.g. 2026 Term 1, Year 2 Sem 1"></div>
        <div class="field"><label>Date</label><input id="r_date" type="date" value="${new Date().toISOString().slice(0, 10)}"></div>
        <div class="field"><label>Class / semester then</label><input id="r_class" value="${esc(s.current_year)}"></div>
      </div>
      <div class="grid">
        <div class="field"><label>Marks %</label><input id="r_marks" type="number" min="0" max="100" step="0.1"></div>
        <div class="field"><label>Attendance %</label><input id="r_attendance" type="number" min="0" max="100" step="0.1"></div>
        <div class="field"><label>Skills (0–10, mentor's rating)</label><input id="r_skills" type="number" min="0" max="10" step="0.5"></div>
        <div class="field"><label>Rank in class</label><input id="r_rank"></div>
      </div>
      <div class="grid">
        <div class="field"><label>Achievements (sports, competitions, certificates)</label><textarea id="r_achievements"></textarea></div>
        <div class="field"><label>Remarks</label><textarea id="r_remarks"></textarea></div>
      </div>
      <div class="err" id="rErr"></div><div><button class="btn btn-p" id="addRec">Save record</button></div>
    </div>
    <div class="card"><h2>History</h2>
      ${recs.length ? `<div class="tablewrap"><table><thead><tr><th>Period</th><th>Date</th><th class="num">Marks</th><th class="num">Attend.</th><th class="num">Skills</th><th class="num">Score</th><th>Rank</th><th>Achievements / remarks</th><th></th></tr></thead><tbody>
        ${recs.map(r => `<tr><td><b>${esc(r.period)}</b><div class="hint">${esc(r.class)}</div></td><td>${fmtDate(r.date)}</td><td class="num">${r.marks ?? '–'}</td><td class="num">${r.attendance ?? '–'}</td><td class="num">${r.skills ?? '–'}</td><td class="num"><b>${fmtScore(r.score)}</b></td><td>${esc(r.rank)}</td><td>${esc(r.achievements)}${r.achievements && r.remarks ? '<br>' : ''}<span class="hint">${esc(r.remarks)}</span></td><td><button class="btn btn-sm btn-d" data-del="${esc(r.id)}">Delete</button></td></tr>`).join('')}
      </tbody></table></div>` : '<div class="hint">No records yet. Add the first one above.</div>'}
    </div>`;
  $('#back').onclick = () => go(PAGE_BEFORE || 'list');
  $('#edit').onclick = () => go('edit', s.id);
  $('#star').onclick = async () => { await api('save_student', {id: s.id, shortlisted: s.shortlisted ? '0' : '1'}); await reload(); go('student', s.id); };
  if ($('#approve')) $('#approve').onclick = async () => { await api('save_student', {id: s.id, status: 'active'}); await reload(); go('student', s.id); };
  $('#addRec').onclick = async () => {
    const b = {id: s.id}; ['period', 'date', 'class', 'marks', 'attendance', 'skills', 'rank', 'achievements', 'remarks'].forEach(k => b[k] = $('#r_' + k).value);
    try { await api('add_record', b); await reload(); go('student', s.id); } catch (e) { $('#rErr').textContent = e.message; }
  };
  $('#main').querySelectorAll('[data-del]').forEach(btn => btn.onclick = async () => {
    if (!confirm('Delete this record?')) return;
    await api('delete_record', {id: s.id, record_id: btn.dataset.del}); await reload(); go('student', s.id);
  });
}
let PAGE_BEFORE = 'list';
const _go = go; go = (p, a) => { if (!['student', 'edit'].includes(PAGE)) PAGE_BEFORE = PAGE; _go(p, a); };

// ---------- add / edit ----------
async function editPage(id) {
  const s = id ? (await api('get', null, '&id=' + encodeURIComponent(id))).student : {status: 'active', joined: new Date().toISOString().slice(0, 10)};
  const f = (k, label, type = 'text', extra = '') => `<div class="field"><label>${label}</label><input id="e_${k}" type="${type}" value="${esc(s[k])}" ${extra}></div>`;
  const ta = (k, label) => `<div class="field"><label>${label}</label><textarea id="e_${k}">${esc(s[k])}</textarea></div>`;
  const sel = (k, label, opts) => `<div class="field"><label>${label}</label><select id="e_${k}">${opts.map(o => `<option ${o === (s[k] || '') ? 'selected' : ''}>${o}</option>`).join('')}</select></div>`;
  $('#main').innerHTML = `
    <div class="head"><div><h1>${id ? 'Edit ' + esc(s.name) : 'Add student'}</h1></div><button class="btn" id="cancel">Cancel</button></div>
    <div class="card"><h2>Personal</h2>
      <div class="grid">${f('name', 'Full name *')}${f('dob', 'Date of birth', 'date')}${sel('gender', 'Gender', ['', 'Female', 'Male', 'Other'])}</div>
      <div class="grid">${f('phone', 'Phone', 'tel')}${f('email', 'Email', 'email')}${f('city', 'City / town')}</div>
      <div class="grid">${ta('address', 'Address')}</div>
      <div class="grid">${f('guardian_name', 'Parent / guardian name')}${f('guardian_relation', 'Relation')}${f('guardian_phone', 'Guardian phone', 'tel')}</div>
    </div>
    <div class="card"><h2>Education</h2>
      <div class="grid">${f('institution', 'School / college')}${f('course', 'Course / class (e.g. 10th, Inter MPC, B.Tech CSE)')}${f('stream', 'Stream / main subjects')}</div>
      <div class="grid">${f('current_year', 'Current year / semester')}${f('roll_no', 'Roll no.')}${f('joined', 'Joined tracking on', 'date')}</div>
      <div class="grid">${ta('interests', 'Interests and skills')}${ta('goal', 'Career goal')}</div>
      <div class="grid">${ta('notes', 'Private notes (only admins see these)')}</div>
    </div>
    <div class="card"><h2>Consent and status</h2>
      <p class="hint">Keep student data only with consent. For a student under 18 the consent must come from a parent or guardian.</p>
      <div class="grid">${f('consent_date', 'Consent given on', 'date')}${f('consent_by', 'Consent given by (name, and relation if guardian)')}${sel('status', 'Status', ['active', 'pending', 'completed', 'withdrawn'])}</div>
    </div>
    <div class="err" id="eErr"></div>
    <div class="grid" style="justify-content:space-between"><button class="btn btn-p" id="save">Save</button>${id ? '<button class="btn btn-d" id="del">Delete student and all their data</button>' : ''}</div>`;
  $('#cancel').onclick = () => id ? go('student', id) : go(PAGE_BEFORE);
  $('#save').onclick = async () => {
    const b = {id: id || ''};
    ['name', 'dob', 'gender', 'phone', 'email', 'city', 'address', 'guardian_name', 'guardian_relation', 'guardian_phone', 'institution', 'course', 'stream', 'current_year', 'roll_no', 'joined', 'interests', 'goal', 'notes', 'consent_date', 'consent_by', 'status'].forEach(k => b[k] = $('#e_' + k).value);
    try { const r = await api('save_student', b); await reload(); go('student', r.id); } catch (e) { $('#eErr').textContent = e.message; }
  };
  if ($('#del')) $('#del').onclick = async () => {
    if (!confirm(`Delete ${s.name} and every record? This cannot be undone.`)) return;
    await api('delete_student', {id}); await reload(); go('list');
  };
}

// ---------- settings ----------
function settingsPage() {
  const s = DATA.settings;
  $('#main').innerHTML = `
    <div class="head"><div><h1>Settings</h1></div></div>
    <div class="card"><h2>General</h2>
      <div class="grid">
        <div class="field"><label>Organisation name</label><input id="s_org" value="${esc(s.org_name)}"></div>
        <div class="field"><label>Track each student for</label><select id="s_years">${[3, 4, 5].map(y => `<option value="${y}" ${y == s.track_years ? 'selected' : ''}>${y} years</option>`).join('')}</select></div>
        <div class="field"><label>Public sign-up form</label><select id="s_pub"><option value="1" ${s.public_form ? 'selected' : ''}>Open</option><option value="0" ${s.public_form ? '' : 'selected'}>Closed</option></select></div>
      </div>
    </div>
    <div class="card"><h2>Welcome page</h2>
      <p class="hint">Shown at <b>${esc(location.origin + location.pathname.replace(/[^/]*$/, ''))}welcome</b>. Share this link with students and parents.</p>
      <div class="grid"><div class="field"><label>Motto (the big headline)</label><input id="s_motto" maxlength="120" value="${esc(s.motto)}"></div></div>
      <div class="grid"><div class="field"><label>Goal (one or two sentences under the headline)</label><textarea id="s_goal" maxlength="400">${esc(s.goal)}</textarea></div></div>
      <div><a class="btn" style="display:inline-flex;align-items:center;text-decoration:none" href="welcome.php" target="_blank">Open welcome page</a></div>
    </div>
    <div class="card"><h2>How the score is worked out</h2>
      <p class="hint">Each record gets a score out of 100 from these parts. The student's overall score is the average of their latest three records, with the newest counting most.</p>
      <div class="grid">
        <div class="field"><label>Marks weight %</label><input id="s_wm" type="number" min="0" max="100" value="${s.w_marks}"></div>
        <div class="field"><label>Attendance weight %</label><input id="s_wa" type="number" min="0" max="100" value="${s.w_attendance}"></div>
        <div class="field"><label>Skills weight %</label><input id="s_ws" type="number" min="0" max="100" value="${s.w_skills}"></div>
      </div>
      <div class="err" id="sErr"></div><div><button class="btn btn-p" id="sSave">Save settings</button></div>
    </div>
    <div class="card"><h2>Change password</h2>
      <div class="grid"><div class="field"><label>Current password</label><input id="p_old" type="password"></div><div class="field"><label>New password</label><input id="p_new" type="password" autocomplete="new-password"></div></div>
      <div class="err" id="pErr"></div><div><button class="btn" id="pSave">Change password</button></div>
    </div>`;
  $('#sSave').onclick = async () => {
    try { await api('save_settings', {org_name: $('#s_org').value, track_years: $('#s_years').value, public_form: $('#s_pub').value, w_marks: $('#s_wm').value, w_attendance: $('#s_wa').value, w_skills: $('#s_ws').value, motto: $('#s_motto').value, goal: $('#s_goal').value});
      await reload(); $('#sErr').style.color = 'var(--good)'; $('#sErr').textContent = 'Saved.'; } catch (e) { $('#sErr').style.color = ''; $('#sErr').textContent = e.message; }
  };
  $('#pSave').onclick = async () => {
    try { await api('change_password', {old: $('#p_old').value, new: $('#p_new').value}); $('#pErr').style.color = 'var(--good)'; $('#pErr').textContent = 'Password changed.'; $('#p_old').value = $('#p_new').value = ''; }
    catch (e) { $('#pErr').style.color = ''; $('#pErr').textContent = e.message; }
  };
}

start();
</script>
</body>
</html>
