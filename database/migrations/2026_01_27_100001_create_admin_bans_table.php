<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_bans', function (Blueprint $table) {
            $table->id();
            
            // Admin information
            $table->unsignedBigInteger('admin_id');
            $table->string('admin_name', 24);
            $table->string('server', 10);
            
            // Ban details
            $table->text('reason');
            $table->text('evidence')->nullable();
            
            // Ban metadata
            $table->unsignedBigInteger('banned_by');
            $table->timestamp('banned_at')->useCurrent();
            
            // Indexes for performance
            $table->index(['admin_id', 'server'], 'idx_admin');
            $table->index('banned_at', 'idx_banned_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_bans');
    }
};
