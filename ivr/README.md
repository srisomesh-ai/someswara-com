# Bharat GPS Tracker: AI call agent dashboard

Admin dashboard for the AI call-receiving system. Step 1 of the build: the dashboard
and a browser test call in Telugu. No phone line yet.

## Use it
1. Open `/ivr` on the site. The first visit asks you to choose the admin password.
2. Settings: paste the Sarvam API key (dashboard.sarvam.ai) and pick a voice.
3. Overview: tap **Talk to the agent here**, allow the microphone, and speak after the
   agent's greeting. Pause for about a second and the agent replies.

## How a test call works
Browser microphone → `api.php` → Sarvam speech-to-text → Sarvam chat model → Sarvam
text-to-speech → played in the browser. One turn at a time, so replies are slower than
the real phone line will be.

## Files
- `index.php`: the dashboard page
- `api.php`: login, settings, test-call backend, call log
- `lib.php`: helpers, the agent's instructions (`SYSTEM_PROMPT`) and greeting
- Saved data (settings, call log, training) lives in `ivr-data/` next to the website folder, outside
  what a deploy replaces. `data/` inside this folder is only the fallback, blocked from the web and not in git

## Notes
- Forgot the password: delete `settings.json` in the `ivr-data` folder (File Manager, one level above
  `public_html`) and set up again.
- The folder can be moved to another path or site as it is. Copy the `ivr-data` folder
  to keep the settings and call log.
- Prices: the owner types his own list on the Products and prices page (`products.json` in the data folder). She says exactly those prices; the website is not read.
- Lead email: after every real phone call a summary goes to `LEAD_EMAIL` (`lib.php`).
- GPS servers: on a renewal call she looks the vehicle number up on the three tracking servers (read only) and tells the expiry date. The server keys are typed on the GPS servers page and kept in `settings.json`, never in this code.
- Caller memory: when a mobile number calls again within 30 days she is given the end of its last call.
- After a phone call: the call is read once more to see what it was. Complaints, new bookings, re-adding and other requests become an unassigned task in TaskManager (`TASK_URL` in `lib.php`); renewals are only emailed. Every call is emailed to `LEAD_EMAIL`.
- Not built yet: leads page, staff transfer.

## Live call server (ivr/live/)
Talk tests use a small Node server on a VPS when it is reachable (`LIVE_URL` in `lib.php`), and fall
back to the turn-by-turn PHP path when it is not. The live server keeps one open connection per call:
audio streams to Sarvam speech-to-text while the caller talks, the reply streams back sentence by
sentence, and the caller can interrupt.

- Install or update on the VPS (as root): `curl -fsSL https://someswara.com/ivr/live/install.sh | bash`
- Remove: `curl -fsSL https://someswara.com/ivr/live/install.sh | bash -s -- remove`
- Logs: `journalctl -u bgt-call -n 50`
- It stores nothing. Per call it trades a one-time token with `api.php` for the Sarvam key, voice,
  language and instructions, and sends the transcript back the same way.

## Phone line (Exotel)
Real calls reach the same live server at `PHONE_URL` (`lib.php`). The dashboard's Phone line page shows
the full address with its secret key; it goes into an Exotel Voicebot applet (default 8000 Hz).
`phone_config` in `api.php` checks the key and the caller's number: in "test numbers only" other callers
hear a call-back message and the call ends. Phone calls are saved in the call log with the caller's number.
