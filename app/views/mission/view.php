<?php

declare(strict_types=1);

use yii\helpers\Html;
use yii\widgets\DetailView;

/** @var yii\web\View $this */
/** @var app\models\Mission $model */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Missions', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="mission-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Plan Route', ['plan', 'id' => $model->id], ['class' => 'btn btn-success']) ?>
        <?= Html::a('Replay', ['replay', 'id' => $model->id], ['class' => 'btn btn-outline-primary']) ?>
        <?= Html::a('Export .plan', ['export-plan', 'id' => $model->id], ['class' => 'btn btn-outline-secondary']) ?>
        <?= Html::a('Update', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a('Delete', ['delete', 'id' => $model->id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => 'Are you sure you want to delete this mission?',
                'method' => 'post',
            ],
        ]) ?>
    </p>

    <?= Html::beginForm(['import-plan', 'id' => $model->id], 'post', [
        'enctype' => 'multipart/form-data',
        'class' => 'd-flex align-items-center gap-2 mb-3',
    ]) ?>
        <label class="small text-body-secondary mb-0">Import QGroundControl&nbsp;.plan:</label>
        <input type="file" name="plan" accept=".plan,application/json" class="form-control form-control-sm" style="max-width: 18rem;" required>
        <?= Html::submitButton('Import', ['class' => 'btn btn-sm btn-outline-success']) ?>
    <?= Html::endForm() ?>

    <?= DetailView::widget([
        'model' => $model,
        'options' => ['class' => 'table table-striped detail-view'],
        'attributes' => [
            'name',
            ['attribute' => 'status', 'value' => $model->statusLabel()],
            ['label' => 'Drone', 'value' => $model->drone->name ?? '—'],
            ['label' => 'Asset', 'value' => $model->asset->name ?? '—'],
            'notes:ntext',
            'created_at:datetime',
        ],
    ]) ?>

    <h2 class="h4 mt-4">Waypoints</h2>
    <?php if ($model->waypoints): ?>
        <table class="table table-sm table-striped">
            <thead>
                <tr><th>#</th><th>Latitude</th><th>Longitude</th><th>Alt (m AGL)</th><th>Speed (m/s)</th></tr>
            </thead>
            <tbody>
                <?php foreach ($model->waypoints as $wp): ?>
                    <tr>
                        <td><?= Html::encode((string) $wp->seq) ?></td>
                        <td><?= Html::encode((string) $wp->latitude) ?></td>
                        <td><?= Html::encode((string) $wp->longitude) ?></td>
                        <td><?= Html::encode((string) $wp->altitude) ?></td>
                        <td><?= Html::encode($wp->speed === null ? '—' : (string) $wp->speed) ?></td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="text-muted">No waypoints yet. The map-based planner is coming in the next step.</p>
    <?php endif ?>

</div>
