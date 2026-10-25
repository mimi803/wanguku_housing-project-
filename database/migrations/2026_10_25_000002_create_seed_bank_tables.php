<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seed_bank_listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farmer_id')->constrained('users')->cascadeOnDelete();
            $table->string('crop_name', 100);
            $table->string('variety', 120);
            $table->string('lot_code', 60)->nullable();
            $table->string('provenance', 180);
            $table->date('harvest_date')->nullable();
            $table->date('germination_tested_at')->nullable();
            $table->decimal('germination_rate', 5, 2)->nullable();
            $table->decimal('quantity_available', 12, 3);
            $table->string('unit', 24);
            $table->string('location', 150)->nullable();
            $table->string('storage_notes', 300)->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('available')->index();
            $table->timestamps();
            $table->index(['crop_name', 'variety']);
        });

        Schema::create('seed_bank_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seed_bank_listing_id')->constrained('seed_bank_listings')->cascadeOnDelete();
            $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('quantity', 12, 3);
            $table->string('unit', 24);
            $table->text('message')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->timestamps();
            $table->index(['requester_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seed_bank_requests');
        Schema::dropIfExists('seed_bank_listings');
    }
};
