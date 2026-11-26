# Maps Business Extractor (Chrome extension)

Collects businesses from a Google Maps search (name, phone, website, address, category, rating,
reviews, plus code, latitude/longitude and the Maps link) and exports them to
**CSV (Excel / Google Sheets)** or **vCard (.vcf)**, or copies the phone numbers.

## Use it
1. Open `chrome://extensions`, switch on **Developer mode**, click **Load unpacked** and pick this
   `maps-extractor` folder (or unzip `maps-extractor.zip` first).
2. Go to https://www.google.com/maps and search, e.g. `plumbers in Hyderabad`.
3. Click the extension icon, then **Start extracting**. A small status box with a **Stop** button
   appears on the Maps page. You can close the popup, because the extraction keeps running in the tab.
4. Open the popup again and click **Download CSV**, **vCard** or **Copy phones**.

### Two modes
- **Open each place (full details)** (default): clicks every result and reads the full address,
  phone, website and plus code. Takes about 1–2 seconds per place.
- **Unticked (quick)**: reads only the results list. About 120 places in roughly 20 seconds. Most
  phones and websites are included, but addresses are shortened and plus codes are missing.

**Max** limits how many places one run collects. Google Maps shows up to about 120 results per
search, so for more, run several searches (different areas or keywords). Results from every
search are kept and de-duplicated until you click **Clear all**.

## CSV columns
Name, Phone, Website, Address, Category, Rating, Reviews, Plus Code, Latitude, Longitude,
Google Maps URL, Search

## How it works
- When you click Start, the popup injects `scraper.js` into the active Google Maps tab (`activeTab` + `scripting`).
- `scraper.js` scrolls the results list, reads each card or opens each place, and saves results to
  `chrome.storage.local`. The popup shows and exports them. Nothing is sent to any server.

## Notes
- Google changes the Maps page from time to time; if fields come back empty, the selectors in
  `scraper.js` (`a.hfpxzc`, `[data-item-id="address"]`, `[data-item-id^="phone:tel:"]`,
  `a[data-item-id="authority"]`) may need updating.
- Automated collection from Google Maps is against Google's Terms of Service. Keep runs modest,
  and follow anti-spam / privacy laws (e.g. DPDP Act, TRAI DND rules) when contacting businesses.
