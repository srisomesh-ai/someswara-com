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
