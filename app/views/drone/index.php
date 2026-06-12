<?php

declare(strict_types=1);

use app\models\Drone;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Fleet';
$this->params['breadcrumbs'][] = $this->title;

$badgeClasses = [
    Drone::STATUS_AVAILABLE => 'text-bg-success',
    Drone::STATUS_IN_MISSION => 'text-bg-primary',
    Drone::STATUS_MAINTENANCE => 'text-bg-warning',
    Drone::STATUS_RETIRED => 'text-bg-secondary',
];
?>
<div class="drone-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Add Drone', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'tableOptions' => ['class' => 'table table-striped align-middle'],
        'columns' => [
            [
                'attribute' => 'name',
                'format' => 'raw',
                'value' => fn (Drone $m) => Html::a(Html::encode($m->name), ['view', 'id' => $m->id]),
            ],
            'model',
            'serial_number',
            [
                'attribute' => 'status',
                'format' => 'raw',
                'value' => fn (Drone $m) => Html::tag(
                    'span',
                    Html::encode($m->statusLabel()),
                    ['class' => 'badge ' . ($badgeClasses[$m->status] ?? 'text-bg-light')],
                ),
            ],
            ['class' => ActionColumn::class],
        ],
    ]) ?>

</div>
