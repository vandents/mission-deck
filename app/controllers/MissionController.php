<?php

declare(strict_types=1);

namespace app\controllers;

use app\models\Mission;
use app\models\Waypoint;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

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

    protected function findModel(int $id): Mission
    {
        if (($model = Mission::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('Mission not found.');
    }
}
