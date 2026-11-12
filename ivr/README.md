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
- `data/`: settings and call log, blocked from the web and not in git

## Notes
- Forgot the password: delete `data/settings.json` in File Manager and set up again.
- The folder can be moved to another path or site as it is. Copy `data/` to keep the
  settings and call log.
- Not built yet: products and prices, script editor, phone line, leads, staff transfer.

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
