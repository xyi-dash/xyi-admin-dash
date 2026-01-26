<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('server_settings', function (Blueprint $table) {
            $table->id();
            $table->string('server', 10);
            $table->string('key', 50);
            $table->string('value')->nullable();
            $table->timestamps();

            $table->unique(['server', 'key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('server_settings');
    }
};
