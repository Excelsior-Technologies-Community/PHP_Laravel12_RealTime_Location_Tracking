<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fleet Telemetry Analytics & Mileage Dashboard - Laravel 12</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        body { background: #0f172a; color: #f8fafc; font-family: system-ui, -apple-system, sans-serif; }
        .kpi-card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 14px;
            padding: 22px;
            transition: all 0.25s ease;
        }
        .kpi-card:hover {
            border-color: #38bdf8;
            box-shadow: 0 10px 25px rgba(56, 189, 248, 0.1);
        }
        .chart-card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 14px;
            padding: 20px;
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
                    <i class="bi bi-bar-chart-line-fill text-info"></i> Fleet Telemetry Analytics & Mileage Performance
                </h3>
                <p class="text-secondary mb-0">High-level spatial intelligence, velocity distributions, and geofence compliance reporting</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('fleet.analytics.export') }}" class="btn btn-success">
                    <i class="bi bi-file-earmark-excel me-1"></i> Export Telemetry CSV
                </a>
            </div>
        </div>

        <!-- 4 Top KPI Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="kpi-card shadow">
                    <span class="text-secondary small fw-bold"><i class="bi bi-geo-fill text-primary"></i> TOTAL GPS PINGS</span>
                    <div class="fs-2 fw-bold text-light mt-1">{{ number_format($totalPings) }}</div>
                    <span class="text-muted small">Recorded coordinate points</span>
                </div>
            </div>

            <div class="col-md-3">
                <div class="kpi-card shadow">
                    <span class="text-secondary small fw-bold"><i class="bi bi-truck text-success"></i> ACTIVE VEHICLES</span>
                    <div class="fs-2 fw-bold text-success mt-1">{{ $activeVehiclesCount }}</div>
                    <span class="text-muted small">Connected fleet assets</span>
                </div>
            </div>

            <div class="col-md-3">
                <div class="kpi-card shadow">
                    <span class="text-secondary small fw-bold"><i class="bi bi-speedometer2 text-warning"></i> AVERAGE SPEED</span>
                    <div class="fs-2 fw-bold text-warning mt-1">{{ $avgSpeed }} <span class="fs-5">km/h</span></div>
                    <span class="text-muted small">Peak: {{ $maxSpeed }} km/h</span>
                </div>
            </div>

            <div class="col-md-3">
                <div class="kpi-card shadow">
                    <span class="text-secondary small fw-bold"><i class="bi bi-shield-exclamation text-danger"></i> GEOFENCE BREACHES</span>
                    <div class="fs-2 fw-bold text-danger mt-1">{{ $totalBreaches }}</div>
                    <span class="text-muted small">Across {{ $totalGeofences }} active zones</span>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row g-3 mb-4">
            <!-- 14-Day Activity Trend -->
            <div class="col-lg-8">
                <div class="chart-card shadow h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0 text-light fw-bold"><i class="bi bi-graph-up text-primary"></i> Daily Fleet GPS Transmission Volume (Last 14 Days)</h6>
                        <span class="badge bg-secondary">Daily Throughput</span>
                    </div>
                    <div style="height: 260px;">
                        <canvas id="trendChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Speed Distribution Doughnut -->
            <div class="col-lg-4">
                <div class="chart-card shadow h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0 text-light fw-bold"><i class="bi bi-pie-chart-fill text-warning"></i> Speed Distribution</h6>
                        <span class="badge bg-secondary">Velocity Clusters</span>
                    </div>
                    <div style="height: 260px; position: relative;">
                        <canvas id="speedChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Vehicle Telemetry Ranking Table -->
        <div class="card-custom shadow mb-4">
            <div class="p-3 border-bottom border-secondary d-flex justify-content-between align-items-center">
                <h6 class="mb-0 text-light fw-bold"><i class="bi bi-trophy-fill text-warning"></i> Fleet Vehicles Performance & Activity Ranking</h6>
                <span class="badge bg-secondary">{{ count($vehicleStats) }} Assets Indexed</span>
            </div>
            <div class="table-responsive">
                <table class="table table-custom mb-0">
                    <thead>
                        <tr>
                            <th>Vehicle ID</th>
                            <th>Driver Name</th>
                            <th>Vehicle Type</th>
                            <th class="text-center">Recorded Pings</th>
                            <th class="text-center">Avg Speed</th>
                            <th class="text-center">Max Recorded Speed</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($vehicleStats as $st)
                            <tr>
                                <td class="fw-bold text-light">
                                    <span class="badge bg-dark border border-secondary text-info">{{ $st->vehicle_id }}</span>
                                </td>
                                <td>{{ $st->user_name }}</td>
                                <td><span class="badge bg-secondary">{{ strtoupper($st->vehicle_type) }}</span></td>
                                <td class="text-center fw-bold">{{ number_format($st->pings) }}</td>
                                <td class="text-center text-info">{{ round($st->avg_speed, 1) }} km/h</td>
                                <td class="text-center text-warning">{{ $st->top_speed }} km/h</td>
                                <td><span class="badge bg-success">Active Online</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">No vehicle statistics recorded.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 1. Trend Line Chart
        const ctxTrend = document.getElementById('trendChart').getContext('2d');
        new Chart(ctxTrend, {
            type: 'line',
            data: {
                labels: {!! json_encode($timelineLabels) !!},
                datasets: [{
                    label: 'GPS Coordinates Logged',
                    data: {!! json_encode($timelineData) !!},
                    borderColor: '#38bdf8',
                    backgroundColor: 'rgba(56, 189, 248, 0.15)',
                    fill: true,
                    tension: 0.35,
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { ticks: { color: '#94a3b8' }, grid: { color: '#334155' } },
                    y: { ticks: { color: '#94a3b8' }, grid: { color: '#334155' } }
                },
                plugins: {
                    legend: { labels: { color: '#f8fafc' } }
                }
            }
        });

        // 2. Speed Clusters Doughnut
        const ctxSpeed = document.getElementById('speedChart').getContext('2d');
        new Chart(ctxSpeed, {
            type: 'doughnut',
            data: {
                labels: {!! json_encode(array_keys($speedBuckets)) !!},
                datasets: [{
                    data: {!! json_encode(array_values($speedBuckets)) !!},
                    backgroundColor: ['#64748b', '#0ea5e9', '#10b981', '#f59e0b'],
                    borderWidth: 2,
                    borderColor: '#1e293b'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { color: '#f8fafc' } }
                }
            }
        });
    </script>
</body>
</html>
