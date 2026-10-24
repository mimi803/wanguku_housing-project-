<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('farm_devices')) {
            Schema::create('farm_devices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('farmer_id')->constrained('users')->cascadeOnDelete();
                $table->string('name', 100);
                $table->string('sensor_type', 24);
                $table->string('unit', 20);
                $table->decimal('warning_below', 12, 3)->nullable();
                $table->string('api_token_hash', 64)->unique();
                $table->boolean('is_active')->default(true);
                $table->timestamp('last_seen_at')->nullable();
                $table->timestamps();
                $table->index(['farmer_id', 'is_active']);
            });
        }

        if (! Schema::hasTable('farm_sensor_readings')) {
            Schema::create('farm_sensor_readings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('device_id')->constrained('farm_devices')->cascadeOnDelete();
                $table->decimal('reading_value', 12, 3);
                $table->timestamp('captured_at');
                $table->boolean('alert_triggered')->default(false);
                $table->timestamp('created_at')->useCurrent();
                $table->index(['device_id', 'captured_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('farm_sensor_readings');
        Schema::dropIfExists('farm_devices');
    }
};
