<?php

declare(strict_types=1);

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;

/**
 * Live operations view: a map of every drone's most recent reported position.
 * Browser-facing, so it uses the normal login session (unlike ApiController).
 */
class OpsController extends Controller
{
    /** A drone is considered "live" if it has reported within this many seconds. */
    private const FRESH_SECONDS = 120;

    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    ['allow' => true, 'roles' => ['@']],
                ],
            ],
        ];
    }

    public function actionIndex(): string
    {
        return $this->render('index');
    }

    /**
     * Latest telemetry ping per drone, for the live map to poll: GET /ops/live
     */
    public function actionLive(): Response
    {
        $since = time() - self::FRESH_SECONDS;

        // Latest ping per drone. Keyed off the auto-increment id (strictly
        // monotonic) rather than recorded_at, which is second-granularity and
        // would tie when several pings land in the same second.
        $rows = Yii::$app->db->createCommand('
            SELECT d.name AS drone, t.drone_id, t.mission_id, t.latitude, t.longitude,
                   t.altitude, t.heading, t.battery_pct, t.status, t.recorded_at
            FROM telemetry t
            JOIN (
                SELECT drone_id, MAX(id) AS mx
                FROM telemetry
                GROUP BY drone_id
            ) latest ON latest.mx = t.id
            JOIN drone d ON d.id = t.drone_id
            WHERE t.recorded_at >= :since
            ORDER BY d.name
        ', [':since' => $since])->queryAll();

        return $this->asJson($rows);
    }
}
