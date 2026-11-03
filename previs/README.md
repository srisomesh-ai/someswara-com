# Previs Engine: phone control panel

Reference photo + shot description → 5-second cinematic clip.
The panel runs on your Hostinger hosting. The video is made on a rented RunPod GPU
(Wan 2.1 image-to-video). The GPU switches itself off after 15 idle minutes.

```
Phone browser ──▶ previs.yourdomain.com (Hostinger, PHP) ──▶ RunPod GPU (ComfyUI + Wan 2.1)
                         clips saved here                       auto-shutdown runs here
```

## 1. RunPod (10 minutes, once)
1. Sign up at runpod.io → **Billing** → add $10–20.
2. **Settings → API Keys → Create API Key** (All access). Copy it.
3. Optional but recommended: **Storage → Network Volume → New**, 40 GB, in a data
   centre that lists RTX 4090. Copy its ID. (~$2.80/month. Without it, every start
   re-downloads ~30 GB of models: ~10–20 min. With it, only the first start is slow.)

## 2. Hostinger (5 minutes, once)
1. hPanel → **Domains → Subdomains** → create e.g. `previs.yourdomain.com`.
2. hPanel → **File Manager** → open the subdomain's folder → upload the contents of
   this zip (index.php, api.php, lib.php, data/, pod/).
   Or push this folder to GitHub and use Git deploy as usual.
3. PHP 8.1 or newer (hPanel → Advanced → PHP Configuration). Default is fine.
4. Optional: hPanel → SSL to make sure HTTPS is on for the subdomain.

## 3. Use it
1. Open the subdomain on your phone. First visit shows **Set up**: paste the API key,
   the volume ID (if any), and choose a panel password.
2. **Start GPU** → wait for the green "GPU ready" badge (live progress is shown).
3. Add a reference photo, describe the shot, tap camera moves, **Render shot**.
4. Clips appear below with progress %, then play/download. They stay saved on your
   hosting after the GPU is off.
5. **Stop GPU** when done, or just leave: it shuts itself off after 15 idle minutes.

## Costs (approx.)
- RTX 4090: ~$0.35–0.70/hr (₹30–60). One 5 s clip ≈ 6–10 min.
- Network volume: ~$0.07/GB/month.
- Your RunPod balance is a hard cap: load small amounts.

## Troubleshooting
- **"No GPU free right now"**: try again in a few minutes. With a network volume, the GPU
  must be free in that volume's data centre.
- **Badge shows "Problem"**: tap Stop GPU, then Start again. Logs on the pod:
  `/guardian.log`, `/comfy.log` (RunPod console → pod → Logs).
- **Forgot panel password**: delete `data/settings.json` in File Manager and set up again.

## Security notes
- The panel is password-protected; `data/` and `pod/` are blocked from the web.
- Your RunPod key is stored in `data/settings.json` on your hosting and passed to the GPU
  so it can switch itself off. Rotate it in RunPod if it ever leaks.
- Only use photos of cast members who've agreed to it.

## Files
- `index.php` – the phone panel
- `api.php` – panel backend (RunPod + ComfyUI calls)
- `lib.php` – settings, RunPod/ComfyUI helpers, video workflow, GPU preferences
- `pod/bootstrap.sh` – runs on the GPU at boot: installs ComfyUI, downloads models
- `pod/guardian.py` – idle auto-shutdown (runs on the GPU)
- `pod/progress.py` – reports render % to the panel
