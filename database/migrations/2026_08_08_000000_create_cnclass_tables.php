<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cnclass_rulesets', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->longText('rules')->nullable();
            $table->timestamps();
        });

        Schema::create('cnclass_prodi_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('prodi_code')->unique();
            $table->foreignId('ruleset_id')->constrained('cnclass_rulesets')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cnclass_prodi_mappings');
        Schema::dropIfExists('cnclass_rulesets');
    }
};
