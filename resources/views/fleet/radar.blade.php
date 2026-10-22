<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Real-Time Multi-Vehicle Fleet Radar - Laravel 12</title>

    <!-- Bootstrap 5 & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Leaflet OpenStreetMap CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    <!-- Google Maps API (Optional Fallback) -->
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAG51y1x54XuveIKH4OEcns_v2ZvVJqJfY"></script>

    <style>
        body { background: #0b1120; color: #f8fafc; font-family: system-ui, -apple-system, sans-serif; overflow-x: hidden; }
        .radar-card { background: #1e293b; border: 1px solid #334155; border-radius: 14px; }
        .vehicle-card {
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: 10px;
            padding: 12px 14px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .vehicle-card:hover {
            border-color: #38bdf8;
            background: #172554;
        }
        .vehicle-card.active {
            border-color: #38bdf8;
            box-shadow: 0 0 0 2px rgba(56, 189, 248, 0.4);
            background: #1e3a8a;
        }
        #mapContainer {
            width: 100%;
            height: calc(100vh - 165px);
            min-height: 580px;
            border-radius: 14px;
            border: 1px solid #334155;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5);
            position: relative;
        }
        #leafletMap, #googleMap {
            width: 100%;
            height: 100%;
            border-radius: 14px;
        }
        .leaflet-container { background: #090d16 !important; }
        /* Vehicle Map Marker Styling */
        .vehicle-marker-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            border: 2px solid #ffffff;
            box-shadow: 0 4px 12px rgba(0,0,0,0.4);
            font-size: 18px;
            color: #ffffff;
            transition: transform 0.3s ease;
        }
        .marker-bike { background: #f59e0b; }
        .marker-car { background: #0284c7; }
        .marker-truck { background: #10b981; }
        .marker-ambulance { background: #ef4444; }
        .pulse-ring {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #22c55e;
            box-shadow: 0 0 0 rgba(34, 197, 94, 0.7);
            animation: pulse-green 1.6s infinite;
        }
        @keyframes pulse-green {
            0% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(34, 197, 94, 0); }
            100% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
        }
        .toast-bubble {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 9999;
            max-width: 380px;
        }
    </style>
</head>
<body>
    @include('layouts.navigation')

    <div class="container-fluid px-3">
        <!-- Top Toolbar -->
        <div class="radar-card p-3 mb-3 shadow d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div class="d-flex align-items-center gap-3">
                <div>
                    <h5 class="mb-0 text-light fw-bold">
                        <i class="bi bi-radar text-primary me-1"></i> Fleet Telemetry Radar
                    </h5>
                    <small class="text-secondary">Simultaneous real-time monitoring of all vehicles & geofences</small>
                </div>
            </div>

            <!-- Controls: Dual Map Switcher + Simulation Tick -->
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <!-- Dual Map Engine Switcher (Feature 6) -->
                <div class="btn-group btn-group-sm" role="group">
                    <button type="button" class="btn btn-outline-info active" id="btnMapOsm" onclick="switchMapEngine('osm')">
                        <i class="bi bi-map-fill"></i> Leaflet (OSM)
                    </button>
                    <button type="button" class="btn btn-outline-info" id="btnMapDark" onclick="switchMapEngine('dark')">
                        <i class="bi bi-moon-stars-fill"></i> CartoDB Dark
                    </button>
                    <button type="button" class="btn btn-outline-info" id="btnMapGoogle" onclick="switchMapEngine('google')">
                        <i class="bi bi-google"></i> Google Maps
                    </button>
                </div>

                <!-- Simulation Dispatcher Button -->
                <button class="btn btn-sm btn-warning fw-bold px-3" id="btnSimulate" onclick="triggerSimulatorTick()">
                    <i class="bi bi-play-circle-fill me-1"></i> Simulate Fleet Movement (1 Ping)
                </button>

                <!-- Auto-Pilot Toggle -->
                <button class="btn btn-sm btn-outline-secondary text-light" id="btnAutoSim" onclick="toggleAutoSim()">
                    <i class="bi bi-arrow-repeat"></i> Auto-Pilot: <span id="autoSimText">OFF</span>
                </button>
            </div>
        </div>

        <!-- Main Body: Sidebar + Map -->
        <div class="row g-3">
            <!-- Left Sidebar: Fleet List & Status Grid (Feature 1) -->
            <div class="col-lg-3 col-md-4">
                <div class="radar-card p-3 shadow h-100" style="max-height: calc(100vh - 165px); overflow-y: auto;">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0 text-info fw-bold"><i class="bi bi-truck-front-fill me-1"></i> Active Fleet Inventory</h6>
                        <span class="badge bg-secondary" id="fleetCountBadge">{{ count($vehicles) }} Vehicles</span>
                    </div>

                    <div id="fleetListContainer">
                        @foreach($vehicles as $v)
                            <div class="vehicle-card" id="card-{{ $v['vehicle_id'] }}" onclick="focusVehicle('{{ $v['vehicle_id'] }}', {{ $v['latitude'] }}, {{ $v['longitude'] }})">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="fw-bold text-light">
                                        @if($v['vehicle_type'] === 'bike') 🛵 @elseif($v['vehicle_type'] === 'truck') 🚚 @elseif($v['vehicle_type'] === 'ambulance') 🚑 @else 🚗 @endif
                                        {{ $v['vehicle_id'] }}
                                    </span>
                                    <span class="badge bg-{{ $v['status_color'] }}" style="font-size: 11px;">
                                        @if($v['motion_status'] === 'Moving') <span class="pulse-ring me-1"></span> @endif
                                        {{ $v['motion_status'] }}
                                    </span>
                                </div>
                                <div class="text-secondary small mb-1">{{ $v['user_name'] }}</div>
                                <div class="d-flex justify-content-between text-muted" style="font-size: 11px;">
                                    <span><i class="bi bi-speedometer2 text-info"></i> <strong class="text-light" id="speed-{{ $v['vehicle_id'] }}">{{ $v['speed'] }}</strong> km/h</span>
                                    <span><i class="bi bi-compass text-warning"></i> <strong class="text-light" id="head-{{ $v['vehicle_id'] }}">{{ $v['heading'] }}°</strong></span>
                                    <span><i class="bi bi-battery-charging text-success"></i> <strong class="text-light">{{ $v['battery_level'] }}%</strong></span>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="border-top border-secondary pt-3 mt-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small text-muted fw-bold">ACTIVE GEOFENCES</span>
                            <a href="{{ route('geofences.index') }}" class="btn btn-xs btn-outline-info py-0 px-2" style="font-size: 11px;">Manage</a>
                        </div>
                        @foreach($geofences as $gf)
                            <div class="d-flex align-items-center gap-2 mb-1 small text-light">
                                <span style="display:inline-block; width:10px; height:10px; border-radius:50%; background:{{ $gf->color }};"></span>
                                <span>{{ $gf->name }}</span>
                                <span class="text-muted ms-auto" style="font-size: 10px;">{{ $gf->radius_meters }}m</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Right: Real-time Map Canvas (Leaflet OSM + Google Maps) -->
            <div class="col-lg-9 col-md-8">
                <div id="mapContainer">
                    <!-- Leaflet Container -->
                    <div id="leafletMap"></div>
                    <!-- Google Maps Container -->
                    <div id="googleMap" style="display: none;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Floating Audio/Toast Alert for Geofence Breaches -->
    <div id="breachToast" class="toast-bubble alert alert-danger border-danger shadow" style="display: none;">
        <div class="d-flex align-items-center">
            <i class="bi bi-exclamation-triangle-fill fs-3 text-danger me-2"></i>
            <div>
                <strong id="breachTitle">Geofence Alert!</strong>
                <div id="breachBody" class="small">Vehicle entered a restricted zone.</div>
            </div>
        </div>
    </div>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
        let currentMapEngine = 'osm'; // osm, dark, google
        let leafletMap = null;
        let googleMapObj = null;
        let leafletMarkers = {};
        let googleMarkers = {};
        let activeTileLayer = null;
        let autoSimInterval = null;
        let followVehicleId = null;

        const initialLat = 23.0350;
        const initialLng = 72.5750;
        const geofences = {!! json_encode($geofences) !!};
        let fleetData = {!! json_encode($vehicles) !!};

        // 1. Initialize Leaflet OpenStreetMap (Default Engine)
        function initLeafletMap() {
            leafletMap = L.map('leafletMap', {
                center: [initialLat, initialLng],
                zoom: 13,
                zoomControl: true
            });

            // Standard OSM Tile Layer
            setLeafletTiles('osm');

            // Draw Geofences on Leaflet
            geofences.forEach(gf => {
                if (gf.type === 'circle' && gf.latitude && gf.longitude) {
                    L.circle([gf.latitude, gf.longitude], {
                        color: gf.color || '#3b82f6',
                        fillColor: gf.color || '#3b82f6',
                        fillOpacity: 0.18,
                        radius: gf.radius_meters
                    }).addTo(leafletMap).bindPopup(`<strong>Geofence:</strong> ${gf.name}<br>Radius: ${gf.radius_meters}m`);
                }
            });

            renderLeafletFleetMarkers(fleetData);
        }

        function setLeafletTiles(theme) {
            if (activeTileLayer) {
                leafletMap.removeLayer(activeTileLayer);
            }
            if (theme === 'dark') {
                activeTileLayer = L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
                    attribution: '&copy; CartoDB &copy; OpenStreetMap',
                    maxZoom: 19
                }).addTo(leafletMap);
            } else {
                activeTileLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap contributors',
                    maxZoom: 19
                }).addTo(leafletMap);
            }
        }

        function renderLeafletFleetMarkers(vehicles) {
            vehicles.forEach(v => {
                const iconClass = v.vehicle_type === 'bike' ? 'marker-bike bi-bicycle' : 
                                 (v.vehicle_type === 'truck' ? 'marker-truck bi-truck' : 
                                 (v.vehicle_type === 'ambulance' ? 'marker-ambulance bi-hospital' : 'marker-car bi-car-front-fill'));

                const html = `
                    <div class="vehicle-marker-icon ${iconClass}" style="transform: rotate(${v.heading}deg);">
                        <i class="bi ${iconClass.split(' ')[1]}"></i>
                    </div>
                `;

                const customIcon = L.divIcon({
                    html: html,
                    className: 'custom-leaflet-marker',
                    iconSize: [44, 44],
                    iconAnchor: [22, 22]
                });

                if (leafletMarkers[v.vehicle_id]) {
                    leafletMarkers[v.vehicle_id].setLatLng([v.latitude, v.longitude]);
                    leafletMarkers[v.vehicle_id].setIcon(customIcon);
                } else {
                    const marker = L.marker([v.latitude, v.longitude], { icon: customIcon }).addTo(leafletMap);
                    marker.bindPopup(`
                        <div class="text-dark">
                            <strong>${v.user_name} (${v.vehicle_id})</strong><br>
                            Type: ${v.vehicle_type.toUpperCase()}<br>
                            Speed: <strong>${v.speed} km/h</strong><br>
                            Heading: <strong>${v.heading}°</strong><br>
                            Battery: ${v.battery_level}%<br>
                            Status: <span class="badge bg-${v.status_color}">${v.motion_status}</span>
                        </div>
                    `);
                    leafletMarkers[v.vehicle_id] = marker;
                }
            });
        }

        // 2. Initialize Google Maps (Engine Switcher Feature 6)
        function initGoogleMap() {
            if (googleMapObj) return;
            const container = document.getElementById('googleMap');
            googleMapObj = new google.maps.Map(container, {
                center: { lat: initialLat, lng: initialLng },
                zoom: 13,
                mapTypeId: 'roadmap',
            });

            // Draw Geofences on Google Maps
            geofences.forEach(gf => {
                if (gf.type === 'circle' && gf.latitude && gf.longitude) {
                    new google.maps.Circle({
                        strokeColor: gf.color || '#3b82f6',
                        strokeOpacity: 0.8,
                        strokeWeight: 2,
                        fillColor: gf.color || '#3b82f6',
                        fillOpacity: 0.2,
                        map: googleMapObj,
                        center: { lat: gf.latitude, lng: gf.longitude },
                        radius: gf.radius_meters
                    });
                }
            });

            renderGoogleFleetMarkers(fleetData);
        }

        function renderGoogleFleetMarkers(vehicles) {
            if (!googleMapObj) return;
            vehicles.forEach(v => {
                const pos = { lat: v.latitude, lng: v.longitude };
                if (googleMarkers[v.vehicle_id]) {
                    googleMarkers[v.vehicle_id].setPosition(pos);
                } else {
                    const marker = new google.maps.Marker({
                        position: pos,
                        map: googleMapObj,
                        title: `${v.user_name} (${v.vehicle_id})`
                    });
                    googleMarkers[v.vehicle_id] = marker;
                }
            });
        }

        // 3. Switch Engine
        function switchMapEngine(engine) {
            currentMapEngine = engine;
            document.getElementById('btnMapOsm').className = engine === 'osm' ? 'btn btn-outline-info active' : 'btn btn-outline-info';
            document.getElementById('btnMapDark').className = engine === 'dark' ? 'btn btn-outline-info active' : 'btn btn-outline-info';
            document.getElementById('btnMapGoogle').className = engine === 'google' ? 'btn btn-outline-info active' : 'btn btn-outline-info';

            const lDiv = document.getElementById('leafletMap');
            const gDiv = document.getElementById('googleMap');

            if (engine === 'google') {
                lDiv.style.display = 'none';
                gDiv.style.display = 'block';
                initGoogleMap();
            } else {
                gDiv.style.display = 'none';
                lDiv.style.display = 'block';
                setLeafletTiles(engine);
                setTimeout(() => { leafletMap.invalidateSize(); }, 200);
            }
        }

        // 4. Focus & Follow a Vehicle
        function focusVehicle(vId, lat, lng) {
            followVehicleId = vId;
            document.querySelectorAll('.vehicle-card').forEach(el => el.classList.remove('active'));
            const card = document.getElementById(`card-${vId}`);
            if (card) card.classList.add('active');

            if (currentMapEngine === 'google' && googleMapObj) {
                googleMapObj.panTo({ lat: lat, lng: lng });
                googleMapObj.setZoom(16);
            } else if (leafletMap) {
                leafletMap.flyTo([lat, lng], 16, { animate: true, duration: 1.2 });
            }
        }

        // 5. Trigger Simulator Tick
        async function triggerSimulatorTick() {
            try {
                const res = await fetch("{{ route('api.fleet.simulator.tick') }}", {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                });
                const data = await res.json();
                fleetData = data.vehicles;

                // Update markers on active map
                renderLeafletFleetMarkers(fleetData);
                if (googleMapObj) renderGoogleFleetMarkers(fleetData);

                // Update sidebar metrics
                fleetData.forEach(v => {
                    const spEl = document.getElementById(`speed-${v.vehicle_id}`);
                    const hdEl = document.getElementById(`head-${v.vehicle_id}`);
                    if (spEl) spEl.textContent = v.speed;
                    if (hdEl) hdEl.textContent = v.heading + '°';
                });

                // Follow camera if vehicle locked
                if (followVehicleId) {
                    const matched = fleetData.find(x => x.vehicle_id === followVehicleId);
                    if (matched) {
                        if (currentMapEngine === 'google' && googleMapObj) {
                            googleMapObj.panTo({ lat: matched.latitude, lng: matched.longitude });
                        } else if (leafletMap) {
                            leafletMap.panTo([matched.latitude, matched.longitude]);
                        }
                    }
                }
            } catch (e) {
                console.error(e);
            }
        }

        function toggleAutoSim() {
            const txt = document.getElementById('autoSimText');
            if (autoSimInterval) {
                clearInterval(autoSimInterval);
                autoSimInterval = null;
                txt.textContent = 'OFF';
                document.getElementById('btnAutoSim').className = 'btn btn-sm btn-outline-secondary text-light';
            } else {
                triggerSimulatorTick();
                autoSimInterval = setInterval(triggerSimulatorTick, 2500);
                txt.textContent = 'ON (2.5s)';
                document.getElementById('btnAutoSim').className = 'btn btn-sm btn-success fw-bold';
            }
        }

        // Procedural Web Audio API Chime for Geofence Breaches
        function playChime(type) {
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = 'sine';
                osc.frequency.setValueAtTime(type === 'ENTER' ? 587.33 : 440, ctx.currentTime);
                osc.frequency.exponentialRampToValueAtTime(type === 'ENTER' ? 880 : 330, ctx.currentTime + 0.3);
                gain.gain.setValueAtTime(0.2, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.4);
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start();
                osc.stop(ctx.currentTime + 0.45);
            } catch (err) {}
        }

        document.addEventListener('DOMContentLoaded', () => {
            initLeafletMap();
        });
    </script>
</body>
</html>
