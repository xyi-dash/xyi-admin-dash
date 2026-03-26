<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_cards', function (Blueprint $table) {
            $table->id();
            
            // Creator information
            $table->unsignedBigInteger('creator_id');
            $table->string('creator_name', 24);
            $table->string('creator_server', 10);
            
            // Target information
            $table->string('target_admin_name', 24);
            
            // Action details
            $table->enum('action_type', ['warning_add', 'warning_remove', 'permanent_ban']);
            $table->text('reason');
            $table->text('evidence')->nullable();
            
            // Status and review
            $table->enum('status', ['pending', 'approved', 'rejected', 'requires_confirmation'])->default('pending');
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            
            // Timestamps
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            
            // Indexes for performance
            $table->index('status', 'idx_status');
            $table->index(['creator_id', 'creator_server'], 'idx_creator');
            $table->index('target_admin_name', 'idx_target');
            $table->index('created_at', 'idx_created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_cards');
    }
};
