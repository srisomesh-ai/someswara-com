# Chat Contact Exporter (Chrome extension)

Exports contacts from WhatsApp Web to **CSV (opens in Excel / Google Sheets)** or **vCard (.vcf)**,
or copies the numbers to the clipboard.

## What it can export
- All contacts WhatsApp Web knows about
- Saved contacts (in your phone's address book)
- Unsaved numbers (people who are not in your address book)
- People you have chatted with
- Business accounts
- Members of all groups, or the members of one group (with admin role)

Options: search by name or number, skip entries whose number is hidden, and pick a phone format
(`+919876543210`, `919876543210` or `+91 98765 43210`).

CSV columns: Name, Saved Name, WhatsApp Name, Business Name, Phone, Saved, Business, Groups (or Role
when you export one group).

## Install (developer mode)
1. Download this `contact-exporter` folder (or unzip `contact-exporter.zip`).
2. Open `chrome://extensions` and switch on **Developer mode** (top right).
3. Click **Load unpacked** and pick the `contact-exporter` folder.
4. Open https://web.whatsapp.com, log in and let your chats load.
5. Click the extension icon, pick what to export, and click **Download CSV**.

## How it works
- The popup injects `extractor.js` into the WhatsApp Web tab only when you click the icon
  (`activeTab` + `scripting`, host access limited to `web.whatsapp.com`).
- `extractor.js` reads WhatsApp Web's own local browser database (IndexedDB `model-storage`:
  the `contact`, `chat`, `group-metadata` and `participant` stores) and returns plain data to the popup.
- Files are created in the popup. Nothing is sent to any server.

## Limits
- WhatsApp hides the phone number of some group members (ids ending in `@lid`). When WhatsApp Web
  has no number for them, they show as "number hidden" and are left out unless you untick
  "Only with phone number".
- Only data WhatsApp Web has already synced is available; wait for chats to load, then use ↻ to rescan.
- WhatsApp Web changes its internals from time to time; if a scan returns nothing, the store
  names in `extractor.js` may need updating.
- This tool is not affiliated with WhatsApp or Meta. Respect people's privacy and anti-spam laws
  when using exported numbers.

## Files
| File | Purpose |
|------|---------|
| `manifest.json` | Manifest V3 definition |
| `popup.html` / `popup.css` / `popup.js` | The popup UI, filters and CSV/vCard export |
| `extractor.js` | Runs in the WhatsApp Web tab and reads the contact data |
| `icons/` | Extension icons |
