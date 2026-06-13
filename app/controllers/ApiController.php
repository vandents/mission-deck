<?php

declare(strict_types=1);

namespace app\controllers;

use app\models\Mission;
use app\models\Telemetry;
use app\models\Waypoint;
use Yii;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\UnauthorizedHttpException;

/**
 * Machine-facing JSON API for the flight simulator (and any external client).
 * Authenticated with a bearer token, not a browser session, so CSRF is off.
 */
class ApiController extends Controller
{
    public $enableCsrfValidation = false;

    public function behaviors(): array
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'telemetry' => ['post'],
                    'mission' => ['get'],
                ],
            ],
        ];
    }

    /**
     * Ingests one telemetry ping: POST /api/telemetry
     */
    public function actionTelemetry(): Response
    {
        $this->authenticate();

        $ping = new Telemetry();
        $ping->load((array) json_decode($this->request->getRawBody(), true), '');
        $ping->recorded_at = time();

        if ($ping->save()) {
            return $this->asJson(['success' => true, 'id' => $ping->id]);
        }

        Yii::$app->response->statusCode = 422;
        return $this->asJson(['success' => false, 'errors' => $ping->errors]);
    }

    /**
     * Returns a mission's flight path for the simulator: GET /api/mission?id=X
     */
    public function actionMission(int $id): Response
    {
        $this->authenticate();

        $mission = Mission::findOne($id);
        if ($mission === null) {
            throw new NotFoundHttpException('Mission not found.');
        }

        return $this->asJson([
            'id' => $mission->id,
            'name' => $mission->name,
            'drone_id' => $mission->drone_id,
            'waypoints' => array_map(static fn (Waypoint $w) => [
                'seq' => $w->seq,
                'latitude' => (float) $w->latitude,
                'longitude' => (float) $w->longitude,
                'altitude' => (float) $w->altitude,
                'speed' => $w->speed === null ? null : (float) $w->speed,
            ], $mission->waypoints),
        ]);
    }

    private function authenticate(): void
    {
        $expected = getenv('API_TOKEN') ?: ($_SERVER['API_TOKEN'] ?? '');
        $provided = preg_replace('/^Bearer\s+/i', '', $this->request->headers->get('Authorization', ''));

        if ($expected === '' || !hash_equals((string) $expected, (string) $provided)) {
            throw new UnauthorizedHttpException('Invalid or missing API token.');
        }
    }
}
