#!/usr/bin/env python3
"""
Mission Deck fleet simulator.

Fetches every drone from the API and flies them all at once, each on its own
random-walk path, streaming telemetry so the whole fleet animates on the Live
Ops page. Standard library only -- no pip install needed.

    python3 simulate_fleet.py --base-url http://localhost:8080 \\
        --token <API_TOKEN> --duration 60

Watch them move on the Ops page while this runs.
"""

import argparse
import json
import math
import random
import sys
import threading
import time
import urllib.error
import urllib.request


def request_json(url, token, method="GET", payload=None):
    data = json.dumps(payload).encode() if payload is not None else None
    headers = {"Authorization": "Bearer " + token, "Content-Type": "application/json"}
    req = urllib.request.Request(url, data=data, headers=headers, method=method)
    with urllib.request.urlopen(req) as resp:
        return json.loads(resp.read().decode())


def fly(drone, args, stop_at):
    """Random-walk one drone around the center until stop_at, streaming telemetry."""
    base = args.base_url.rstrip("/")
    lat = args.center_lat + random.uniform(-args.spread, args.spread)
    lng = args.center_lng + random.uniform(-args.spread, args.spread)
    alt = random.uniform(40, 100)
    heading = random.uniform(0, 360)
    battery = 100.0

    while time.time() < stop_at:
        heading = (heading + random.uniform(-30, 30)) % 360
        rad = math.radians(heading)
        lat += 0.0008 * math.cos(rad)
        lng += 0.0008 * math.sin(rad)
        # Bounce back toward the center if we drift past the spread box.
        lat = max(args.center_lat - args.spread, min(args.center_lat + args.spread, lat))
        lng = max(args.center_lng - args.spread, min(args.center_lng + args.spread, lng))
        alt = max(20, min(120, alt + random.uniform(-5, 5)))
        battery = max(15, battery - random.uniform(0.2, 0.8))
        try:
            request_json(base + "/api/telemetry", args.token, "POST", {
                "drone_id": drone["id"],
                "latitude": round(lat, 7),
                "longitude": round(lng, 7),
                "altitude": round(alt, 2),
                "heading": round(heading, 1),
                "battery_pct": round(battery),
                "status": "flying",
            })
        except (urllib.error.URLError, urllib.error.HTTPError):
            pass  # drop a ping and keep flying
        time.sleep(args.interval)


def main():
    ap = argparse.ArgumentParser(description="Fly the whole fleet around for a live demo.")
    ap.add_argument("--base-url", required=True, help="e.g. http://localhost:8080")
    ap.add_argument("--token", required=True, help="API token (env API_TOKEN on the server)")
    ap.add_argument("--duration", type=float, default=60, help="seconds to fly (default 60)")
    ap.add_argument("--interval", type=float, default=1.0, help="seconds between pings per drone")
    ap.add_argument("--center-lat", type=float, default=29.21, help="center latitude")
    ap.add_argument("--center-lng", type=float, default=-81.02, help="center longitude")
    ap.add_argument("--spread", type=float, default=0.05, help="how far drones wander, in degrees")
    args = ap.parse_args()

    base = args.base_url.rstrip("/")
    try:
        drones = request_json(base + "/api/drones", args.token)
    except (urllib.error.URLError, urllib.error.HTTPError) as e:
        sys.exit("Could not list drones: {}".format(e))
    if not drones:
        sys.exit("No drones to fly. Seed the fleet first.")

    print("Flying {} drones for {:.0f}s...".format(len(drones), args.duration))
    stop_at = time.time() + args.duration
    threads = [threading.Thread(target=fly, args=(d, args, stop_at), daemon=True) for d in drones]
    for t in threads:
        t.start()
    for t in threads:
        t.join()
    print("Fleet flight complete.")


if __name__ == "__main__":
    main()
