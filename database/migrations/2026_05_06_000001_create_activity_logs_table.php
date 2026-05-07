<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Koneksi yang digunakan (sama dengan tabel users)
     */
    protected $connection = 'mysql';

    public function up(): void
    {
        Schema::connection('mysql')->create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('username')->nullable();
            $table->string('user_name')->nullable();
            $table->string('user_role', 20)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('method', 10)->default('GET');
            $table->string('url', 500);
            $table->string('route_name')->nullable();
            $table->text('user_agent')->nullable();
            $table->smallInteger('status_code')->nullable();
            $table->timestamps();

            // Index untuk query filter
            $table->index(['created_at']);
            $table->index(['user_role']);
        });
    }

    public function down(): void
    {
        Schema::connection('mysql')->dropIfExists('activity_logs');
    }
};
