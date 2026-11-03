"""Idle auto-shutdown. Runs on the GPU pod itself, so it works even if every phone and laptop is off.

Checks ComfyUI's queue every 30 s. After IDLE_MINUTES with nothing rendering or waiting,
it terminates its own pod through the RunPod REST API. While setup is still running
(ComfyUI not answering) it waits up to BOOT_GRACE minutes, then shuts down anyway
so a broken boot can't bill forever.
"""
import json
import os
import time
import urllib.request

POD_ID = os.environ["RUNPOD_POD_ID"]          # set by RunPod automatically
API_KEY = os.environ["GUARDIAN_API_KEY"]
IDLE_MINUTES = int(os.getenv("IDLE_MINUTES", "15"))
BOOT_GRACE = int(os.getenv("BOOT_GRACE_MINUTES", "60"))
COMFY = "http://127.0.0.1:8188"


def log(msg):
    print(time.strftime("%H:%M:%S"), msg, flush=True)


def busy():
    try:
        with urllib.request.urlopen(f"{COMFY}/queue", timeout=10) as r:
            q = json.load(r)
        return bool(q.get("queue_running") or q.get("queue_pending"))
    except Exception:
        return None  # ComfyUI not up (yet)


def terminate():
    for attempt in range(5):
        try:
            req = urllib.request.Request(
                f"https://rest.runpod.io/v1/pods/{POD_ID}", method="DELETE",
                headers={"Authorization": f"Bearer {API_KEY}"})
            urllib.request.urlopen(req, timeout=30).read()
            log("terminate request sent")
            return
        except Exception as e:
            log(f"terminate failed ({e}), retrying")
            time.sleep(20)


def main():
    started = time.time()
    seen_ready = False
    idle_since = None
    while True:
        b = busy()
        now = time.time()
        if b is None and not seen_ready:
            if now - started > BOOT_GRACE * 60:
                log("boot never finished, shutting down")
                return terminate()
        elif b:
            seen_ready = True
            idle_since = None
        else:
            seen_ready = True
            idle_since = idle_since or now
            if now - idle_since >= IDLE_MINUTES * 60:
                log(f"idle {IDLE_MINUTES} min, shutting down")
                return terminate()
        time.sleep(30)


if __name__ == "__main__":
    main()
