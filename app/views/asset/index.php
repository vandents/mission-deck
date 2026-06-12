<?php

declare(strict_types=1);

use app\models\Asset;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Assets';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="asset-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Add Asset', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'tableOptions' => ['class' => 'table table-striped align-middle'],
        'columns' => [
            [
                'attribute' => 'name',
                'format' => 'raw',
                'value' => fn (Asset $m) => Html::a(Html::encode($m->name), ['view', 'id' => $m->id]),
            ],
            ['attribute' => 'type', 'value' => fn (Asset $m) => $m->typeLabel()],
            'latitude',
            'longitude',
            ['class' => ActionColumn::class],
        ],
    ]) ?>

</div>
