<?php

declare(strict_types=1);

use app\models\Drone;
use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var array<string, int> $droneCounts drone status => count */
/** @var int $assetCount */

$this->title = 'Dashboard';

$totalDrones = array_sum($droneCounts);
$cards = [
    ['label' => 'Total Drones', 'value' => $totalDrones, 'url' => ['/drone/index'], 'class' => 'text-bg-dark'],
    ['label' => 'Available', 'value' => $droneCounts[Drone::STATUS_AVAILABLE] ?? 0, 'url' => ['/drone/index'], 'class' => 'text-bg-success'],
    ['label' => 'In Mission', 'value' => $droneCounts[Drone::STATUS_IN_MISSION] ?? 0, 'url' => ['/drone/index'], 'class' => 'text-bg-primary'],
    ['label' => 'Assets', 'value' => $assetCount, 'url' => ['/asset/index'], 'class' => 'text-bg-secondary'],
];
?>
<div class="site-index">

    <h1 class="mb-4">Mission Deck</h1>

    <div class="row g-3">
        <?php foreach ($cards as $card): ?>
            <div class="col-6 col-lg-3">
                <a href="<?= Url::to($card['url']) ?>" class="text-decoration-none">
                    <div class="card <?= $card['class'] ?>">
                        <div class="card-body">
                            <div class="display-5 fw-bold"><?= $card['value'] ?></div>
                            <div><?= Html::encode($card['label']) ?></div>
                        </div>
                    </div>
                </a>
            </div>
        <?php endforeach ?>
    </div>

    <div class="mt-4">
        <?= Html::a('Add Drone', ['/drone/create'], ['class' => 'btn btn-outline-success me-2']) ?>
        <?= Html::a('Add Asset', ['/asset/create'], ['class' => 'btn btn-outline-secondary']) ?>
    </div>

</div>
