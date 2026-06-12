<?php

declare(strict_types=1);

use app\models\Asset;
use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\Asset $model */
?>
<div class="asset-form col-md-6">

    <?php $form = ActiveForm::begin() ?>

    <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'type')->dropDownList(Asset::typeOptions()) ?>

    <div class="row">
        <div class="col"><?= $form->field($model, 'latitude')->textInput() ?></div>
        <div class="col"><?= $form->field($model, 'longitude')->textInput() ?></div>
    </div>

    <?= $form->field($model, 'description')->textarea(['rows' => 4]) ?>

    <div class="form-group mb-3">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end() ?>

</div>
