<?php

declare(strict_types=1);

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\Drone $model */

$this->title = 'Add Drone';
$this->params['breadcrumbs'][] = ['label' => 'Fleet', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="drone-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', ['model' => $model]) ?>

</div>
