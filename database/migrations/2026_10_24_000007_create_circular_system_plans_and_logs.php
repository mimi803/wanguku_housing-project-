<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('farm_system_plans')) {
            Schema::create('farm_system_plans', function (Blueprint $table) {
                $table->id();
                $table->foreignId('farmer_id')->constrained('users')->cascadeOnDelete();
                $table->unsignedTinyInteger('floor_number');
                $table->string('system_type', 24);
                $table->string('name', 120);
                $table->string('stage', 20)->default('concept');
                $table->unsignedSmallInteger('target_year')->default(2035);
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->index(['farmer_id', 'floor_number']);
            });
        }

        if (! Schema::hasTable('circular_resource_logs')) {
            Schema::create('circular_resource_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('farmer_id')->constrained('users')->cascadeOnDelete();
                $table->date('recorded_on');
                $table->string('resource_type', 24);
                $table->string('source', 120);
                $table->string('destination', 120);
                $table->decimal('quantity', 12, 3)->nullable();
                $table->string('unit', 24)->nullable();
                $table->string('review_status', 28)->default('review_required');
                $table->text('notes')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->index(['farmer_id', 'recorded_on']);
                $table->index(['resource_type', 'review_status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('circular_resource_logs');
        Schema::dropIfExists('farm_system_plans');
    }
};
