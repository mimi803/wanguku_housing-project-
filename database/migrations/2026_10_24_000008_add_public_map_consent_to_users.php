<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'show_on_map')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('show_on_map')->default(false);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'show_on_map')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('show_on_map');
            });
        }
    }
};
