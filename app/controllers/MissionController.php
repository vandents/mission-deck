<?php

declare(strict_types=1);

namespace app\controllers;

use app\components\QgcPlan;
use app\models\Mission;
use app\models\Telemetry;
use app\models\Waypoint;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\UploadedFile;

class MissionController extends Controller
{
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    ['allow' => true, 'roles' => ['@']],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['post'],
                    'save-waypoints' => ['post'],
                    'import-plan' => ['post'],
                ],
            ],
        ];
    }

    public function actionIndex(): string
    {
        $dataProvider = new ActiveDataProvider([
            // Eager-load drone and asset to avoid a query per row in the grid.
            'query' => Mission::find()->with(['drone', 'asset']),
            'sort' => ['defaultOrder' => ['created_at' => SORT_DESC]],
        ]);

        return $this->render('index', ['dataProvider' => $dataProvider]);
    }

    public function actionView(int $id): string
    {
        return $this->render('view', ['model' => $this->findModel($id)]);
    }

    /**
     * Interactive map planner for a mission's flight path.
     */
    public function actionPlan(int $id): string
    {
        return $this->render('plan', ['model' => $this->findModel($id)]);
    }

    /**
     * Replaces a mission's waypoints from a JSON body. Full-replace semantics:
     * the posted list becomes the mission's complete, re-sequenced flight path.
     */
    public function actionSaveWaypoints(int $id): Response
    {
        $mission = $this->findModel($id);
        $payload = json_decode($this->request->getRawBody(), true);
        $rows = $payload['waypoints'] ?? [];

        $db = Yii::$app->db;
        $transaction = $db->beginTransaction();
        try {
            Waypoint::deleteAll(['mission_id' => $mission->id]);

            foreach (array_values($rows) as $i => $row) {
                $waypoint = new Waypoint([
                    'mission_id' => $mission->id,
                    'seq' => $i + 1,
                    'latitude' => $row['latitude'] ?? null,
                    'longitude' => $row['longitude'] ?? null,
                    'altitude' => $row['altitude'] ?? 50,
                    'speed' => ($row['speed'] ?? '') === '' ? null : $row['speed'],
                ]);
                if (!$waypoint->save()) {
                    throw new \RuntimeException('Invalid waypoint at position ' . ($i + 1));
                }
            }

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            Yii::$app->response->statusCode = 422;
            return $this->asJson(['success' => false, 'error' => $e->getMessage()]);
        }

        return $this->asJson(['success' => true, 'count' => count($rows)]);
    }

    public function actionCreate(): Response|string
    {
        $model = new Mission();

        if ($model->load($this->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', "Mission '{$model->name}' created.");
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', ['model' => $model]);
    }

    public function actionUpdate(int $id): Response|string
    {
        $model = $this->findModel($id);

        if ($model->load($this->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', ['model' => $model]);
    }

    public function actionDelete(int $id): Response
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Downloads the mission as a QGroundControl .plan file.
     */
    public function actionExportPlan(int $id): Response
    {
        $mission = $this->findModel($id);
        $json = json_encode(QgcPlan::build($mission->waypoints), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $filename = preg_replace('/[^A-Za-z0-9_-]+/', '_', $mission->name) . '.plan';

        return Yii::$app->response->sendContentAsFile($json, $filename, ['mimeType' => 'application/json']);
    }

    /**
     * Replaces a mission's waypoints from an uploaded QGroundControl .plan file.
     */
    public function actionImportPlan(int $id): Response
    {
        $mission = $this->findModel($id);
        $file = UploadedFile::getInstanceByName('plan');
        $plan = $file ? json_decode((string) file_get_contents($file->tempName), true) : null;

        if (!is_array($plan) || ($plan['fileType'] ?? '') !== 'Plan') {
            Yii::$app->session->setFlash('error', 'That is not a valid QGroundControl .plan file.');
            return $this->redirect(['view', 'id' => $id]);
        }

        $rows = QgcPlan::parse($plan);
        $transaction = Yii::$app->db->beginTransaction();
        try {
            Waypoint::deleteAll(['mission_id' => $mission->id]);
            foreach ($rows as $i => $row) {
                $waypoint = new Waypoint(array_merge($row, ['mission_id' => $mission->id, 'seq' => $i + 1]));
                if (!$waypoint->save()) {
                    throw new \RuntimeException('Invalid waypoint at position ' . ($i + 1));
                }
            }
            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            Yii::$app->session->setFlash('error', 'Import failed: ' . $e->getMessage());
            return $this->redirect(['view', 'id' => $id]);
        }

        Yii::$app->session->setFlash('success', 'Imported ' . count($rows) . ' waypoint(s) from the .plan file.');
        return $this->redirect(['view', 'id' => $id]);
    }

    /**
     * Replays a mission's recorded telemetry track on a map.
     */
    public function actionReplay(int $id): string
    {
        $mission = $this->findModel($id);
        $track = Telemetry::find()
            ->where(['mission_id' => $id])
            ->orderBy(['id' => SORT_ASC])
            ->all();

        return $this->render('replay', ['model' => $mission, 'track' => $track]);
    }

    protected function findModel(int $id): Mission
    {
        if (($model = Mission::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('Mission not found.');
    }
}
