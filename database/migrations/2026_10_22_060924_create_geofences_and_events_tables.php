<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('geofences', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type', 32)->default('circle'); // circle, polygon
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->integer('radius_meters')->default(500);
            $table->json('polygon_coordinates')->nullable();
            $table->string('color', 16)->default('#3b82f6');
            $table->boolean('alert_on_entry')->default(true);
            $table->boolean('alert_on_exit')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('geofence_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('geofence_id')->index();
            $table->string('vehicle_id', 64)->index();
            $table->string('user_name', 128);
            $table->string('event_type', 32); // ENTER, EXIT
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('speed', 6, 2)->default(0);
            $table->timestamp('occurred_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('geofence_events');
        Schema::dropIfExists('geofences');
    }
};
