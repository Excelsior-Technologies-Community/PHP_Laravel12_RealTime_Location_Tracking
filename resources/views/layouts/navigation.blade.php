<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm mb-3 border-bottom border-secondary">
    <div class="container-fluid px-4">
        <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="{{ route('fleet.radar') }}">
            <span class="badge bg-primary px-2 py-1"><i class="bi bi-radar"></i> GPS</span>
            <span>Real-Time Fleet Radar</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navContent">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 gap-1">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('fleet.radar*') ? 'active text-primary fw-semibold' : '' }}" href="{{ route('fleet.radar') }}">
                        <i class="bi bi-crosshair text-danger"></i> Multi-Vehicle Fleet Radar
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('geofences.*') ? 'active text-primary fw-semibold' : '' }}" href="{{ route('geofences.index') }}">
                        <i class="bi bi-geo-alt-fill text-success"></i> Geofencing Studio
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('trip.replay*') ? 'active text-primary fw-semibold' : '' }}" href="{{ route('trip.replay') }}">
                        <i class="bi bi-rewind-btn-fill text-warning"></i> Historical Trip Replay
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('fleet.analytics*') ? 'active text-primary fw-semibold' : '' }}" href="{{ route('fleet.analytics') }}">
                        <i class="bi bi-bar-chart-line-fill text-info"></i> Telemetry Analytics
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('dashboard') ? 'active text-primary fw-semibold' : '' }}" href="{{ route('dashboard') }}">
                        <i class="bi bi-pin-map-fill"></i> Location Logs History
                    </a>
                </li>
            </ul>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-secondary"><i class="bi bi-broadcast"></i> Live GPS Engine</span>
                <span class="badge bg-success"><i class="bi bi-check-circle-fill"></i> 4 Vehicles Active</span>
            </div>
        </div>
    </div>
</nav>
