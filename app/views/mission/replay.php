<?php

declare(strict_types=1);

use app\models\Telemetry;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\web\View;

/** @var yii\web\View $this */
/** @var app\models\Mission $model */
/** @var Telemetry[] $track */

$this->title = 'Replay: ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Missions', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Replay';
?>
<div class="mission-replay">

    <h1><?= Html::encode($this->title) ?></h1>

<?php if (!$track): ?>
    <div class="alert alert-info">No recorded telemetry for this mission yet. Fly it with the simulator, then come back to replay.</div>
    <?= Html::a('Back to mission', ['view', 'id' => $model->id], ['class' => 'btn btn-link']) ?>
<?php else: ?>
<?php
$points = array_map(static fn (Telemetry $p) => [
    'lat' => (float) $p->latitude,
    'lng' => (float) $p->longitude,
    'alt' => (float) $p->altitude,
    'battery' => $p->battery_pct,
    'status' => $p->status,
    't' => $p->recorded_at,
], $track);

$this->registerCssFile('https://unpkg.com/leaflet@1.9.4/dist/leaflet.css');
$this->registerJsFile('https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', ['position' => View::POS_HEAD]);

$config = Json::htmlEncode(['points' => $points]);
$js = <<<JS
(function () {
    const pts = {$config}.points;
    const map = L.map('replay-map');
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    const line = L.polyline(pts.map(p => [p.lat, p.lng]), { color: '#0d6efd', weight: 3 }).addTo(map);
    map.fitBounds(line.getBounds(), { padding: [40, 40] });
    const marker = L.marker([pts[0].lat, pts[0].lng]).addTo(map);

    const scrub = document.getElementById('scrub');
    const info = document.getElementById('info');
    const playBtn = document.getElementById('play');
    let i = 0, timer = null;

    function show(idx) {
        const p = pts[idx];
        marker.setLatLng([p.lat, p.lng]);
        scrub.value = idx;
        const batt = p.battery === null ? '—' : p.battery + '%';
        const time = new Date(p.t * 1000).toLocaleTimeString();
        info.textContent = (idx + 1) + '/' + pts.length + '  ·  alt ' + p.alt + ' m  ·  batt ' + batt + '  ·  ' + (p.status || '') + '  ·  ' + time;
    }

    function stop() { clearInterval(timer); timer = null; playBtn.textContent = 'Play'; }

    function play() {
        if (timer) { stop(); return; }
        if (i >= pts.length - 1) { i = 0; }
        playBtn.textContent = 'Pause';
        timer = setInterval(function () {
            if (i >= pts.length - 1) { stop(); return; }
            i++; show(i);
        }, 250);
    }

    playBtn.addEventListener('click', play);
    scrub.addEventListener('input', function () { stop(); i = parseInt(scrub.value, 10); show(i); });
    show(0);
})();
JS;
$this->registerJs($js, View::POS_READY);
?>
    <p class="text-muted">Recorded flight track — <?= count($track) ?> telemetry points.</p>

    <div class="d-flex align-items-center gap-3 mb-2">
        <button id="play" type="button" class="btn btn-primary btn-sm" style="min-width: 5rem;">Play</button>
        <input id="scrub" type="range" min="0" max="<?= count($track) - 1 ?>" value="0" class="form-range" style="max-width: 320px;">
        <span id="info" class="small text-body-secondary"></span>
    </div>

    <div id="replay-map" style="height: 520px;" class="border rounded"></div>

    <p class="mt-3"><?= Html::a('Back to mission', ['view', 'id' => $model->id], ['class' => 'btn btn-link']) ?></p>
<?php endif ?>

</div>
