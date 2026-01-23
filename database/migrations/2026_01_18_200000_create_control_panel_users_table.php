<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('control_panel_users', function (Blueprint $table) {
            $table->id();
            $table->string('nickname');
            $table->string('server')->default('one'); // which server account is from
            $table->json('permissions')->nullable(); // for future granular permissions
            $table->string('created_by')->nullable(); // who added this user
            $table->timestamps();

            $table->unique(['nickname', 'server']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('control_panel_users');
    }
};
