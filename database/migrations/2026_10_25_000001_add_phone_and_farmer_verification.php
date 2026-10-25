<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'email')) {
            Schema::table('users', fn (Blueprint $table) => $table->string('email', 100)->nullable()->change());
        }
        if (! Schema::hasColumn('users', 'phone_verified_at')) {
            Schema::table('users', fn (Blueprint $table) => $table->timestamp('phone_verified_at')->nullable());
        }

        if (! Schema::hasColumn('users', 'farmer_verified_at')) {
            Schema::table('users', fn (Blueprint $table) => $table->timestamp('farmer_verified_at')->nullable());
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = array_values(array_filter(['phone_verified_at', 'farmer_verified_at'], fn ($column) => Schema::hasColumn('users', $column)));
            if ($columns) $table->dropColumn($columns);
        });
    }
};
