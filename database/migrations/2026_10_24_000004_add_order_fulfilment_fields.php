<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'delivery_notes')) $table->text('delivery_notes')->nullable();
            if (! Schema::hasColumn('orders', 'payment_method')) $table->string('payment_method', 30)->default('arrange_with_farmer');
            if (! Schema::hasColumn('orders', 'payment_status')) $table->string('payment_status', 20)->default('unpaid');
            if (! Schema::hasColumn('orders', 'updated_at')) $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('orders')) return;

        Schema::table('orders', function (Blueprint $table) {
            foreach (['delivery_notes', 'payment_method', 'payment_status', 'updated_at'] as $column) {
                if (Schema::hasColumn('orders', $column)) $table->dropColumn($column);
            }
        });
    }
};
