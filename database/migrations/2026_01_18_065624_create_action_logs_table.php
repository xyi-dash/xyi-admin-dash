<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('action_logs', function (Blueprint $table) {
            $table->id();
            $table->string('action_type', 50)->index();

            $table->unsignedBigInteger('actor_id');
            $table->string('actor_name', 24);
            $table->string('actor_server', 10);

            $table->unsignedBigInteger('target_id')->nullable();
            $table->string('target_name', 24)->nullable();
            $table->string('target_server', 10)->nullable();

            $table->json('details')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();

            $table->index(['actor_id', 'actor_server']);
            $table->index(['target_id', 'target_server']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('action_logs');
    }
};
