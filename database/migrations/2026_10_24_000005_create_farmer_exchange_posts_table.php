<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('farmer_exchange_posts')) return;

        Schema::create('farmer_exchange_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farmer_id')->constrained('users')->cascadeOnDelete();
            $table->string('post_kind', 12);
            $table->string('resource_type', 24);
            $table->string('title', 150);
            $table->text('details');
            $table->decimal('quantity', 10, 2)->nullable();
            $table->string('unit', 32)->nullable();
            $table->string('location', 150)->nullable();
            $table->string('status', 16)->default('open');
            $table->timestamps();
            $table->index(['status', 'post_kind', 'resource_type']);
            $table->index(['farmer_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('farmer_exchange_posts');
    }
};
