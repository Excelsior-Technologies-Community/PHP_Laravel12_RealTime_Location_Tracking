<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historical Trip Replay & Route Trail Studio - Laravel 12</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    <style>
        body { background: #0f172a; color: #f8fafc; font-family: system-ui, -apple-system, sans-serif; }
        .replay-card { background: #1e293b; border: 1px solid #334155; border-radius: 14px; }
        #replayMap {
            width: 100%;
            height: calc(100vh - 280px);
            min-height: 480px;
            border-radius: 12px;
            border: 1px solid #334155;
        }
        .player-bar {
            background: #0b1120;
            border: 1px solid #334155;
            border-radius: 12px;
            padding: 16px 20px;
        }
        .hud-stat {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 10px;
            padding: 10px 16px;
            text-align: center;
        }
        .replay-marker {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: #f59e0b;
            border: 2px solid #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            color: #ffffff;
            box-shadow: 0 4px 15px rgba(245, 158, 11, 0.6);
            transition: transform 0.2s linear;
        }
    </style>
</head>
<body>
    @include('layouts.navigation')

    <div class="container-fluid px-4 py-2">
        <!-- Top Toolbar -->
        <div class="replay-card p-3 mb-3 shadow d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h4 class="mb-1 text-light fw-bold">
                    <i class="bi bi-rewind-btn-fill text-warning me-1"></i> Historical Trip Replay & Route Trail Playback Studio
                </h4>
                <small class="text-secondary">Audit breadcrumb trajectories, stops, and driving speed variations step-by-step</small>
            </div>

            <div class="d-flex align-items-center gap-2">
                <!-- Vehicle Selector -->
                <select id="vehicleSelector" class="form-select form-select-sm bg-dark text-light border-secondary" style="width: 180px;" onchange="changeSelection()">
                    @foreach($vehicles as $v)
                        <option value="{{ $v }}" {{ $selectedVehicle === $v ? 'selected' : '' }}>{{ $v }}</option>
                    @endforeach
                </select>

                <!-- Trip Selector -->
                <select id="tripSelector" class="form-select form-select-sm bg-dark text-light border-secondary" style="width: 220px;" onchange="loadTripData()">
                    @foreach($trips as $t)
                        <option value="{{ $t }}" {{ $selectedTrip === $t ? 'selected' : '' }}>{{ $t }}</option>
                    @endforeach
                </select>

                <button class="btn btn-sm btn-primary" onclick="loadTripData()">
                    <i class="bi bi-arrow-clockwise"></i> Load Route
                </button>
            </div>
        </div>

        <!-- HUD Telemetry Counters Row -->
        <div class="row g-3 mb-3">
            <div class="col-md-3">
                <div class="hud-stat">
                    <span class="text-secondary small fw-bold">CUMULATIVE DISTANCE</span>
                    <div class="fs-4 fw-bold text-light mt-1"><span id="hudDistance">0.00</span> km</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="hud-stat">
                    <span class="text-secondary small fw-bold">CURRENT SPEED</span>
                    <div class="fs-4 fw-bold text-warning mt-1"><span id="hudSpeed">0</span> km/h</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="hud-stat">
                    <span class="text-secondary small fw-bold">TIMESTAMP AT POINT</span>
                    <div class="fs-4 fw-bold text-info mt-1" id="hudTime">--:--:--</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="hud-stat">
                    <span class="text-secondary small fw-bold">WAYPOINT PROGRESS</span>
                    <div class="fs-4 fw-bold text-success mt-1"><span id="hudIndex">0</span> / <span id="hudTotal">0</span></div>
                </div>
            </div>
        </div>

        <!-- Leaflet Map Container -->
        <div class="position-relative mb-3">
            <div id="replayMap"></div>
        </div>

        <!-- Interactive Playback Controller Bar -->
        <div class="player-bar shadow d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-primary px-3 fw-bold" id="btnPlayPause" onclick="togglePlay()">
                    <i class="bi bi-play-fill" id="playIcon"></i> Play
                </button>
                <button class="btn btn-outline-secondary text-light" onclick="resetReplay()">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </button>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-info active" id="btnSpd1" onclick="setSpeed(1)">1x</button>
                    <button class="btn btn-outline-info" id="btnSpd2" onclick="setSpeed(2)">2x</button>
                    <button class="btn btn-outline-info" id="btnSpd5" onclick="setSpeed(5)">5x</button>
                </div>
            </div>

            <!-- Scrubbing Range Slider -->
            <div class="flex-grow-1 mx-3">
                <input type="range" class="form-range" id="replaySlider" min="0" max="100" value="0" oninput="onSliderScrub(this.value)">
            </div>

            <div class="text-secondary small">
                Status: <strong class="text-light" id="playbackStatus">Ready to replay</strong>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
        let replayMap = null;
        let routePolyline = null;
        let activePolyline = null;
        let vehicleMarker = null;
        let waypoints = [];
        let currentIndex = 0;
        let isPlaying = false;
        let playInterval = null;
        let speedMultiplier = 1;

        function initMap() {
            replayMap = L.map('replayMap').setView([23.0350, 72.5750], 13);
            L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
                attribution: '&copy; CartoDB &copy; OpenStreetMap',
                maxZoom: 19
            }).addTo(replayMap);

            loadTripData();
        }

        async function loadTripData() {
            const vId = document.getElementById('vehicleSelector').value;
            const tId = document.getElementById('tripSelector').value;

            try {
                const res = await fetch(`/api/trip/waypoints?vehicle_id=${vId}&trip_id=${tId}`);
                const data = await res.json();
                waypoints = data.waypoints;

                document.getElementById('hudTotal').textContent = waypoints.length;
                document.getElementById('hudDistance').textContent = data.total_distance_km;

                renderRouteOnMap();
                resetReplay();
            } catch (err) {
                console.error(err);
            }
        }

        function renderRouteOnMap() {
            if (routePolyline) replayMap.removeLayer(routePolyline);
            if (activePolyline) replayMap.removeLayer(activePolyline);
            if (vehicleMarker) replayMap.removeLayer(vehicleMarker);

            if (waypoints.length === 0) return;

            const latlngs = waypoints.map(w => [w.lat, w.lng]);

            // Draw full background path (translucent blue)
            routePolyline = L.polyline(latlngs, { color: '#38bdf8', weight: 4, opacity: 0.4 }).addTo(replayMap);

            // Draw active traversed path (bright amber)
            activePolyline = L.polyline([], { color: '#f59e0b', weight: 5, opacity: 0.95 }).addTo(replayMap);

            // Start & End markers
            L.circleMarker(latlngs[0], { radius: 7, color: '#22c55e', fillOpacity: 1 }).addTo(replayMap).bindPopup('Trip Start Point');
            L.circleMarker(latlngs[latlngs.length - 1], { radius: 7, color: '#ef4444', fillOpacity: 1 }).addTo(replayMap).bindPopup('Trip End Point');

            // Vehicle marker
            const iconHtml = `
                <div class="replay-marker" id="replayCarIcon">
                    <i class="bi bi-car-front-fill"></i>
                </div>
            `;
            const icon = L.divIcon({ html: iconHtml, className: '', iconSize: [38, 38], iconAnchor: [19, 19] });
            vehicleMarker = L.marker(latlngs[0], { icon: icon }).addTo(replayMap);

            replayMap.fitBounds(routePolyline.getBounds(), { padding: [50, 50] });
        }

        function togglePlay() {
            if (isPlaying) {
                pauseReplay();
            } else {
                startReplay();
            }
        }

        function startReplay() {
            if (waypoints.length === 0) return;
            if (currentIndex >= waypoints.length - 1) {
                currentIndex = 0;
            }

            isPlaying = true;
            document.getElementById('playIcon').className = 'bi bi-pause-fill';
            document.getElementById('btnPlayPause').innerHTML = '<i class="bi bi-pause-fill"></i> Pause';
            document.getElementById('playbackStatus').textContent = `Playing (${speedMultiplier}x speed)`;

            const stepMs = Math.round(500 / speedMultiplier);
            clearInterval(playInterval);
            playInterval = setInterval(advanceStep, stepMs);
        }

        function pauseReplay() {
            isPlaying = false;
            clearInterval(playInterval);
            document.getElementById('btnPlayPause').innerHTML = '<i class="bi bi-play-fill"></i> Play';
            document.getElementById('playbackStatus').textContent = 'Paused';
        }

        function advanceStep() {
            if (currentIndex >= waypoints.length - 1) {
                pauseReplay();
                document.getElementById('playbackStatus').textContent = 'Trip replay finished';
                return;
            }

            currentIndex++;
            updatePlayerState(currentIndex);
        }

        function updatePlayerState(index) {
            const pt = waypoints[index];
            if (!pt) return;

            // Update Vehicle Marker Position & Bearing
            vehicleMarker.setLatLng([pt.lat, pt.lng]);
            const iconEl = document.getElementById('replayCarIcon');
            if (iconEl) iconEl.style.transform = `rotate(${pt.heading}deg)`;

            // Update Traversed Polyline
            const traversed = waypoints.slice(0, index + 1).map(w => [w.lat, w.lng]);
            activePolyline.setLatLngs(traversed);

            // Update Telemetry HUD
            document.getElementById('hudSpeed').textContent = pt.speed;
            document.getElementById('hudDistance').textContent = pt.distance_so_far_km;
            document.getElementById('hudTime').textContent = pt.timestamp;
            document.getElementById('hudIndex').textContent = index + 1;

            // Update Slider
            const pct = Math.round((index / (waypoints.length - 1)) * 100);
            document.getElementById('replaySlider').value = pct;
        }

        function onSliderScrub(val) {
            if (waypoints.length === 0) return;
            const targetIndex = Math.round((val / 100) * (waypoints.length - 1));
            currentIndex = targetIndex;
            updatePlayerState(currentIndex);
        }

        function resetReplay() {
            pauseReplay();
            currentIndex = 0;
            if (waypoints.length > 0) {
                updatePlayerState(0);
            }
        }

        function setSpeed(mult) {
            speedMultiplier = mult;
            document.getElementById('btnSpd1').className = mult === 1 ? 'btn btn-outline-info active' : 'btn btn-outline-info';
            document.getElementById('btnSpd2').className = mult === 2 ? 'btn btn-outline-info active' : 'btn btn-outline-info';
            document.getElementById('btnSpd5').className = mult === 5 ? 'btn btn-outline-info active' : 'btn btn-outline-info';

            if (isPlaying) {
                startReplay(); // restart interval with new timing
            }
        }

        function changeSelection() {
            window.location.href = `{{ route('trip.replay') }}?vehicle_id=${document.getElementById('vehicleSelector').value}`;
        }

        document.addEventListener('DOMContentLoaded', initMap);
    </script>
</body>
</html>
