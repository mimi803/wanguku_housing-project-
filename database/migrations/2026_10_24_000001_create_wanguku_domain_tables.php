<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('products')) {
            Schema::create('products', function (Blueprint $table) {
                $table->id();
                $table->foreignId('farmer_id')->constrained('users')->cascadeOnDelete();
                $table->string('title', 150);
                $table->string('category', 60);
                $table->decimal('price', 10, 2);
                $table->unsignedInteger('stock')->default(1);
                $table->text('description')->nullable();
                $table->string('image_url')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->index('category');
            });
        }

        if (! Schema::hasTable('orders')) {
            Schema::create('orders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->foreignId('buyer_id')->constrained('users')->cascadeOnDelete();
                $table->unsignedInteger('quantity')->default(1);
                $table->decimal('total_amount', 10, 2);
                $table->string('phone', 20);
                $table->string('checkout_request_id', 100)->nullable()->index();
                $table->string('mpesa_receipt', 50)->nullable();
                $table->string('status', 20)->default('pending');
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (! Schema::hasTable('incubation_batches')) {
            Schema::create('incubation_batches', function (Blueprint $table) {
                $table->id();
                $table->foreignId('farmer_id')->constrained('users')->cascadeOnDelete();
                $table->string('batch_name', 100);
                $table->unsignedInteger('egg_count');
                $table->date('start_date');
                $table->date('day7_candling');
                $table->date('day14_candling');
                $table->date('hatch_date');
                $table->unsignedInteger('hatched_count')->nullable();
                $table->boolean('completed')->default(false);
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (! Schema::hasTable('mortality_logs')) {
            Schema::create('mortality_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('farmer_id')->constrained('users')->cascadeOnDelete();
                $table->date('log_date');
                $table->string('flock_label', 100);
                $table->unsignedInteger('headcount')->default(0);
                $table->unsignedInteger('deaths')->default(0);
                $table->decimal('feed_cost', 10, 2)->default(0);
                $table->unsignedInteger('emergency_sales')->default(0);
                $table->text('notes')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (! Schema::hasTable('vaccination_schedules')) {
            Schema::create('vaccination_schedules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('farmer_id')->constrained('users')->cascadeOnDelete();
                $table->string('batch_name', 100);
                $table->string('vaccine_name', 150);
                $table->date('due_date');
                $table->boolean('completed')->default(false);
                $table->timestamp('created_at')->useCurrent();
                $table->index('due_date');
            });
        }

        if (! Schema::hasTable('articles')) {
            Schema::create('articles', function (Blueprint $table) {
                $table->id();
                $table->string('title', 200);
                $table->string('category', 50);
                $table->text('content');
                $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (! Schema::hasTable('farm_operation_logs')) {
            Schema::create('farm_operation_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('farmer_id')->constrained('users')->cascadeOnDelete();
                $table->string('operation_type', 20);
                $table->string('title', 160);
                $table->dateTime('recorded_at');
                $table->decimal('quantity', 12, 3)->nullable();
                $table->string('unit', 32)->nullable();
                $table->string('status', 20)->default('logged');
                $table->string('flock_batch', 100)->nullable();
                $table->text('notes')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->index(['farmer_id', 'recorded_at']);
                $table->index(['operation_type', 'recorded_at']);
            });
        }
    }

    public function down(): void
    {
        // Deliberately non-destructive: these tables may predate Laravel and contain live farm data.
    }
};
