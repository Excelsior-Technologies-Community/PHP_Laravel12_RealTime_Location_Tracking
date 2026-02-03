<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Location Tracker</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            color: white;
            padding: 30px 0;
            margin-bottom: 30px;
        }

        .header h1 {
            font-size: 2.8rem;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
        }

        .header p {
            font-size: 1.2rem;
            opacity: 0.9;
        }

        .status-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .location-info {
            display: flex;
            gap: 30px;
        }

        .info-box {
            text-align: center;
        }

        .info-box .label {
            display: block;
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }

        .info-box .value {
            font-size: 1.4rem;
            font-weight: bold;
            color: #333;
        }

        .status-indicator {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .status-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #4CAF50;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                opacity: 1;
            }

            50% {
                opacity: 0.5;
            }

            100% {
                opacity: 1;
            }
        }

        #map {
            height: 600px;
            width: 100%;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            margin-bottom: 25px;
        }

        .controls {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            text-align: center;
        }

        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 50px;
            font-size: 1rem;
            cursor: pointer;
            transition: transform 0.3s ease;
            margin: 0 10px;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .connection-status {
            margin-top: 20px;
            padding: 15px;
            border-radius: 10px;
            background: #f8f9fa;
            display: inline-block;
        }

        @media (max-width: 768px) {
            .status-card {
                flex-direction: column;
                gap: 20px;
            }

            .location-info {
                width: 100%;
                justify-content: space-around;
            }

            .header h1 {
                font-size: 2rem;
            }

            #map {
                height: 400px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>📍 Live Location Tracker</h1>
            <p>Real-time location sharing with live updates</p>
        </div>

        <div class="status-card">
            <div class="location-info">
                <div class="info-box">
                    <span class="label">Latitude</span>
                    <span class="value" id="lat-value">0.0000</span>
                </div>
                <div class="info-box">
                    <span class="label">Longitude</span>
                    <span class="value" id="lng-value">0.0000</span>
                </div>
                <div class="info-box">
                    <span class="label">Accuracy</span>
                    <span class="value" id="accuracy-value">--</span>
                </div>
            </div>
            <div class="status-indicator">
                <div class="status-dot"></div>
                <span id="connection-status">Connecting...</span>
            </div>
        </div>

        <div id="map"></div>

        <div class="controls">
            <button class="btn" onclick="startTracking()" id="start-btn">
                ▶ Start Tracking
            </button>
            <button class="btn" onclick="stopTracking()" id="stop-btn" disabled>
                ⏹ Stop Tracking
            </button>

            <div class="connection-status">
                <span id="ws-status">WebSocket: Connecting...</span>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAG51y1x54XuveIKH4OEcns_v2ZvVJqJfY"></script>
    <script src="https://js.pusher.com/8.2/pusher.min.js"></script>
    <script>
        // Global variables
        let map, marker, watchId;
        let currentLat = 20.5937;
        let currentLng = 78.9629;
        let isTracking = false;

        // Initialize map
        function initMap() {
            map = new google.maps.Map(document.getElementById("map"), {
                zoom: 15,
                center: { lat: currentLat, lng: currentLng },
                mapTypeControl: true,
                streetViewControl: true,
                fullscreenControl: true,
                styles: [
                    {
                        featureType: "all",
                        elementType: "labels.text.fill",
                        stylers: [{ color: "#7c93a3" }]
                    },
                    {
                        featureType: "all",
                        elementType: "labels.text.stroke",
                        stylers: [{ color: "#ffffff" }, { visibility: "on" }]
                    }
                ]
            });

            marker = new google.maps.Marker({
                position: { lat: currentLat, lng: currentLng },
                map: map,
                title: "Your Location",
                animation: google.maps.Animation.DROP,
                icon: {
                    url: 'http://maps.google.com/mapfiles/ms/icons/blue-dot.png'
                }
            });
        }

        // Start tracking
        function startTracking() {
            if (isTracking) return;

            if (navigator.geolocation) {
                watchId = navigator.geolocation.watchPosition(
                    (position) => {
                        currentLat = position.coords.latitude;
                        currentLng = position.coords.longitude;

                        // Update UI
                        document.getElementById('lat-value').textContent = currentLat.toFixed(6);
                        document.getElementById('lng-value').textContent = currentLng.toFixed(6);
                        document.getElementById('accuracy-value').textContent =
                            position.coords.accuracy ? `${Math.round(position.coords.accuracy)}m` : '--';

                        // Update map
                        marker.setPosition({ lat: currentLat, lng: currentLng });
                        map.setCenter({ lat: currentLat, lng: currentLng });

                        // Send to server
                        sendLocation(currentLat, currentLng);
                    },
                    (error) => {
                        alert('Error getting location: ' + error.message);
                        console.error(error);
                    },
                    {
                        enableHighAccuracy: true,
                        maximumAge: 0,
                        timeout: 5000
                    }
                );

                isTracking = true;
                document.getElementById('start-btn').disabled = true;
                document.getElementById('stop-btn').disabled = false;
                document.getElementById('connection-status').textContent = 'Live Tracking';
            } else {
                alert("Geolocation is not supported by this browser.");
            }
        }

        // Stop tracking
        function stopTracking() {
            if (watchId && isTracking) {
                navigator.geolocation.clearWatch(watchId);
                isTracking = false;
                document.getElementById('start-btn').disabled = false;
                document.getElementById('stop-btn').disabled = true;
                document.getElementById('connection-status').textContent = 'Tracking Stopped';
            }
        }

        // Send location to server
        function sendLocation(lat, lng) {
            fetch('/location', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ lat: lat, lng: lng })
            })
                .then(response => response.json())
                .then(data => console.log('Location saved:', data))
                .catch(error => console.error('Error:', error));
        }

        // Initialize Pusher for real-time updates
        function initPusher() {
            // Replace with your Pusher credentials
            const pusher = new Pusher('YOUR_PUSHER_KEY', {
                cluster: 'ap2',
                encrypted: true
            });

            const channel = pusher.subscribe('location-channel');

            channel.bind('App\\Events\\LocationEvent', function (data) {
                document.getElementById('ws-status').textContent =
                    'WebSocket: Receiving live updates';
                document.getElementById('ws-status').style.color = '#4CAF50';

                // If we're not tracking ourselves, update marker
                if (!isTracking) {
                    const lat = parseFloat(data.location.latitude);
                    const lng = parseFloat(data.location.longitude);

                    marker.setPosition({ lat: lat, lng: lng });
                    map.setCenter({ lat: lat, lng: lng });

                    document.getElementById('lat-value').textContent = lat.toFixed(6);
                    document.getElementById('lng-value').textContent = lng.toFixed(6);
                }
            });

            // Connection status updates
            pusher.connection.bind('connected', function () {
                document.getElementById('connection-status').textContent = 'Connected';
                document.getElementById('connection-status').style.color = '#4CAF50';
                document.getElementById('ws-status').textContent = 'WebSocket: Connected';
                document.getElementById('ws-status').style.color = '#4CAF50';
            });

            pusher.connection.bind('disconnected', function () {
                document.getElementById('ws-status').textContent = 'WebSocket: Disconnected';
                document.getElementById('ws-status').style.color = '#f44336';
            });
        }

        // Initialize everything when page loads
        document.addEventListener('DOMContentLoaded', function () {
            initMap();
            initPusher();

            // Ask for permission and start tracking automatically
            setTimeout(() => {
                if (confirm("Start live location tracking?")) {
                    startTracking();
                }
            }, 1000);
        });
    </script>
</body>

</html>