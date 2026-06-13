<?php

declare(strict_types=1);

use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\web\View;

/** @var yii\web\View $this */

$this->title = 'Live Ops';
$this->params['breadcrumbs'][] = $this->title;

$this->registerCssFile('https://unpkg.com/leaflet@1.9.4/dist/leaflet.css');
$this->registerJsFile('https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', ['position' => View::POS_HEAD]);

$liveUrl = Json::htmlEncode(Url::to(['live']));

$js = <<<JS
(function () {
    const liveUrl = {$liveUrl};
    const map = L.map('ops-map').setView([29.21, -81.02], 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    const markers = {};
    const listEl = document.getElementById('drone-list');
    let centered = false;

    function refresh() {
        fetch(liveUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(function (rows) {
                const seen = {};
                let html = '';
                rows.forEach(function (row) {
                    seen[row.drone_id] = true;
                    const lat = parseFloat(row.latitude), lng = parseFloat(row.longitude);
                    const battery = row.battery_pct === null ? '—' : row.battery_pct + '%';
                    const popup = '<b>' + row.drone + '</b><br>Alt ' + row.altitude + ' m AGL<br>Battery ' + battery + '<br>' + (row.status || '');
                    if (markers[row.drone_id]) {
                        markers[row.drone_id].setLatLng([lat, lng]).setPopupContent(popup);
                    } else {
                        markers[row.drone_id] = L.marker([lat, lng]).addTo(map).bindPopup(popup);
                    }
                    html += '<tr><td>' + row.drone + '</td><td>' + battery + '</td><td>' + (row.status || '—') + '</td></tr>';
                });
                Object.keys(markers).forEach(function (id) {
                    if (!seen[id]) { map.removeLayer(markers[id]); delete markers[id]; }
                });
                listEl.innerHTML = html || '<tr><td colspan="3" class="text-muted">No drones reporting. Start the simulator.</td></tr>';
                if (!centered && rows.length) {
                    map.fitBounds(L.featureGroup(Object.values(markers)).getBounds(), { padding: [50, 50] });
                    centered = true;
                }
            })
            .catch(function () { /* ignore transient poll errors */ });
    }

    refresh();
    setInterval(refresh, 2000);
})();
JS;

$this->registerJs($js, View::POS_READY);
?>
<div class="ops-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <p class="text-muted">Live drone positions, refreshed every 2 seconds. Fly a mission with the simulator to see drones appear.</p>

    <div class="row g-3">
        <div class="col-lg-8">
            <div id="ops-map" style="height: 540px;" class="border rounded"></div>
        </div>
        <div class="col-lg-4">
            <table class="table table-sm align-middle">
                <thead>
                    <tr><th>Drone</th><th>Battery</th><th>Status</th></tr>
                </thead>
                <tbody id="drone-list">
                    <tr><td colspan="3" class="text-muted">Loading…</td></tr>
                </tbody>
            </table>
        </div>
    </div>

</div>
