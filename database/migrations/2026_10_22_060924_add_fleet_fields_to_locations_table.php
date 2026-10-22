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
        Schema::table('locations', function (Blueprint $table) {
            $table->string('vehicle_id', 64)->nullable()->after('user_name')->index();
            $table->string('vehicle_type', 32)->default('car')->after('vehicle_id');
            $table->decimal('speed', 6, 2)->default(0)->after('longitude');
            $table->integer('heading')->default(0)->after('speed');
            $table->integer('battery_level')->default(100)->after('heading');
            $table->string('trip_id', 64)->nullable()->after('battery_level')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn([
                'vehicle_id',
                'vehicle_type',
                'speed',
                'heading',
                'battery_level',
                'trip_id',
            ]);
        });
    }
};
