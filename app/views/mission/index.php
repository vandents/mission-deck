<?php

declare(strict_types=1);

use app\models\Mission;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Missions';
$this->params['breadcrumbs'][] = $this->title;

$badgeClasses = [
    Mission::STATUS_PLANNED => 'text-bg-secondary',
    Mission::STATUS_ACTIVE => 'text-bg-primary',
    Mission::STATUS_COMPLETED => 'text-bg-success',
    Mission::STATUS_ABORTED => 'text-bg-danger',
];
?>
<div class="mission-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Plan Mission', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'tableOptions' => ['class' => 'table table-striped align-middle'],
        'columns' => [
            [
                'attribute' => 'name',
                'format' => 'raw',
                'value' => fn (Mission $m) => Html::a(Html::encode($m->name), ['view', 'id' => $m->id]),
            ],
            [
                'label' => 'Drone',
                'value' => fn (Mission $m) => $m->drone->name ?? '—',
            ],
            [
                'label' => 'Asset',
                'value' => fn (Mission $m) => $m->asset->name ?? '—',
            ],
            [
                'attribute' => 'status',
                'format' => 'raw',
                'value' => fn (Mission $m) => Html::tag(
                    'span',
                    Html::encode($m->statusLabel()),
                    ['class' => 'badge ' . ($badgeClasses[$m->status] ?? 'text-bg-light')],
                ),
            ],
            ['class' => ActionColumn::class],
        ],
    ]) ?>

</div>
