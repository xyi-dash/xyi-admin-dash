<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('server', 10);
            $table->timestamp('unlocked_at');
            $table->timestamp('last_activity_at');
            $table->string('ip_address', 45)->nullable();
            
            $table->unique(['user_id', 'server']);
            $table->index('last_activity_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_sessions');
    }
};
