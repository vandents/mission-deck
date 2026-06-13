<?php

declare(strict_types=1);

use app\models\Waypoint;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\web\View;

/** @var yii\web\View $this */
/** @var app\models\Mission $model */

$this->title = 'Plan: ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Missions', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Plan';

$this->registerCssFile('https://unpkg.com/leaflet@1.9.4/dist/leaflet.css');
$this->registerJsFile('https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', ['position' => View::POS_HEAD]);

$existing = array_map(static fn (Waypoint $w) => [
    'latitude' => (float) $w->latitude,
    'longitude' => (float) $w->longitude,
    'altitude' => (float) $w->altitude,
    'speed' => $w->speed === null ? '' : (float) $w->speed,
], $model->waypoints);

// Center on the target asset if it has coordinates, else a default (Daytona Beach, FL).
$center = $model->asset && $model->asset->latitude !== null
    ? [(float) $model->asset->latitude, (float) $model->asset->longitude]
    : [29.21, -81.02];

$config = Json::htmlEncode([
    'waypoints' => $existing,
    'center' => $center,
    'saveUrl' => Url::to(['save-waypoints', 'id' => $model->id]),
    'csrfToken' => Yii::$app->request->csrfToken,
]);

$js = <<<JS
(function () {
    const cfg = {$config};
    const map = L.map('mission-map');
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    const path = L.polyline([], { color: '#0d6efd', weight: 3 }).addTo(map);
    const listEl = document.getElementById('waypoint-list');
    const statusEl = document.getElementById('save-status');
    let points = []; // { lat, lng, altitude, speed, marker }

    function addPoint(lat, lng, altitude, speed) {
        const marker = L.marker([lat, lng], { draggable: true }).addTo(map);
        const wp = { lat: lat, lng: lng, altitude: altitude, speed: speed, marker: marker };
        marker.on('dragend', function () {
            const p = marker.getLatLng();
            wp.lat = p.lat;
            wp.lng = p.lng;
            render();
        });
        points.push(wp);
        render();
    }

    function render() {
        path.setLatLngs(points.map(p => [p.lat, p.lng]));
        let rows = '';
        points.forEach(function (p, i) {
            p.marker.bindTooltip(String(i + 1), { permanent: true, direction: 'top' });
            rows +=
                '<tr>' +
                '<td>' + (i + 1) + '</td>' +
                '<td>' + p.lat.toFixed(5) + ', ' + p.lng.toFixed(5) + '</td>' +
                '<td><input type="number" class="form-control form-control-sm" data-i="' + i + '" data-f="altitude" value="' + p.altitude + '" style="width:6rem"></td>' +
                '<td><input type="number" class="form-control form-control-sm" data-i="' + i + '" data-f="speed" value="' + p.speed + '" style="width:6rem"></td>' +
                '<td><button type="button" class="btn btn-sm btn-outline-danger" data-del="' + i + '">&times;</button></td>' +
                '</tr>';
        });
        listEl.innerHTML = rows;
    }

    // Edit altitude/speed inline.
    listEl.addEventListener('change', function (e) {
        const i = e.target.getAttribute('data-i');
        const f = e.target.getAttribute('data-f');
        if (i !== null && f) {
            points[i][f] = e.target.value;
        }
    });

    // Delete a waypoint.
    listEl.addEventListener('click', function (e) {
        const i = e.target.getAttribute('data-del');
        if (i !== null) {
            map.removeLayer(points[i].marker);
            points.splice(i, 1);
            render();
        }
    });

    // Click the map to append a waypoint (50 m default altitude).
    map.on('click', function (e) {
        addPoint(e.latlng.lat, e.latlng.lng, 50, '');
    });

    document.getElementById('save-route').addEventListener('click', function () {
        const body = JSON.stringify({
            waypoints: points.map(p => ({
                latitude: p.lat,
                longitude: p.lng,
                altitude: p.altitude,
                speed: p.speed
            }))
        });
        statusEl.textContent = 'Saving…';
        fetch(cfg.saveUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': cfg.csrfToken },
            body: body
        }).then(r => r.json()).then(function (data) {
            statusEl.textContent = data.success ? ('Saved ' + data.count + ' waypoint(s).') : ('Error: ' + data.error);
            statusEl.className = data.success ? 'text-success' : 'text-danger';
        }).catch(function (err) {
            statusEl.textContent = 'Error: ' + err;
            statusEl.className = 'text-danger';
        });
    });

    // Load existing waypoints, or center on the default.
    if (cfg.waypoints.length) {
        cfg.waypoints.forEach(w => addPoint(w.latitude, w.longitude, w.altitude, w.speed));
        map.fitBounds(path.getBounds(), { padding: [40, 40] });
    } else {
        map.setView(cfg.center, 13);
    }
})();
JS;

$this->registerJs($js, View::POS_READY);
?>
<div class="mission-plan">

    <h1><?= Html::encode($this->title) ?></h1>
    <p class="text-muted">Click the map to add waypoints. Drag markers to reposition. Set altitude (m AGL) and speed (m/s) per point, then save.</p>

    <div class="row g-3">
        <div class="col-lg-7">
            <div id="mission-map" style="height: 520px;" class="border rounded"></div>
        </div>
        <div class="col-lg-5">
            <table class="table table-sm align-middle">
                <thead>
                    <tr><th>#</th><th>Lat, Lng</th><th>Alt</th><th>Speed</th><th></th></tr>
                </thead>
                <tbody id="waypoint-list"></tbody>
            </table>
            <button type="button" id="save-route" class="btn btn-success">Save Route</button>
            <?= Html::a('Back to mission', ['view', 'id' => $model->id], ['class' => 'btn btn-link']) ?>
            <span id="save-status" class="ms-2"></span>
        </div>
    </div>

</div>
