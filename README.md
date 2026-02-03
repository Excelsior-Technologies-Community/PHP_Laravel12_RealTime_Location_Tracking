# PHP_Laravel12_Realtime_Location_Tracking

A beginner-friendly Laravel 12 project demonstrating **Realtime Location Tracking** using **Google Maps**, **Pusher Broadcasting**, and **Laravel Events**. This project shows how multiple users can see live GPS movement on a map in real time.

---

## Project Overview

This project allows a browser to capture GPS coordinates and broadcast them instantly to all connected users. It is ideal for learning:

* Laravel Broadcasting
* Realtime events with Pusher
* Google Maps integration
* GPS tracking in browser
* Multi-user realtime communication

---

## Tech Stack

* Laravel 12
* PHP 8+
* MySQL
* Pusher
* Google Maps JavaScript API
* JavaScript (Vanilla)
* Blade Templates

---

## Features

* Live GPS tracking
* Google Map marker movement
* Broadcast events using Pusher
* Store latitude and longitude in database
* Multi-user realtime updates
* Simple UI for beginners

---

## Installation Steps

### 1. Create Project

```bash
composer create-project laravel/laravel realtime-location
cd realtime-location
```

---

### 2. Environment Setup (.env)

Create database in **phpMyAdmin**:

```
realtime_location
```

Update `.env` file:

```
DB_DATABASE=realtime_location
DB_USERNAME=root
DB_PASSWORD=

BROADCAST_DRIVER=pusher

PUSHER_APP_ID=xxxx
PUSHER_APP_KEY=xxxx
PUSHER_APP_SECRET=xxxx
PUSHER_APP_CLUSTER=ap2
```

---

### 3. Install Packages

```bash
composer require pusher/pusher-php-server
npm install
npm run dev
```

---

## Location Model and Migration

### Create Model

```bash
php artisan make:model Location -m
```

### Migration File

`database/migrations/xxxx_create_locations_table.php`

```php
public function up()
{
    Schema::create('locations', function (Blueprint $table) {
        $table->id();
        $table->string('user_name')->nullable();
        $table->double('latitude');
        $table->double('longitude');
        $table->timestamps();
    });
}
```

### Run Migration

```bash
php artisan migrate
```

---

## Model

`app/Models/Location.php`

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $fillable = [
        'user_name',
        'latitude',
        'longitude'
    ];
}
```

---

## Event

### Create Event

```bash
php artisan make:event LocationEvent
```

`app/Events/LocationEvent.php`

```php
namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class LocationEvent implements ShouldBroadcast
{
    use SerializesModels;

    public $location;

    public function __construct($location)
    {
        $this->location = $location;
    }

    public function broadcastOn()
    {
        return new Channel('location-channel');
    }
}
```

---

## Controller

### Create Controller

```bash
php artisan make:controller LocationController
```

`app/Http/Controllers/LocationController.php`

```php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Location;
use App\Events\LocationEvent;

class LocationController extends Controller
{
    public function index()
    {
        return view('map');
    }

    public function store(Request $request)
    {
        $location = Location::create([
            'user_name' => 'User',
            'latitude' => $request->lat,
            'longitude' => $request->lng,
        ]);

        broadcast(new LocationEvent($location))->toOthers();

        return response()->json(['status' => 'ok']);
    }
}
```

---

## Routes

`routes/web.php`

```php
use App\Http\Controllers\LocationController;

Route::get('/', [LocationController::class, 'index']);
Route::post('/location', [LocationController::class, 'store']);
```

---

## Broadcasting Configuration

`config/broadcasting.php`

```
'default' => env('BROADCAST_DRIVER', 'pusher'),
```

---

## Blade View (Map UI)

Create file:

`resources/views/map.blade.php`

```html
<!DOCTYPE html>
<html>
<head>
    <title>Realtime Location Tracking</title>

    <script src="https://maps.googleapis.com/maps/api/js?key=YOUR_GOOGLE_MAP_KEY"></script>
    <script src="https://js.pusher.com/8.2/pusher.min.js"></script>
</head>
<body>

<h2>Live Location Tracking</h2>
<div id="map" style="height:500px;width:100%;"></div>

<script>
let map, marker;

function initMap(lat = 20.5937, lng = 78.9629) {
    map = new google.maps.Map(document.getElementById("map"), {
        zoom: 5,
        center: {lat: lat, lng: lng}
    });

    marker = new google.maps.Marker({
        position: {lat: lat, lng: lng},
        map: map
    });
}

initMap();

navigator.geolocation.watchPosition(function(pos){
    let lat = pos.coords.latitude;
    let lng = pos.coords.longitude;

    marker.setPosition({lat,lng});
    map.setCenter({lat,lng});

    fetch('/location', {
        method: 'POST',
        headers: {
            'Content-Type':'application/json',
            'X-CSRF-TOKEN':'{{ csrf_token() }}'
        },
        body: JSON.stringify({lat:lat, lng:lng})
    });
});

Pusher.logToConsole = true;

var pusher = new Pusher('PUSHER_KEY', {
    cluster: 'ap2'
});

var channel = pusher.subscribe('location-channel');

channel.bind('App\\Events\\LocationEvent', function(data) {
    marker.setPosition({
        lat: parseFloat(data.location.latitude),
        lng: parseFloat(data.location.longitude)
    });
});
</script>

</body>
</html>
```

---

## Run Project

```bash
php artisan serve
```

Open browser:

```
http://127.0.0.1:8000
```

Allow location permission in browser.
<img width="1668" height="969" alt="image" src="https://github.com/user-attachments/assets/30c7ba5a-859e-474b-b169-cdce595c3039" />

---

## How the System Works

1. Browser captures GPS coordinates.
2. Latitude and longitude are sent to Laravel backend.
3. Laravel saves data into database.
4. Event is broadcast using Pusher.
5. All connected users receive updates instantly.
6. Map marker moves in real time.

---

## Final Output

* Live moving Google Map marker
* Realtime location updates
* Multi-user tracking capability
* Simple beginner realtime project

---

## Future Enhancements

* User authentication and login
* Multiple markers for different users
* Route and travel history
* Admin dashboard
* Mobile application integration

---

## Use Cases

* Delivery tracking systems
* Employee tracking
* Ride sharing apps
* Learning realtime broadcasting in Laravel
