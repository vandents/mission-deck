<?php

declare(strict_types=1);

use app\models\Asset;
use app\models\Drone;
use app\models\Mission;
use yii\bootstrap5\ActiveForm;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\Mission $model */

$drones = ArrayHelper::map(Drone::find()->orderBy('name')->all(), 'id', 'name');
$assets = ArrayHelper::map(Asset::find()->orderBy('name')->all(), 'id', 'name');
?>
<div class="mission-form col-md-6">

    <?php $form = ActiveForm::begin() ?>

    <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'drone_id')->dropDownList($drones, ['prompt' => '— Unassigned —']) ?>

    <?= $form->field($model, 'asset_id')->dropDownList($assets, ['prompt' => '— None —']) ?>

    <?= $form->field($model, 'status')->dropDownList(Mission::statusOptions()) ?>

    <?= $form->field($model, 'notes')->textarea(['rows' => 4]) ?>

    <div class="form-group mb-3">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end() ?>

</div>
