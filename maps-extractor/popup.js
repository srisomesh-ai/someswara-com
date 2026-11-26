const $ = (id) => document.getElementById(id);
const MAPS_RE = /^https:\/\/((www|maps)\.)?google\.[a-z.]+\/maps|^https:\/\/maps\.google\.[a-z.]+\//;

let places = [];
let run = null;
let tab = null;

// ---------- data ----------

async function refresh() {
  const got = await chrome.storage.local.get(['places', 'run', 'opts']);
  places = Object.values(got.places || {}).sort((a, b) => (a.scrapedAt || 0) - (b.scrapedAt || 0));
  run = got.run || null;
  if (got.opts && !refresh.optsLoaded) {
    $('detailed').checked = got.opts.detailed !== false;
    if (got.opts.max) $('max').value = got.opts.max;
    refresh.optsLoaded = true;
  }
  renderRun();
  render();
}

function onMaps() {
  return !!(tab && tab.url && MAPS_RE.test(tab.url));
}

// ---------- run controls ----------

function renderRun() {
  const running = !!(run && run.running && Date.now() - run.updatedAt < 60000);
  const el = $('run-status');
  el.classList.toggle('err', !!(run && run.error));
  if (!onMaps() && !running) {
    el.textContent = 'Open Google Maps and search for businesses.';
  } else if (run && run.error) {
    el.textContent = run.error;
  } else if (running) {
    el.textContent = `${run.phase} · ${run.done} saved${run.query ? ` · "${run.query}"` : ''}`;
  } else if (run && run.phase) {
    el.textContent = run.phase + (run.query ? ` · "${run.query}"` : '');
  } else {
    el.textContent = 'Ready';
  }
  $('hint').hidden = !onMaps() || running || !!(run && run.phase);
  $('open-maps').hidden = onMaps() || running;
  $('start').hidden = !onMaps() || running;
  $('opts').hidden = !onMaps() || running;
  $('stop').hidden = !running;
}

async function start() {
  const opts = { detailed: $('detailed').checked, max: Number($('max').value) || 200 };
  await chrome.storage.local.set({ opts, run: { running: true, phase: 'Starting', done: 0, updatedAt: Date.now() } });
  try {
    await chrome.scripting.executeScript({ target: { tabId: tab.id }, files: ['scraper.js'] });
    const [res] = await chrome.scripting.executeScript({
      target: { tabId: tab.id },
      func: (o) => window.__gmx.start(o),
      args: [opts],
    });
    if (res && res.result && !res.result.ok && res.result.error !== 'Already running') {
      await chrome.storage.local.set({ run: { running: false, error: res.result.error, updatedAt: Date.now() } });
    }
  } catch (e) {
    await chrome.storage.local.set({ run: { running: false, error: 'Could not start: ' + e.message, updatedAt: Date.now() } });
  }
}

async function stop() {
  try {
    await chrome.scripting.executeScript({ target: { tabId: tab.id }, func: () => window.__gmx && window.__gmx.stop() });
  } catch (e) {
    await chrome.storage.local.set({ run: { ...run, running: false, phase: 'Stopped', updatedAt: Date.now() } });
  }
}

// ---------- list ----------

function rows() {
  let list = places;
  if ($('only-phone').checked) list = list.filter((p) => p.phone);
  const q = $('search').value.trim().toLowerCase();
  if (q) {
    const qd = q.replace(/\D/g, '');
    list = list.filter((p) =>
      [p.name, p.address, p.category, p.query, p.website].some((s) => s && s.toLowerCase().includes(q)) ||
      (qd.length >= 3 && p.phone && p.phone.replace(/\D/g, '').includes(qd)));
  }
  return list;
}

function render() {
  $('n-all').textContent = places.length;
  $('n-phone').textContent = places.filter((p) => p.phone).length;
  $('n-web').textContent = places.filter((p) => p.website).length;

  const list = rows();
  $('count').textContent = `${list.length} place${list.length === 1 ? '' : 's'}`;
  const ul = $('list');
  ul.textContent = '';
  const max = 300;
  for (const p of list.slice(-max).reverse()) {
    const li = document.createElement('li');
    const left = document.createElement('div');
    const name = document.createElement('span');
    name.className = 'name';
    name.textContent = p.name;
    const sub = document.createElement('span');
    sub.className = 'sub';
    sub.textContent = [p.category, p.rating && `★ ${p.rating}${p.reviews ? ` (${p.reviews})` : ''}`, p.address]
      .filter(Boolean).join(' · ');
    left.append(name, sub);
    const phone = document.createElement('span');
    phone.className = 'phone';
    phone.textContent = p.phone || '—';
    li.append(left, phone);
    ul.appendChild(li);
  }
  if (list.length > max) {
    const li = document.createElement('li');
    li.className = 'empty';
    li.textContent = `+ ${list.length - max} more (all included in export)`;
    ul.appendChild(li);
  }
  if (!list.length) {
    const li = document.createElement('li');
    li.className = 'empty';
    li.textContent = places.length ? 'Nothing matches.' : 'No places yet.';
    ul.appendChild(li);
  }
  for (const id of ['export-csv', 'export-vcf', 'copy']) $(id).disabled = !list.length;
  $('clear').disabled = !places.length;
}

// ---------- export ----------

function csvCell(v) {
  let s = v == null ? '' : String(v);
  if (/^[=@\t\r]/.test(s) || /^[+\-][^\d\s(]/.test(s)) s = "'" + s;
  return /[",\r\n]/.test(s) ? '"' + s.replace(/"/g, '""') + '"' : s;
}

function fileBase() {
  const queries = [...new Set(rows().map((p) => p.query).filter(Boolean))];
  const label = queries.length === 1 ? queries[0] : 'google-maps-places';
  const safe = label.replace(/[^\p{L}\p{N}]+/gu, '-').replace(/^-|-$/g, '').slice(0, 50) || 'places';
  return `${safe}-${new Date().toISOString().slice(0, 10)}`;
}

function download(name, text, type) {
  const url = URL.createObjectURL(new Blob([text], { type }));
  const a = document.createElement('a');
  a.href = url;
  a.download = name;
  document.body.appendChild(a);
  a.click();
  a.remove();
  setTimeout(() => URL.revokeObjectURL(url), 5000);
}

const COLUMNS = [
  ['Name', 'name'], ['Phone', 'phone'], ['Website', 'website'], ['Address', 'address'],
  ['Category', 'category'], ['Rating', 'rating'], ['Reviews', 'reviews'], ['Plus Code', 'plusCode'],
  ['Latitude', 'lat'], ['Longitude', 'lng'], ['Google Maps URL', 'url'], ['Search', 'query'],
];

function exportCsv() {
  const list = rows();
  const lines = [COLUMNS.map((c) => c[0]).join(',')];
  for (const p of list) lines.push(COLUMNS.map(([, k]) => csvCell(p[k])).join(','));
  download(fileBase() + '.csv', '\ufeff' + lines.join('\r\n'), 'text/csv;charset=utf-8');
  toast(`Exported ${list.length} places to CSV`);
}

function vcfEscape(s) {
  return String(s).replace(/\\/g, '\\\\').replace(/\n/g, '\\n').replace(/[,;]/g, (m) => '\\' + m);
}

function exportVcf() {
  const list = rows().filter((p) => p.phone);
  const cards = list.map((p) => [
    'BEGIN:VCARD', 'VERSION:3.0',
    'FN:' + vcfEscape(p.name),
    'N:;' + vcfEscape(p.name) + ';;;',
    'ORG:' + vcfEscape(p.name),
    'TEL;TYPE=WORK:' + p.phone.replace(/[^\d+]/g, ''),
    p.website ? 'URL:' + p.website : '',
    p.address ? 'ADR;TYPE=WORK:;;' + vcfEscape(p.address) + ';;;;' : '',
    p.category ? 'NOTE:' + vcfEscape(p.category) : '',
    'END:VCARD',
  ].filter(Boolean).join('\r\n'));
  download(fileBase() + '.vcf', cards.join('\r\n') + '\r\n', 'text/vcard;charset=utf-8');
  toast(`Exported ${list.length} places with phones to vCard`);
}

async function copyPhones() {
  const nums = [...new Set(rows().map((p) => p.phone).filter(Boolean))];
  await navigator.clipboard.writeText(nums.join('\n'));
  toast(`Copied ${nums.length} phone numbers`);
}

async function clearAll() {
  if (!confirm(`Delete all ${places.length} saved places?`)) return;
  await chrome.storage.local.set({ places: {}, run: null });
  toast('Cleared');
}

let toastTimer;
function toast(msg) {
  $('toast').textContent = msg;
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => ($('toast').textContent = ''), 3000);
}

// ---------- wiring ----------

$('start').addEventListener('click', start);
$('stop').addEventListener('click', stop);
$('open-maps').addEventListener('click', () => chrome.tabs.create({ url: 'https://www.google.com/maps' }));
$('search').addEventListener('input', render);
$('only-phone').addEventListener('change', render);
$('export-csv').addEventListener('click', exportCsv);
$('export-vcf').addEventListener('click', exportVcf);
$('copy').addEventListener('click', () => copyPhones().catch(() => toast('Copy failed')));
$('clear').addEventListener('click', clearAll);
chrome.storage.onChanged.addListener((changes, area) => {
  if (area === 'local' && (changes.places || changes.run)) refresh();
});

(async () => {
  [tab] = await chrome.tabs.query({ active: true, currentWindow: true });
  refresh();
})();
