<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Geofencing Engine & Breach Alert Studio - Laravel 12</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    <style>
        body { background: #0f172a; color: #f8fafc; font-family: system-ui, -apple-system, sans-serif; }
        .card-custom { background: #1e293b; border: 1px solid #334155; border-radius: 14px; }
        #geofenceMap {
            width: 100%;
            height: 440px;
            border-radius: 12px;
            border: 1px solid #334155;
        }
        .table-custom th { background: #1e293b; color: #94a3b8; font-size: 11px; text-transform: uppercase; border-color: #334155; }
        .table-custom td { background: #0f172a; color: #f8fafc; border-color: #334155; vertical-align: middle; }
    </style>
</head>
<body>
    @include('layouts.navigation')

    <div class="container py-2">
        <!-- Title & Subtitle -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold text-light mb-1">
                    <i class="bi bi-geo-alt-fill text-success"></i> Interactive Geofencing Engine & Breach Alert Studio
                </h3>
                <p class="text-secondary mb-0">Define circular virtual boundaries, monitor real-time entry/exit breaches, and audit spatial perimeter violations</p>
            </div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createGeofenceModal">
                <i class="bi bi-plus-circle-fill me-1"></i> Create Virtual Geofence
            </button>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="row g-4 mb-4">
            <!-- Left: Interactive Map -->
            <div class="col-lg-7">
                <div class="card-custom p-3 shadow h-100">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0 text-light fw-bold"><i class="bi bi-map text-primary"></i> Active Spatial Zones (Leaflet)</h6>
                        <span class="text-secondary small">Click map to set geofence center coordinates</span>
                    </div>
                    <div id="geofenceMap"></div>
                </div>
            </div>

            <!-- Right: Geofences List -->
            <div class="col-lg-5">
                <div class="card-custom p-3 shadow h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0 text-light fw-bold"><i class="bi bi-shield-check text-success"></i> Active Virtual Boundaries</h6>
                        <span class="badge bg-secondary">{{ count($geofences) }} Zones</span>
                    </div>

                    <div style="max-height: 380px; overflow-y: auto;">
                        @forelse($geofences as $gf)
                            <div class="p-3 mb-2 rounded border border-secondary" style="background: #0b1120;">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="fw-bold text-light d-flex align-items-center gap-2">
                                        <span style="display:inline-block; width:12px; height:12px; border-radius:50%; background:{{ $gf->color }};"></span>
                                        {{ $gf->name }}
                                    </span>
                                    <span class="badge {{ $gf->is_active ? 'bg-success' : 'bg-secondary' }}">
                                        {{ $gf->is_active ? 'Active' : 'Disabled' }}
                                    </span>
                                </div>
                                <div class="text-secondary small mb-2">
                                    Radius: <strong>{{ $gf->radius_meters }}m</strong> | Coordinates: <code>{{ round($gf->latitude, 4) }}, {{ round($gf->longitude, 4) }}</code>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="badge bg-dark border border-secondary text-info">
                                        {{ $gf->events_count }} Breach Logs
                                    </span>
                                    <div class="d-flex gap-1">
                                        <form action="{{ route('geofences.toggle', $gf->id) }}" method="POST">
                                            @csrf
                                            <button class="btn btn-xs btn-outline-warning py-0 px-2" style="font-size: 11px;">
                                                {{ $gf->is_active ? 'Pause' : 'Activate' }}
                                            </button>
                                        </form>
                                        <form action="{{ route('geofences.destroy', $gf->id) }}" method="POST" onsubmit="return confirm('Delete geofence?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-xs btn-outline-danger py-0 px-2" style="font-size: 11px;"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-4 text-muted">No geofences created yet. Click "Create Virtual Geofence" above.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Breach Events Log Table -->
        <div class="card-custom shadow mb-4">
            <div class="p-3 border-bottom border-secondary d-flex justify-content-between align-items-center">
                <h6 class="mb-0 text-light fw-bold"><i class="bi bi-bell-fill text-danger"></i> Real-Time Geofence Breach Event Logs</h6>
                <form action="{{ route('geofences.clearEvents') }}" method="POST">
                    @csrf
                    <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Clear all breach history?')">
                        <i class="bi bi-trash"></i> Clear Breach Logs
                    </button>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table table-custom mb-0">
                    <thead>
                        <tr>
                            <th>#ID</th>
                            <th>Event Type</th>
                            <th>Zone Name</th>
                            <th>Vehicle ID</th>
                            <th>Driver</th>
                            <th>Speed</th>
                            <th>Coordinates</th>
                            <th>Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentEvents as $ev)
                            <tr>
                                <td>#{{ $ev->id }}</td>
                                <td>
                                    <span class="badge {{ $ev->event_type === 'ENTER' ? 'bg-danger' : 'bg-warning text-dark' }}">
                                        {{ $ev->event_type }}
                                    </span>
                                </td>
                                <td class="fw-bold text-light">{{ $ev->geofence ? $ev->geofence->name : 'Zone #' . $ev->geofence_id }}</td>
                                <td><span class="badge bg-dark border border-secondary text-info">{{ $ev->vehicle_id }}</span></td>
                                <td>{{ $ev->user_name }}</td>
                                <td>{{ $ev->speed }} km/h</td>
                                <td><code>{{ round($ev->latitude, 4) }}, {{ round($ev->longitude, 4) }}</code></td>
                                <td class="text-muted small">{{ $ev->occurred_at ? $ev->occurred_at->format('M d, H:i:s') : '--' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">No breach events recorded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal: Create Geofence -->
    <div class="modal fade" id="createGeofenceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content bg-dark text-light border-secondary">
                <form action="{{ route('geofences.store') }}" method="POST">
                    @csrf
                    <div class="modal-header border-secondary">
                        <h5 class="modal-title"><i class="bi bi-geo-alt-fill text-success"></i> Create Virtual Geofence</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">ZONE NAME</label>
                            <input type="text" name="name" class="form-control bg-dark text-light border-secondary" placeholder="e.g. North Hub Logistics Depot" required>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label text-muted small fw-bold">CENTER LATITUDE</label>
                                <input type="number" step="any" id="modalLat" name="latitude" class="form-control bg-dark text-light border-secondary" value="23.0350" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label text-muted small fw-bold">CENTER LONGITUDE</label>
                                <input type="number" step="any" id="modalLng" name="longitude" class="form-control bg-dark text-light border-secondary" value="72.5750" required>
                            </div>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label text-muted small fw-bold">RADIUS (METERS)</label>
                                <input type="number" name="radius_meters" class="form-control bg-dark text-light border-secondary" value="750" min="50" max="25000" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label text-muted small fw-bold">ZONE COLOR</label>
                                <input type="color" name="color" class="form-control form-control-color bg-dark border-secondary w-100" value="#10b981">
                            </div>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="alert_on_entry" id="chkEntry" value="1" checked>
                            <label class="form-check-label small" for="chkEntry">Alert & Chime on Vehicle Entry</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="alert_on_exit" id="chkExit" value="1" checked>
                            <label class="form-check-label small" for="chkExit">Alert & Chime on Vehicle Exit</label>
                        </div>
                    </div>
                    <div class="modal-footer border-secondary">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save2"></i> Save Geofence</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        const geofences = {!! json_encode($geofences) !!};
        let gMap = L.map('geofenceMap').setView([23.0350, 72.5750], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(gMap);

        geofences.forEach(gf => {
            if (gf.latitude && gf.longitude) {
                L.circle([gf.latitude, gf.longitude], {
                    color: gf.color || '#3b82f6',
                    fillColor: gf.color || '#3b82f6',
                    fillOpacity: 0.22,
                    radius: gf.radius_meters
                }).addTo(gMap).bindPopup(`<strong>${gf.name}</strong><br>Radius: ${gf.radius_meters}m`);
            }
        });

        // Click map to set coordinates in modal
        gMap.on('click', function(e) {
            document.getElementById('modalLat').value = e.latlng.lat.toFixed(6);
            document.getElementById('modalLng').value = e.latlng.lng.toFixed(6);
            const myModal = new bootstrap.Modal(document.getElementById('createGeofenceModal'));
            myModal.show();
        });
    </script>
</body>
</html>
