#!/bin/bash
# Runs on the RunPod GPU at boot. Installs ComfyUI, downloads models, starts everything.
S=/status
mkdir -p "$S"
st() { echo "$1" > "$S/status.txt"; echo "[boot] $1"; }
st "Booting"
echo '{}' > "$S/progress.json"
(cd "$S" && nohup python -m http.server 8189 >/dev/null 2>&1 &)

BASE="${BASE_DIR:-/opt}"
mkdir -p "$BASE" && cd "$BASE" || exit 1

# Safety net first: idle auto-shutdown must run even if setup below fails
echo "$GUARDIAN_B64" | base64 -d > /guardian.py
nohup python /guardian.py > /guardian.log 2>&1 &

st "Installing ComfyUI"
if [ ! -d ComfyUI ]; then
  git clone --depth 1 https://github.com/comfyanonymous/ComfyUI.git || { st "Error: could not download ComfyUI"; sleep infinity; }
else
  (cd ComfyUI && git pull -q) || true
fi
cd ComfyUI
pip install -q -r requirements.txt websocket-client || { st "Error: package install failed"; sleep infinity; }

n=$(printf '%s\n' "$MODELS" | grep -c .)
i=0
while read -r url dir; do
  [ -z "$url" ] && continue
  i=$((i+1))
  mkdir -p "models/$dir"
  f="models/$dir/$(basename "$url")"
  if [ ! -s "$f" ]; then
    st "Downloading model $i of $n"
    curl -fsSL -C - -o "$f.part" "$url" && mv "$f.part" "$f" || { st "Error: model download failed ($(basename "$url"))"; sleep infinity; }
  fi
done <<< "$MODELS"

st "Starting video engine"
python main.py --listen 0.0.0.0 --port 8188 > /comfy.log 2>&1 &
COMFY=$!
until curl -s -o /dev/null http://127.0.0.1:8188/queue; do
  kill -0 $COMFY 2>/dev/null || { st "Error: video engine crashed (see /comfy.log)"; sleep infinity; }
  sleep 3
done

echo "$PROGRESS_B64" | base64 -d > /progress.py
nohup python /progress.py > /progress.log 2>&1 &
st "ready"
wait $COMFY
st "Error: video engine stopped"
sleep infinity
