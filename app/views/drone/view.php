<?php

declare(strict_types=1);

use yii\helpers\Html;
use yii\widgets\DetailView;

/** @var yii\web\View $this */
/** @var app\models\Drone $model */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Fleet', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="drone-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Update', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a('Delete', ['delete', 'id' => $model->id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => 'Are you sure you want to delete this drone?',
                'method' => 'post',
            ],
        ]) ?>
    </p>

    <?= DetailView::widget([
        'model' => $model,
        'options' => ['class' => 'table table-striped detail-view'],
        'attributes' => [
            'name',
            'model',
            'serial_number',
            ['attribute' => 'status', 'value' => $model->statusLabel()],
            'notes:ntext',
            'created_at:datetime',
            'updated_at:datetime',
        ],
    ]) ?>

</div>
