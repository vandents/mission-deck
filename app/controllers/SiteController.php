<?php

declare(strict_types=1);

namespace app\controllers;

use Yii;
use app\models\Asset;
use app\models\Drone;
use app\models\LoginForm;
use yii\base\Security;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\ErrorAction;
use yii\web\Response;

class SiteController extends Controller
{
    public function __construct(
        $id,
        $module,
        private readonly Security $security,
        $config = [],
    ) {
        parent::__construct($id, $module, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['index', 'logout'],
                'rules' => [
                    [
                        'actions' => ['index', 'logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions(): array
    {
        return [
            'error' => [
                'class' => ErrorAction::class,
            ],
        ];
    }

    /**
     * Operations dashboard.
     */
    public function actionIndex(): string
    {
        $rows = Drone::find()
            ->select(['status', 'n' => 'COUNT(*)'])
            ->groupBy('status')
            ->asArray()
            ->all();

        return $this->render('index', [
            'droneCounts' => array_map('intval', array_column($rows, 'n', 'status')),
            'assetCount' => (int) Asset::find()->count(),
        ]);
    }

    /**
     * Login action.
     */
    public function actionLogin(): Response|string
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm($this->security);

        if ($model->load($this->request->post()) && $model->login()) {
            return $this->goBack();
        }

        $model->password = '';

        return $this->render('login', ['model' => $model]);
    }

    /**
     * Logout action.
     */
    public function actionLogout(): Response
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }
}
