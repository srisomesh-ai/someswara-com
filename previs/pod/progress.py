"""Listens to ComfyUI and writes render progress (0-100 per job) to /status/progress.json."""
import json
import os
import time

import websocket

CLIENT_ID = "previs-panel"     # the panel submits jobs with this id, so progress comes here
SAMPLER_NODE = "11"            # KSampler node in the workflow = most of the render time
OUT = "/status/progress.json"
state = {}


def save():
    with open(OUT + ".tmp", "w") as f:
        json.dump(state, f)
    os.replace(OUT + ".tmp", OUT)


while True:
    try:
        ws = websocket.create_connection(f"ws://127.0.0.1:8188/ws?clientId={CLIENT_ID}")
        while True:
            msg = ws.recv()
            if not isinstance(msg, str):
                continue
            d = json.loads(msg)
            data = d.get("data") or {}
            pid = data.get("prompt_id")
            if not pid:
                continue
            if d.get("type") == "progress" and str(data.get("node")) == SAMPLER_NODE:
                state[pid] = round(95 * data["value"] / max(1, data["max"]))
                save()
            elif d.get("type") == "executing" and data.get("node") is None:
                state[pid] = 100
                save()
    except Exception:
        time.sleep(3)
