<?php

declare(strict_types=1);

namespace app\components;

use app\models\Waypoint;

/**
 * Converts between Mission Deck waypoints and the QGroundControl ".plan" format
 * (the JSON mission file QGC and MissionPlanner read/write).
 *
 * Waypoints use MAV_CMD_NAV_WAYPOINT (command 16) in frame 3
 * (MAV_FRAME_GLOBAL_RELATIVE_ALT), so altitude is relative to home — which is
 * exactly our AGL convention.
 */
class QgcPlan
{
    private const NAV_WAYPOINT = 16;
    private const FRAME_RELATIVE_ALT = 3;

    /**
     * @param Waypoint[] $waypoints
     * @return array the .plan document, ready to json_encode
     */
    public static function build(array $waypoints): array
    {
        $items = [];
        foreach (array_values($waypoints) as $i => $w) {
            $items[] = [
                'AMSLAltAboveTerrain' => null,
                'Altitude' => (float) $w->altitude,
                'AltitudeMode' => 1,
                'autoContinue' => true,
                'command' => self::NAV_WAYPOINT,
                'doJumpId' => $i + 1,
                'frame' => self::FRAME_RELATIVE_ALT,
                'params' => [0, 0, 0, null, (float) $w->latitude, (float) $w->longitude, (float) $w->altitude],
                'type' => 'SimpleItem',
            ];
        }

        $first = $waypoints[array_key_first($waypoints)] ?? null;
        $home = $first ? [(float) $first->latitude, (float) $first->longitude, 0] : [0, 0, 0];

        return [
            'fileType' => 'Plan',
            'geoFence' => ['circles' => [], 'polygons' => [], 'version' => 2],
            'groundStation' => 'Mission Deck',
            'mission' => [
                'cruiseSpeed' => 15,
                'firmwareType' => 12,
                'globalPlanAltitudeMode' => 1,
                'hoverSpeed' => 5,
                'items' => $items,
                'plannedHomePosition' => $home,
                'vehicleType' => 2,
                'version' => 2,
            ],
            'rallyPoints' => ['circles' => [], 'points' => [], 'version' => 2],
            'version' => 1,
        ];
    }

    /**
     * Extracts waypoint rows (latitude/longitude/altitude/speed) from a parsed
     * .plan document. Ignores non-waypoint items (surveys, fences, etc.).
     *
     * @return array<int, array{latitude: float, longitude: float, altitude: float, speed: null}>
     */
    public static function parse(array $plan): array
    {
        $rows = [];
        foreach ($plan['mission']['items'] ?? [] as $item) {
            if (($item['command'] ?? null) !== self::NAV_WAYPOINT || ($item['type'] ?? '') !== 'SimpleItem') {
                continue;
            }
            $params = $item['params'] ?? [];
            $lat = $params[4] ?? null;
            $lng = $params[5] ?? null;
            if ($lat === null || $lng === null) {
                continue;
            }
            $rows[] = [
                'latitude' => (float) $lat,
                'longitude' => (float) $lng,
                'altitude' => (float) ($item['Altitude'] ?? $params[6] ?? 50),
                'speed' => null,
            ];
        }

        return $rows;
    }
}
