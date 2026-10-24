<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('farm_daily_records')) {
            return;
        }

        Schema::create('farm_daily_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farmer_id')->constrained('users')->cascadeOnDelete();
            $table->date('recorded_on');
            $table->unsignedInteger('flock_headcount')->nullable();
            $table->unsignedInteger('eggs_collected')->nullable();
            $table->decimal('feed_kg', 10, 3)->nullable();
            $table->decimal('water_litres', 10, 3)->nullable();
            $table->unsignedInteger('deaths')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['farmer_id', 'recorded_on']);
            $table->index(['farmer_id', 'recorded_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('farm_daily_records');
    }
};
