#!/usr/bin/env python3
"""
Mission Deck flight simulator.

Fetches a mission's planned waypoints from the API, then "flies" the route by
interpolating between waypoints and POSTing a telemetry ping each step. Watch it
live on the Ops page.

Standard library only -- no pip install needed.

    python3 simulate_flight.py --base-url http://localhost:8080 \\
        --token <API_TOKEN> --mission 1 --drone 2

If --drone is omitted, the mission's assigned drone is used.
"""

import argparse
import json
import math
import sys
import time
import urllib.error
import urllib.request


def http_json(url, token, method="GET", payload=None):
    data = json.dumps(payload).encode() if payload is not None else None
    headers = {"Authorization": "Bearer " + token, "Content-Type": "application/json"}
    req = urllib.request.Request(url, data=data, headers=headers, method=method)
    try:
        with urllib.request.urlopen(req) as resp:
            return json.loads(resp.read().decode())
    except urllib.error.HTTPError as e:
        sys.exit("HTTP {} from {}: {}".format(e.code, url, e.read().decode()))


def bearing(lat1, lon1, lat2, lon2):
    """Compass heading in degrees from point 1 to point 2."""
    p1, p2 = math.radians(lat1), math.radians(lat2)
    dlon = math.radians(lon2 - lon1)
    y = math.sin(dlon) * math.cos(p2)
    x = math.cos(p1) * math.sin(p2) - math.sin(p1) * math.cos(p2) * math.cos(dlon)
    return (math.degrees(math.atan2(y, x)) + 360) % 360


def main():
    ap = argparse.ArgumentParser(description="Fly a Mission Deck mission and stream telemetry.")
    ap.add_argument("--base-url", required=True, help="e.g. http://localhost:8080")
    ap.add_argument("--token", required=True, help="API token (env API_TOKEN on the server)")
    ap.add_argument("--mission", type=int, required=True, help="mission id to fly")
    ap.add_argument("--drone", type=int, help="drone id (defaults to the mission's drone)")
    ap.add_argument("--interval", type=float, default=1.0, help="seconds between pings")
    ap.add_argument("--steps", type=int, default=20, help="interpolation steps per leg")
    args = ap.parse_args()

    base = args.base_url.rstrip("/")
    mission = http_json("{}/api/mission?id={}".format(base, args.mission), args.token)
    waypoints = mission["waypoints"]
    if len(waypoints) < 2:
        sys.exit("Mission needs at least 2 waypoints to fly. Plan a route first.")

    drone_id = args.drone or mission["drone_id"]
    if not drone_id:
        sys.exit("No drone: pass --drone or assign one to the mission.")

    legs = len(waypoints) - 1
    total_steps = legs * args.steps
    sent = 0
    print("Flying '{}' ({} waypoints) as drone {}...".format(mission["name"], len(waypoints), drone_id))

    for leg in range(legs):
        a, b = waypoints[leg], waypoints[leg + 1]
        head = round(bearing(a["latitude"], a["longitude"], b["latitude"], b["longitude"]), 1)
        for step in range(args.steps):
            f = step / args.steps
            lat = a["latitude"] + (b["latitude"] - a["latitude"]) * f
            lng = a["longitude"] + (b["longitude"] - a["longitude"]) * f
            alt = a["altitude"] + (b["altitude"] - a["altitude"]) * f
            battery = round(100 - 75 * (sent / total_steps))  # 100% -> ~25% over the flight
            http_json("{}/api/telemetry".format(base), args.token, "POST", {
                "drone_id": drone_id,
                "mission_id": args.mission,
                "latitude": round(lat, 7),
                "longitude": round(lng, 7),
                "altitude": round(alt, 2),
                "heading": head,
                "battery_pct": battery,
                "status": "flying",
            })
            sent += 1
            print("  ping {}/{}  {:.5f},{:.5f}  alt {:.0f}m  batt {}%".format(
                sent, total_steps, lat, lng, alt, battery))
            time.sleep(args.interval)

    last = waypoints[-1]
    http_json("{}/api/telemetry".format(base), args.token, "POST", {
        "drone_id": drone_id,
        "mission_id": args.mission,
        "latitude": last["latitude"],
        "longitude": last["longitude"],
        "altitude": last["altitude"],
        "heading": 0,
        "battery_pct": round(100 - 75),
        "status": "idle",
    })
    print("Flight complete. {} pings sent.".format(sent + 1))


if __name__ == "__main__":
    main()
