# AI Video Studio

Private web app for generating ad videos with Kling 3.0, Veo 3.1, Veo 3.1 Lite and Seedance 2.0 through the fal.ai API. PHP, no database.

## First run
1. Deploy to Hostinger (Git deploy) into a folder or subdomain.
2. Open the site, choose a login password, paste your fal.ai API key (fal.ai → Dashboard → Keys; add credit under Billing).
3. Generate. Videos are downloaded to `videos/` on your server so they stay after fal's links expire.

Do the first-run setup right after deploying — until a password is set, anyone who opens the page could set it.

## Files
- `index.php` — setup, login, settings and the main screen
- `api.php` — generate, poll status, list videos
- `lib.php` — storage, model list, fal.ai calls, price estimates (edit `models()` to change rates or add models)
- `data/` — config and job list (blocked from the web by `.htaccess`, not in Git)
- `videos/`, `uploads/` — generated videos and product photos (not in Git)

## Costs
The ₹ amounts on screen are estimates from 720p per-second rates in `lib.php`; fal.ai bills the actual amount. Settings has the dollar→rupee rate.
