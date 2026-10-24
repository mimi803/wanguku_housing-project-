<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100);
                $table->string('email', 100)->unique();
                $table->string('phone', 20)->nullable();
                $table->string('password');
                $table->string('role', 20)->default('buyer');
                $table->decimal('latitude', 10, 8)->nullable();
                $table->decimal('longitude', 11, 8)->nullable();
                $table->string('location_name', 150)->nullable();
                $table->timestamp('email_verified_at')->nullable();
                $table->rememberToken();
                $table->timestamps();
            });
        } else {
            // Add only Laravel auth/profile columns missing from the legacy PHP schema.
            Schema::table('users', function (Blueprint $table) {
                if (! Schema::hasColumn('users', 'phone')) $table->string('phone', 20)->nullable();
                if (! Schema::hasColumn('users', 'role')) $table->string('role', 20)->default('buyer');
                if (! Schema::hasColumn('users', 'latitude')) $table->decimal('latitude', 10, 8)->nullable();
                if (! Schema::hasColumn('users', 'longitude')) $table->decimal('longitude', 11, 8)->nullable();
                if (! Schema::hasColumn('users', 'location_name')) $table->string('location_name', 150)->nullable();
                if (! Schema::hasColumn('users', 'email_verified_at')) $table->timestamp('email_verified_at')->nullable();
                if (! Schema::hasColumn('users', 'remember_token')) $table->rememberToken();
                if (! Schema::hasColumn('users', 'created_at')) $table->timestamp('created_at')->nullable();
                if (! Schema::hasColumn('users', 'updated_at')) $table->timestamp('updated_at')->nullable();
            });
        }

        if (! Schema::hasTable('password_reset_tokens')) {
            Schema::create('password_reset_tokens', function (Blueprint $table) {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }

        if (! Schema::hasTable('sessions')) {
            Schema::create('sessions', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->foreignId('user_id')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->longText('payload');
                $table->integer('last_activity')->index();
            });
        }
    }

    public function down(): void
    {
        // Keep users and existing authentication data intact on rollback.
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
    }
};
