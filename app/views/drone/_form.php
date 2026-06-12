<?php

declare(strict_types=1);

use app\models\Drone;
use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\Drone $model */
?>
<div class="drone-form col-md-6">

    <?php $form = ActiveForm::begin() ?>

    <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'model')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'serial_number')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'status')->dropDownList(Drone::statusOptions()) ?>

    <?= $form->field($model, 'notes')->textarea(['rows' => 4]) ?>

    <div class="form-group mb-3">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end() ?>

</div>
