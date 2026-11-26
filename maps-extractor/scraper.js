// Runs inside the Google Maps tab (injected by the popup).
// Scrolls the results list, reads each place and saves it to chrome.storage.local.
(() => {
  if (window.__gmx) return;

  const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
  const jitter = (min, max) => sleep(min + Math.random() * (max - min));
  const PHONE_RE = /^\+?[\d\s\-().]{7,20}$/;
  const isPhone = (s) => PHONE_RE.test(s) && (s.match(/\d/g) || []).length >= 7;

  const state = { running: false, stop: false };
  let places = {};
  let overlay;

  // ---------- storage ----------

  async function loadPlaces() {
    const got = await chrome.storage.local.get('places');
    places = got.places || {};
  }
  let saveTimer;
  function savePlaces(now) {
    clearTimeout(saveTimer);
    const write = () => chrome.storage.local.set({ places });
    if (now) return write();
    saveTimer = setTimeout(write, 400);
  }
  function setRun(run) {
    return chrome.storage.local.set({ run: { ...run, updatedAt: Date.now() } });
  }

  // ---------- parsing ----------

  function placeId(href) {
    const m = /!1s(0x[0-9a-f]+:0x[0-9a-f]+)/i.exec(href || '');
    if (m) return m[1];
    const c = /[?&]cid=(\d+)/.exec(href || '');
    return c ? 'cid:' + c[1] : (href || '').split('?')[0];
  }

  function coords(href) {
    const m = /!3d(-?[\d.]+)!4d(-?[\d.]+)/.exec(href || '') || /@(-?[\d.]+),(-?[\d.]+)/.exec(href || '');
    return m ? { lat: m[1], lng: m[2] } : { lat: '', lng: '' };
  }

  function currentQuery() {
    const box = document.querySelector('input#searchboxinput, input[name="q"]');
    if (box && box.value) return box.value.trim();
    const m = /\/maps\/search\/([^/@?]+)/.exec(location.pathname);
    return m ? decodeURIComponent(m[1].replace(/\+/g, ' ')) : '';
  }

  // Parse a result card from the list (fast, no clicking).
  function parseCard(a, query) {
    const card = a.closest('.Nv2PK') || a.parentElement;
    const name = (a.getAttribute('aria-label') || '').trim();
    const href = a.href;
    const p = {
      id: placeId(href), name, phone: '', website: '', address: '', category: '',
      rating: '', reviews: '', plusCode: '', ...coords(href),
      url: href.split('?')[0], query, detailed: false, scrapedAt: Date.now(),
    };
    if (!card) return p;

    const site = card.querySelector('a[data-value="Website"], a[aria-label^="Visit"][href^="http"]');
    if (site) p.website = site.href;

    const lines = card.innerText.split('\n').map((s) => s.trim()).filter(Boolean);
    let infoLines = [];
    for (const line of lines) {
      if (line === name) continue;
      const r = /^(\d[.,]\d)\s*\(([\d,.\sKk]+)\)/.exec(line);
      if (r && !p.rating) { p.rating = r[1].replace(',', '.'); p.reviews = r[2].replace(/[,.\s]/g, ''); continue; }
      if (/^\d[.,]\d$/.test(line) && !p.rating) { p.rating = line.replace(',', '.'); continue; }
      if (line.includes('·')) infoLines.push(line.split('·').map((s) => s.trim()).filter(Boolean));
      else if (isPhone(line) && !p.phone) p.phone = line;
    }
    if (!p.rating) {
      const img = card.querySelector('[role="img"][aria-label]');
      const r = img && /(\d[.,]\d)\D+([\d,.]+)/.exec(img.getAttribute('aria-label'));
      if (r) { p.rating = r[1].replace(',', '.'); p.reviews = r[2].replace(/[,.]/g, ''); }
    }
    for (const parts of infoLines) {
      const ph = parts.find(isPhone);
      if (ph && !p.phone) p.phone = ph;
    }
    const first = infoLines.find((parts) => !parts.some(isPhone));
    if (first) {
      p.category = first[0] || '';
      const rest = first.slice(1).filter((s) => !/^[₹$€£¥]|^\$+$/.test(s));
      if (rest.length) p.address = rest[rest.length - 1];
    }
    return p;
  }

  // Read the open place panel (full details).
  function parseDetails(root, p) {
    const q = (sel) => root.querySelector(sel);
    const label = (el) => (el && (el.getAttribute('aria-label') || el.innerText) || '').replace(/^[^:]*:\s*/, '').trim();

    const tel = q('[data-item-id^="phone:tel:"]');
    if (tel) p.phone = label(tel) || tel.getAttribute('data-item-id').slice('phone:tel:'.length);
    const site = q('a[data-item-id="authority"]');
    if (site) p.website = site.href;
    const addr = q('[data-item-id="address"]');
    if (addr) p.address = label(addr);
    const plus = q('[data-item-id="oloc"]');
    if (plus) p.plusCode = label(plus);
    const cat = q('button[jsaction*="category"]');
    if (cat && cat.innerText.trim()) p.category = cat.innerText.trim();
    const stars = q('[role="img"][aria-label*="star" i]');
    const r = stars && /(\d[.,]\d)/.exec(stars.getAttribute('aria-label'));
    if (r && !p.rating) p.rating = r[1].replace(',', '.');
    if (!p.reviews) {
      const rev = Array.from(root.querySelectorAll('[aria-label]'))
        .map((e) => /^([\d,.\s]+)\s+reviews?$/i.exec(e.getAttribute('aria-label').trim()))
        .find(Boolean);
      if (rev) p.reviews = rev[1].replace(/[,.\s]/g, '');
    }
    p.detailed = true;
    return p;
  }

  async function waitForPanel(name, timeout = 8000) {
    const start = Date.now();
    while (Date.now() - start < timeout) {
      const panel = Array.from(document.querySelectorAll('div[role="main"][aria-label]'))
        .find((m) => m.getAttribute('aria-label') === name);
      if (panel && panel.querySelector('[data-item-id]')) {
        await sleep(300); // phone/website rows render a moment later
        return panel;
      }
      await sleep(200);
    }
    return null;
  }

  // ---------- overlay ----------

  function showOverlay() {
    if (overlay) return;
    overlay = document.createElement('div');
    overlay.style.cssText = 'position:fixed;left:16px;bottom:16px;z-index:2147483647;background:#1a1f1d;color:#fff;' +
      'font:13px/1.4 system-ui,sans-serif;padding:10px 12px;border-radius:10px;box-shadow:0 4px 18px rgba(0,0,0,.35);' +
      'display:flex;gap:10px;align-items:center;max-width:320px';
    const text = document.createElement('span');
    text.className = 'gmx-text';
    const btn = document.createElement('button');
    btn.textContent = 'Stop';
    btn.style.cssText = 'background:#e5484d;color:#fff;border:0;border-radius:6px;padding:4px 10px;cursor:pointer;font:inherit';
    btn.onclick = () => { state.stop = true; btn.textContent = 'Stopping…'; };
    overlay.append(text, btn);
    text.textContent = 'Maps Extractor: starting…';
    document.body.appendChild(overlay);
  }
  function status(msg) {
    if (overlay) overlay.querySelector('.gmx-text').textContent = msg;
  }
  function hideOverlay() {
    if (overlay) overlay.remove();
    overlay = null;
  }

  // ---------- main loop ----------

  async function run(opts) {
    const max = Math.max(1, Number(opts.max) || 200);
    const detailed = !!opts.detailed;
    const query = currentQuery();
    await loadPlaces();
    state.running = true;
    state.stop = false;
    showOverlay();

    let found = 0, done = 0;
    const report = (phase) => {
      status(`Maps Extractor: ${phase.toLowerCase()} · ${done} saved${detailed ? '' : ` of ${found}`}`);
      return setRun({ running: true, phase, done, found, query, detailed });
    };

    try {
      const feed = document.querySelector('div[role="feed"]');

      // A single place is open (no list): save just that one.
      if (!feed) {
        const panel = document.querySelector('div[role="main"][aria-label]');
        const h1 = document.querySelector('h1.DUwDvf, h1');
        if (!panel || !h1) throw new Error('Search for something on Google Maps first (e.g. "plumbers in Hyderabad").');
        const p = { id: placeId(location.href), name: (panel.getAttribute('aria-label') || h1.innerText).trim(),
          phone: '', website: '', address: '', category: '', rating: '', reviews: '', plusCode: '',
          ...coords(location.href), url: location.href.split('?')[0], query, scrapedAt: Date.now() };
        places[p.id] = parseDetails(panel, p);
        done = 1;
        await savePlaces(true);
        return finish('Saved 1 place', done, found, query, detailed);
      }

      const seen = new Set();
      let idle = 0;
      while (!state.stop && done < max) {
        const anchors = Array.from(feed.querySelectorAll('a.hfpxzc[href*="/maps/place/"]'));
        const fresh = anchors.filter((a) => !seen.has(placeId(a.href)));
        found = anchors.length;

        for (const a of fresh) {
          if (state.stop || done >= max) break;
          const p = parseCard(a, query);
          seen.add(p.id);
          if (!p.name) continue;
          if (detailed) {
            a.scrollIntoView({ block: 'center' });
            a.click();
            const panel = await waitForPanel(p.name);
            if (panel) parseDetails(panel, p);
            await jitter(600, 1300);
          }
          const prev = places[p.id];
          places[p.id] = prev ? { ...prev, ...Object.fromEntries(Object.entries(p).filter(([, v]) => v !== '')) } : p;
          done++;
          savePlaces();
          if (detailed || done % 10 === 0) await report(detailed ? 'Opening places' : 'Reading list');
        }

        if (state.stop || done >= max) break;
        const ended = /end of the list|end of list/i.test(feed.innerText.slice(-400)) ||
          !!feed.querySelector('.HlvSq');
        if (ended && !fresh.length) break;

        feed.scrollTop = feed.scrollHeight;
        await report('Loading more results');
        await jitter(1200, 1800);
        if (!fresh.length) {
          if (++idle >= 6) break; // nothing new after several scrolls
        } else idle = 0;
      }

      await savePlaces(true);
      const why = state.stop ? 'Stopped' : done >= max ? 'Reached limit' : 'Finished';
      return finish(`${why}: ${done} places`, done, found, query, detailed);
    } catch (e) {
      await setRun({ running: false, phase: 'Error', error: e.message, done, found, query, detailed });
      status(e.message);
      setTimeout(hideOverlay, 5000);
      state.running = false;
      return { ok: false, error: e.message };
    }
  }

  async function finish(msg, done, found, query, detailed) {
    await setRun({ running: false, phase: msg, done, found, query, detailed });
    status(msg + ' — open the extension to export');
    state.running = false;
    setTimeout(hideOverlay, 4000);
    return { ok: true, done };
  }

  window.__gmx = {
    start(opts) {
      if (state.running) return { ok: false, error: 'Already running' };
      run(opts || {}); // keeps going after the popup closes
      return { ok: true };
    },
    stop() { state.stop = true; return { ok: true }; },
    isRunning: () => state.running,
  };
})();
