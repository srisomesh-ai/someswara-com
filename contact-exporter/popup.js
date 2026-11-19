const WA_URL = 'https://web.whatsapp.com/';
const $ = (id) => document.getElementById(id);

let data = null; // { contacts, groups }
let byKey = new Map();
let source = 'all';
let tabId = null;

const prefs = (() => {
  try { return JSON.parse(localStorage.getItem('prefs') || '{}'); } catch (e) { return {}; }
})();
function savePrefs() {
  try {
    localStorage.setItem('prefs', JSON.stringify({ format: $('format').value, onlyPhone: $('only-phone').checked }));
  } catch (e) { /* ignore */ }
}

function show(view) {
  for (const v of ['open', 'busy', 'error', 'main']) $('view-' + v).hidden = v !== view;
  $('rescan').hidden = view !== 'main';
}

function fail(msg) {
  $('error-text').textContent = msg;
  show('error');
}

// ---------- scanning ----------

async function scan() {
  const [tab] = await chrome.tabs.query({ active: true, currentWindow: true });
  if (!tab || !tab.url || !tab.url.startsWith(WA_URL)) {
    show('open');
    return;
  }
  tabId = tab.id;
  show('busy');
  try {
    await chrome.scripting.executeScript({ target: { tabId }, files: ['extractor.js'] });
    const [res] = await chrome.scripting.executeScript({
      target: { tabId },
      func: () => window.__ccxExtract(),
    });
    const result = res && res.result;
    if (!result || !result.ok) return fail((result && result.error) || 'No data returned.');
    if (!result.contacts.length) {
      return fail('No contacts found yet. Wait until your chats have loaded in WhatsApp Web, then try again.');
    }
    load(result);
  } catch (e) {
    fail('Could not read this tab: ' + e.message);
  }
}

function load(result) {
  data = result;
  byKey = new Map(data.contacts.map((c) => [c.key, c]));
  const saved = data.contacts.filter((c) => c.saved).length;
  $('n-all').textContent = data.contacts.length;
  $('n-saved').textContent = saved;
  $('n-unsaved').textContent = data.contacts.length - saved;
  $('n-groups').textContent = data.groups.length;

  const sel = $('source');
  sel.textContent = '';
  const opt = (value, label, parent = sel) => {
    const o = document.createElement('option');
    o.value = value;
    o.textContent = label;
    parent.appendChild(o);
  };
  opt('all', 'All contacts');
  opt('saved', 'Saved contacts (in your phone)');
  opt('unsaved', 'Unsaved numbers');
  opt('chats', 'People you have chatted with');
  opt('business', 'Business accounts');
  if (data.groups.length) {
    opt('groups', 'Members of all groups');
    const og = document.createElement('optgroup');
    og.label = 'One group';
    for (const g of data.groups) opt('g:' + g.id, `${g.name} (${g.members.length})`, og);
    sel.appendChild(og);
  }
  setSource(data.groups.some((g) => 'g:' + g.id === source) || !source.startsWith('g:') ? source : 'all');
  show('main');
}

// ---------- filtering ----------

function displayName(c) {
  return c.name || c.businessName || c.pushname || '';
}

function currentGroup() {
  return source.startsWith('g:') ? data.groups.find((g) => 'g:' + g.id === source) : null;
}

function rows() {
  let list;
  const group = currentGroup();
  if (group) {
    list = group.members.map((m) => ({ ...byKey.get(m.key), role: m.role })).filter((c) => c.key);
  } else if (source === 'groups') {
    const keys = new Set(data.groups.flatMap((g) => g.members.map((m) => m.key)));
    list = data.contacts.filter((c) => keys.has(c.key));
  } else {
    const test = {
      all: () => true,
      saved: (c) => c.saved,
      unsaved: (c) => !c.saved,
      chats: (c) => c.hasChat,
      business: (c) => c.isBusiness,
    }[source] || (() => true);
    list = data.contacts.filter(test);
  }

  if ($('only-phone').checked) list = list.filter((c) => c.phone);

  const q = $('search').value.trim().toLowerCase();
  if (q) {
    const qd = q.replace(/\D/g, '');
    list = list.filter((c) =>
      [c.name, c.pushname, c.businessName].some((s) => s && s.toLowerCase().includes(q)) ||
      (qd && c.phone && c.phone.includes(qd)));
  }

  return list.sort((a, b) => {
    const an = displayName(a), bn = displayName(b);
    if (!an !== !bn) return an ? -1 : 1;
    return an.localeCompare(bn) || (a.phone || '').localeCompare(b.phone || '');
  });
}

function setSource(s) {
  source = s;
  $('source').value = s;
  document.querySelectorAll('.stat').forEach((b) => b.classList.toggle('active', b.dataset.source === s));
  render();
}

function render() {
  const list = rows();
  const ul = $('list');
  ul.textContent = '';
  $('count').textContent = `${list.length} contact${list.length === 1 ? '' : 's'}`;
  const max = 300;
  for (const c of list.slice(0, max)) {
    const li = document.createElement('li');
    const name = document.createElement('span');
    name.className = 'name';
    name.textContent = displayName(c) || 'No name';
    if (c.role === 'admin') name.appendChild(tag('admin'));
    if (!c.saved) name.appendChild(tag('unsaved'));
    const phone = document.createElement('span');
    phone.className = 'phone';
    phone.textContent = c.phone ? formatPhone(c.phone) : 'number hidden';
    li.append(name, phone);
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
    li.textContent = 'Nothing matches.';
    ul.appendChild(li);
  }
  for (const id of ['export-csv', 'export-vcf', 'copy']) $(id).disabled = !list.length;
}

function tag(text) {
  const t = document.createElement('span');
  t.className = 'tag';
  t.textContent = text;
  return t;
}

// ---------- formatting & export ----------

function formatPhone(digits) {
  if (!digits) return '';
  switch ($('format').value) {
    case 'digits': return digits;
    case 'spaced': {
      // Most countries use a 10-digit national number; split the rest off as the country code.
      if (digits.length >= 11 && digits.length <= 13) {
        const cc = digits.slice(0, digits.length - 10);
        const n = digits.slice(-10);
        if (cc === '1') return `+1 ${n.slice(0, 3)} ${n.slice(3, 6)} ${n.slice(6)}`;
        return `+${cc} ${n.slice(0, 5)} ${n.slice(5)}`;
      }
      return '+' + digits;
    }
    default: return '+' + digits;
  }
}

function csvCell(v) {
  let s = v == null ? '' : String(v);
  // Stop spreadsheet apps from treating names as formulas.
  if (/^[=@\t\r]/.test(s) || /^[+\-][^\d\s(]/.test(s)) s = "'" + s;
  return /[",\r\n]/.test(s) ? '"' + s.replace(/"/g, '""') + '"' : s;
}

function fileBase() {
  const g = currentGroup();
  const label = g ? g.name : $('source').selectedOptions[0].textContent.replace(/\s*\(.*\)$/, '');
  const safe = label.replace(/[^\p{L}\p{N}]+/gu, '-').replace(/^-|-$/g, '').slice(0, 40) || 'contacts';
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

function exportCsv() {
  const list = rows();
  const group = currentGroup();
  const head = ['Name', 'Saved Name', 'WhatsApp Name', 'Business Name', 'Phone', 'Saved', 'Business', group ? 'Role' : 'Groups'];
  const lines = [head.join(',')];
  for (const c of list) {
    lines.push([
      displayName(c), c.name, c.pushname, c.businessName, formatPhone(c.phone),
      c.saved ? 'Yes' : 'No', c.isBusiness ? 'Yes' : 'No',
      group ? (c.role === 'admin' ? 'Admin' : 'Member') : (c.groups || []).join('; '),
    ].map(csvCell).join(','));
  }
  // BOM so Excel opens names in any language correctly.
  download(fileBase() + '.csv', '\ufeff' + lines.join('\r\n'), 'text/csv;charset=utf-8');
  toast(`Exported ${list.length} contacts to CSV`);
}

function vcfEscape(s) {
  return String(s).replace(/\\/g, '\\\\').replace(/\n/g, '\\n').replace(/[,;]/g, (m) => '\\' + m);
}

function exportVcf() {
  const list = rows().filter((c) => c.phone);
  const cards = list.map((c) => {
    const name = displayName(c) || '+' + c.phone;
    return [
      'BEGIN:VCARD', 'VERSION:3.0',
      'FN:' + vcfEscape(name),
      'N:;' + vcfEscape(name) + ';;;',
      'TEL;TYPE=CELL:+' + c.phone,
      c.businessName ? 'ORG:' + vcfEscape(c.businessName) : '',
      c.groups && c.groups.length ? 'NOTE:' + vcfEscape('Groups: ' + c.groups.join(', ')) : '',
      'END:VCARD',
    ].filter(Boolean).join('\r\n');
  });
  download(fileBase() + '.vcf', cards.join('\r\n') + '\r\n', 'text/vcard;charset=utf-8');
  toast(`Exported ${list.length} contacts to vCard`);
}

async function copyNumbers() {
  const nums = rows().filter((c) => c.phone).map((c) => formatPhone(c.phone));
  await navigator.clipboard.writeText(nums.join('\n'));
  toast(`Copied ${nums.length} numbers`);
}

let toastTimer;
function toast(msg) {
  $('toast').textContent = msg;
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => ($('toast').textContent = ''), 3000);
}

// ---------- wiring ----------

if (prefs.format) $('format').value = prefs.format;
if (prefs.onlyPhone === false) $('only-phone').checked = false;

$('open-wa').addEventListener('click', () => chrome.tabs.create({ url: WA_URL }));
$('retry').addEventListener('click', scan);
$('rescan').addEventListener('click', scan);
$('source').addEventListener('change', (e) => setSource(e.target.value));
$('search').addEventListener('input', render);
$('only-phone').addEventListener('change', () => { savePrefs(); render(); });
$('format').addEventListener('change', () => { savePrefs(); render(); });
$('export-csv').addEventListener('click', exportCsv);
$('export-vcf').addEventListener('click', exportVcf);
$('copy').addEventListener('click', () => copyNumbers().catch(() => toast('Copy failed')));
document.querySelectorAll('.stat').forEach((b) => b.addEventListener('click', () => setSource(b.dataset.source)));

scan();
