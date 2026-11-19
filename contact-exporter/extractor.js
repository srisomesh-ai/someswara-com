// Runs inside the web.whatsapp.com tab (injected by the popup).
// Reads WhatsApp Web's own local IndexedDB ("model-storage") and returns
// plain contact / group data. Nothing leaves the browser.
(() => {
  if (window.__ccxExtract) return;

  const DB_NAME = 'model-storage';

  function openDb(name) {
    return new Promise((resolve, reject) => {
      const req = indexedDB.open(name);
      req.onsuccess = () => resolve(req.result);
      req.onerror = () => reject(req.error);
      // If the DB did not exist we just created an empty one; abort that.
      req.onupgradeneeded = () => {
        req.transaction.abort();
        reject(new Error('NO_DB'));
      };
    });
  }

  function readAll(db, store) {
    return new Promise((resolve) => {
      if (!db.objectStoreNames.contains(store)) return resolve([]);
      try {
        const req = db.transaction(store, 'readonly').objectStore(store).getAll();
        req.onsuccess = () => resolve(req.result || []);
        req.onerror = () => resolve([]);
      } catch (e) {
        resolve([]);
      }
    });
  }

  // WhatsApp ids look like "919876543210@c.us", "1234-5678@g.us", "12345@lid",
  // or older serialized objects {user, server, _serialized}.
  function idStr(id) {
    if (!id) return '';
    if (typeof id === 'string') return id;
    if (id._serialized) return id._serialized;
    if (id.user && id.server) return id.user + '@' + id.server;
    if (id.id) return idStr(id.id);
    return String(id);
  }

  function server(id) {
    const s = idStr(id);
    const at = s.lastIndexOf('@');
    return at === -1 ? '' : s.slice(at + 1);
  }

  function user(id) {
    const s = idStr(id);
    const at = s.indexOf('@');
    return (at === -1 ? s : s.slice(0, at)).split(':')[0];
  }

  const isPhoneServer = (srv) => srv === 'c.us' || srv === 's.whatsapp.net';

  async function extract() {
    let db;
    try {
      db = await openDb(DB_NAME);
    } catch (e) {
      return { ok: false, error: 'WhatsApp data not found. Make sure WhatsApp Web is logged in and has finished loading.' };
    }

    const storeNames = Array.from(db.objectStoreNames);
    const [contactsRaw, chatsRaw, groupMetaRaw, participantRaw] = await Promise.all([
      readAll(db, 'contact'),
      readAll(db, 'chat'),
      readAll(db, 'group-metadata'),
      readAll(db, 'participant'),
    ]);

    // Any store that maps a LID (hidden id) to a phone number.
    const lidToPhone = new Map();
    const addLid = (lid, pn) => {
      if (lid && pn && server(lid) === 'lid' && isPhoneServer(server(pn))) {
        lidToPhone.set(idStr(lid), user(pn));
      }
    };
    for (const name of storeNames.filter((n) => /lid|pn/i.test(n))) {
      for (const r of await readAll(db, name)) {
        addLid(r.lid || r.lidJid || r.id, r.pn || r.pnJid || r.phoneNumber);
      }
    }
    db.close();

    const people = new Map(); // key = phone digits or raw id

    function phoneFor(id, rec) {
      const srv = server(id);
      if (isPhoneServer(srv)) return user(id);
      if (srv === 'lid') {
        if (rec && rec.phoneNumber) return user(rec.phoneNumber);
        return lidToPhone.get(idStr(id)) || '';
      }
      return '';
    }

    function upsert(id, rec, extra) {
      const raw = idStr(id);
      const srv = server(raw);
      if (!raw || !(isPhoneServer(srv) || srv === 'lid')) return null;
      if (user(raw) === '0' || raw === 'status@broadcast') return null;
      const phone = phoneFor(raw, rec);
      const key = phone || raw;
      const p = people.get(key) || {
        key, id: raw, phone, name: '', pushname: '', businessName: '',
        saved: false, isBusiness: false, hasChat: false, lastChat: 0, groups: [],
      };
      if (rec) {
        if (rec.name && !p.name) p.name = rec.name;
        if (rec.pushname && !p.pushname) p.pushname = rec.pushname;
        if (rec.verifiedName && !p.businessName) p.businessName = rec.verifiedName;
        if (rec.isBusiness || rec.verifiedName) p.isBusiness = true;
        const flag = rec.isAddressBookContact;
        if (flag === 1 || flag === true || (flag === undefined && rec.name)) p.saved = true;
      }
      if (!p.phone && phone) p.phone = phone;
      Object.assign(p, extra || {});
      people.set(key, p);
      return p;
    }

    // LID records that carry a phone number also teach us the mapping.
    for (const c of contactsRaw) addLid(c.id, c.phoneNumber);
    for (const c of contactsRaw) upsert(c.id, c);

    for (const ch of chatsRaw) {
      const srv = server(ch.id);
      if (isPhoneServer(srv) || srv === 'lid') {
        const p = upsert(ch.id, null);
        if (p) {
          p.hasChat = true;
          p.lastChat = Math.max(p.lastChat, Number(ch.t) || 0);
        }
      }
    }

    // Groups
    const groups = new Map();
    const chatName = new Map(chatsRaw.map((c) => [idStr(c.id), c.name || c.formattedTitle || '']));
    for (const g of groupMetaRaw) {
      const gid = idStr(g.id);
      groups.set(gid, {
        id: gid,
        name: g.subject || chatName.get(gid) || gid,
        created: Number(g.creation) || 0,
        isCommunity: !!(g.isParentGroup || g.groupType === 'COMMUNITY'),
        members: new Map(),
      });
    }

    function addMember(gid, m, role) {
      const mid = idStr(m && (m.id || m));
      const g = groups.get(gid) || { id: gid, name: chatName.get(gid) || gid, created: 0, members: new Map() };
      groups.set(gid, g);
      const p = upsert(mid, null);
      if (!p) return;
      const isAdmin = role || (m && (m.isAdmin || m.isSuperAdmin)) ? 'admin' : '';
      const prev = g.members.get(p.key);
      g.members.set(p.key, prev === 'admin' ? 'admin' : isAdmin);
      if (!p.groups.includes(g.name)) p.groups.push(g.name);
    }

    for (const g of groupMetaRaw) {
      for (const m of g.participants || []) addMember(idStr(g.id), m);
    }
    for (const r of participantRaw) {
      const gid = idStr(r.groupId || r.id);
      const admins = new Set([...(r.admins || []), ...(r.superAdmins || [])].map(idStr));
      for (const m of r.participants || []) addMember(gid, m, admins.has(idStr(m && (m.id || m))));
    }

    // Groups' names may have been filled after members were attached.
    const groupList = Array.from(groups.values())
      .filter((g) => g.members.size > 0 || g.name !== g.id)
      .map((g) => ({
        id: g.id, name: g.name, created: g.created, isCommunity: !!g.isCommunity,
        members: Array.from(g.members, ([key, role]) => ({ key, role })),
      }))
      .sort((a, b) => a.name.localeCompare(b.name));

    const myId = (() => {
      try {
        const v = localStorage.getItem('last-wid-md') || localStorage.getItem('last-wid') || '';
        return user(v.replace(/"/g, ''));
      } catch (e) { return ''; }
    })();

    const contacts = Array.from(people.values()).filter((p) => p.phone !== myId || !myId);

    return {
      ok: true,
      scannedAt: Date.now(),
      contacts,
      groups: groupList,
      debug: { stores: storeNames, counts: { contact: contactsRaw.length, chat: chatsRaw.length, groups: groupMetaRaw.length, participant: participantRaw.length } },
    };
  }

  window.__ccxExtract = () =>
    extract().catch((e) => ({ ok: false, error: 'Could not read WhatsApp data: ' + (e && e.message) }));
})();
