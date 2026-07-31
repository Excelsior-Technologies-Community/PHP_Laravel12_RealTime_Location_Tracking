<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Laravel Real-Time Location Tracker</title>

    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAG51y1x54XuveIKH4OEcns_v2ZvVJqJfY"></script>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">


    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">


    <style>
        body {
            background: #f4f7fb;
            font-family: 'Segoe UI', sans-serif;
        }


        .header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 35px;
            border-radius: 20px;
            margin-bottom: 25px;
        }


        .card {
            border: none;
            border-radius: 18px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, .08);
        }


        .status-online {
            color: #198754;
            font-weight: bold;
        }


        .status-offline {
            color: #dc3545;
            font-weight: bold;
        }


        #map {

            width: 100%;
            height: 500px;
            border-radius: 20px;

        }


        .info-box {

            padding: 20px;
            text-align: center;

        }


        .info-box h6 {

            color: #777;

        }


        .info-box h3 {

            font-weight: bold;

        }


        .btn-custom {

            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;

        }



        .btn-custom:hover {

            color: white;
            opacity: .9;

        }
    </style>


</head>


<body>


    <div class="container py-4">


        {{-- Success Message --}}
        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            <strong>Success!</strong> {{ session('success') }}

            <button type="button"
                class="btn-close"
                data-bs-dismiss="alert">
            </button>
        </div>
        @endif

        {{-- Validation Errors --}}
        @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>

            <strong>Error!</strong>

            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>

            <button type="button"
                class="btn-close"
                data-bs-dismiss="alert">
            </button>
        </div>
        @endif

        <div class="header">

            <h1>
                <i class="bi bi-geo-alt-fill"></i>
                Real-Time Location Tracker
            </h1>


            <p>
                Laravel 12 + WebSocket Live Tracking Dashboard
            </p>


        </div>



        <!-- Status Section -->


        <div class="card mb-4">

            <div class="card-body">


                <div class="row">


                    <div class="col-md-3">

                        <div class="info-box">

                            <h6>Latitude</h6>

                            <h3 id="lat-value">
                                0.000000
                            </h3>

                        </div>

                    </div>



                    <div class="col-md-3">

                        <div class="info-box">

                            <h6>Longitude</h6>

                            <h3 id="lng-value">
                                0.000000
                            </h3>

                        </div>

                    </div>



                    <div class="col-md-3">

                        <div class="info-box">

                            <h6>Accuracy</h6>

                            <h3 id="accuracy-value">
                                --
                            </h3>

                        </div>

                    </div>



                    <div class="col-md-3">

                        <div class="info-box">


                            <h6>Status</h6>


                            <h3 id="live-status"
                                class="status-offline">

                                Offline

                            </h3>


                        </div>


                    </div>



                </div>


            </div>

        </div>





        <!-- Map -->


        <div class="card mb-4">


            <div class="card-body">


                <div id="map"></div>


            </div>


        </div>





        <!-- Controls -->


        <div class="card mb-4">


            <div class="card-body text-center">


                <button
                    class="btn btn-custom px-4"
                    onclick="startTracking()"
                    id="start-btn">

                    <i class="bi bi-play-fill"></i>
                    Start Tracking

                </button>



                <button
                    class="btn btn-danger px-4"
                    onclick="stopTracking()"
                    id="stop-btn"
                    disabled>

                    <i class="bi bi-stop-fill"></i>
                    Stop Tracking

                </button>


                <a href="{{ route('location.export') }}"
                    class="btn btn-success px-4">


                    <i class="bi bi-file-earmark-excel"></i>

                    Export CSV


                </a>



                <form action="{{ route('history.deleteAll') }}"
                    method="POST"
                    class="d-inline">


                    @csrf

                    @method('DELETE')


                    <button
                        class="btn btn-dark px-4"
                        onclick="return confirm('Delete all history?')">


                        <i class="bi bi-trash"></i>

                        Delete All


                    </button>


                </form>



            </div>


        </div>






        <!-- Search -->


        <div class="card mb-4">


            <div class="card-body">


                <form method="GET">


                    <div class="input-group">


                        <input
                            type="text"
                            name="search"
                            class="form-control"
                            placeholder="Search user, latitude, longitude, status..."
                            value="{{ request('search') }}">



                        <button class="btn btn-primary">

                            <i class="bi bi-search"></i>

                            Search

                        </button>


                        <a href="{{ url('/') }}"
                            class="btn btn-secondary">

                            Reset

                        </a>



                    </div>


                </form>


            </div>


        </div>





        <!-- History Table -->


        <div class="card">


            <div class="card-header bg-dark text-white">

                <h5 class="mb-0">

                    <i class="bi bi-clock-history"></i>

                    Location History

                </h5>

            </div>



            <div class="card-body">


                <div class="table-responsive">


                    <table class="table table-hover">


                        <thead>

                            <tr>

                                <th>#</th>

                                <th>User</th>

                                <th>Latitude</th>

                                <th>Longitude</th>

                                <th>Status</th>

                                <th>Last Seen</th>

                                <th>Action</th>


                            </tr>


                        </thead>



                        <tbody>


                            @forelse($locations as $location)


                            <tr>


                                <td>
                                    {{ ($locations->currentPage() - 1) * $locations->perPage() + $loop->iteration }}
                                </td>


                                <td>
                                    {{ $location->user_name }}
                                </td>


                                <td>
                                    {{ $location->latitude }}
                                </td>


                                <td>
                                    {{ $location->longitude }}
                                </td>



                                <td>


                                    @if($location->status=="Online")


                                    <span class="badge bg-success">

                                        Online

                                    </span>


                                    @else


                                    <span class="badge bg-danger">

                                        Offline

                                    </span>


                                    @endif


                                </td>



                                <td>

                                    {{ $location->last_seen }}

                                </td>



                                <td>


                                    <form method="POST"
                                        action="{{ route('history.delete',$location->id) }}">


                                        @csrf

                                        @method('DELETE')


                                        <button
                                            class="btn btn-sm btn-danger"
                                            onclick="return confirm('Delete this record?')">


                                            <i class="bi bi-trash"></i>


                                        </button>


                                    </form>


                                </td>


                            </tr>


                            @empty


                            <tr>

                                <td colspan="7"
                                    class="text-center">

                                    No Location History Found

                                </td>


                            </tr>


                            @endforelse


                        </tbody>



                    </table>


                </div>


                {{-- Pagination --}}

                <div class="mt-4 d-flex justify-content-center">

                    {{ $locations->links('pagination::bootstrap-5') }}

                </div>


            </div>

        </div>


    </div>

<<<<<<< HEAD
    <!-- Scripts -->
    <script src="https://maps.googleapis.com/maps/api/js?"></script>
=======

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>





    <!-- Pusher -->

>>>>>>> development
    <script src="https://js.pusher.com/8.2/pusher.min.js"></script>



    <script>
        let map;

        let marker;

        let watchId;

        let isTracking = false;



        let currentLat = 20.5937;

        let currentLng = 78.9629;




        /*
        |--------------------------------------------------------------------------
        | Initialize Google Map
        |--------------------------------------------------------------------------
        */


        function initMap() {

            const center = {
                lat: 23.0225,
                lng: 72.5714
            };

            map = new google.maps.Map(document.getElementById("map"), {
                zoom: 14,
                center: center
            });

            marker = new google.maps.Marker({
                position: center,
                map: map
            });

        }




        /*
        |--------------------------------------------------------------------------
        | Start Tracking
        |--------------------------------------------------------------------------
        */


        function startTracking() {


            if (isTracking)
                return;



            if (!navigator.geolocation) {


                alert(
                    "Geolocation is not supported"
                );


                return;

            }



            watchId = navigator.geolocation.watchPosition(


                function(position) {



                    currentLat =
                        position.coords.latitude;


                    currentLng =
                        position.coords.longitude;



                    let accuracy =
                        position.coords.accuracy;



                    document.getElementById(
                            "lat-value"
                        ).innerHTML =
                        currentLat.toFixed(6);



                    document.getElementById(
                            "lng-value"
                        ).innerHTML =
                        currentLng.toFixed(6);



                    document.getElementById(
                            "accuracy-value"
                        ).innerHTML =
                        Math.round(accuracy) + " m";




                    marker.setPosition({

                        lat: currentLat,

                        lng: currentLng

                    });



                    map.setCenter({

                        lat: currentLat,

                        lng: currentLng

                    });




                    document.getElementById(
                        "live-status"
                    ).innerHTML = "Online";


                    document.getElementById(
                        "live-status"
                    ).className = "status-online";




                    sendLocation(
                        currentLat,
                        currentLng
                    );



                },



                function(error) {


                    console.log(error);


                    alert(
                        "Location permission denied"
                    );


                },


                {

                    enableHighAccuracy: true,

                    maximumAge: 0,

                    timeout: 5000

                }



            );



            isTracking = true;



            document.getElementById(
                "start-btn"
            ).disabled = true;



            document.getElementById(
                "stop-btn"
            ).disabled = false;



        }






        /*
        |--------------------------------------------------------------------------
        | Stop Tracking
        |--------------------------------------------------------------------------
        */


        function stopTracking() {



            if (watchId) {


                navigator.geolocation.clearWatch(
                    watchId
                );



            }



            isTracking = false;



            document.getElementById(
                "start-btn"
            ).disabled = false;



            document.getElementById(
                "stop-btn"
            ).disabled = true;




            document.getElementById(
                "live-status"
            ).innerHTML = "Offline";


            document.getElementById(
                "live-status"
            ).className = "status-offline";



        }





        /*
        |--------------------------------------------------------------------------
        | Send Location To Laravel
        |--------------------------------------------------------------------------
        */


        function sendLocation(lat, lng) {



            fetch(
                    "/location", {

                        method: "POST",


                        headers: {


                            "Content-Type": "application/json",


                            "X-CSRF-TOKEN": "{{ csrf_token() }}"


                        },



                        body: JSON.stringify({

                            lat: lat,

                            lng: lng

                        })


                    }

                )


                .then(response => response.json())


                .then(data => {


                    console.log(
                        "Location Saved",
                        data
                    );


                })

                .catch(error => {


                    console.log(error);


                });



        }





        document.addEventListener("DOMContentLoaded", function() {
            initMap();
            initPusher();
        });


        /*
        |--------------------------------------------------------------------------
        | Pusher Real-Time Location Updates
        |--------------------------------------------------------------------------
        */


        function initPusher() {


            // Replace with your Pusher credentials

            const pusher = new Pusher(
                "{{ env('PUSHER_APP_KEY') }}", {

                    cluster: "{{ env('PUSHER_APP_CLUSTER') }}",

                    encrypted: true

                }
            );



            const channel =
                pusher.subscribe(
                    "location-channel"
                );



            channel.bind(
                "App\\Events\\LocationEvent",
                function(data) {



                    console.log(
                        "Live Location Received",
                        data
                    );



                    let lat =
                        parseFloat(
                            data.location.latitude
                        );



                    let lng =
                        parseFloat(
                            data.location.longitude
                        );



                    /*
                    Update Map Marker
                    */


                    marker.setPosition({

                        lat: lat,

                        lng: lng

                    });



                    map.setCenter({

                        lat: lat,

                        lng: lng

                    });




                    /*
                    Update Information
                    */


                    document.getElementById(
                            "lat-value"
                        ).innerHTML =
                        lat.toFixed(6);



                    document.getElementById(
                            "lng-value"
                        ).innerHTML =
                        lng.toFixed(6);



                    document.getElementById(
                            "live-status"
                        ).innerHTML =
                        "Online";



                    document.getElementById(
                            "live-status"
                        ).className =
                        "status-online";



                }
            );





            /*
            Connection Success
            */


            pusher.connection.bind(
                "connected",
                function() {


                    console.log(
                        "WebSocket Connected"
                    );


                }
            );





            /*
            Connection Lost
            */


            pusher.connection.bind(
                "disconnected",
                function() {


                    document.getElementById(
                            "live-status"
                        ).innerHTML =
                        "Offline";



                    document.getElementById(
                            "live-status"
                        ).className =
                        "status-offline";


                }
            );



        }





        /*
        |--------------------------------------------------------------------------
        | Browser Close / Tab Close
        |--------------------------------------------------------------------------
        */


        window.addEventListener(
            "beforeunload",
            function() {


                if (isTracking) {


                    navigator.geolocation.clearWatch(
                        watchId
                    );


                }


            });






        /*
        |--------------------------------------------------------------------------
        | Initialize Everything
        |--------------------------------------------------------------------------
        */


        document.addEventListener(
            "DOMContentLoaded",
            function() {


                initMap();


                initPusher();



            });
    </script>
</body>

</html>