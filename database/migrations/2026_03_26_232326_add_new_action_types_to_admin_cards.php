<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add custom_action_description column
        Schema::table('admin_cards', function (Blueprint $table) {
            $table->text('custom_action_description')->nullable()->after('evidence');
        });

        // Update action_type enum to include new types
        DB::statement("ALTER TABLE admin_cards MODIFY COLUMN action_type ENUM(
            'warning_add',
            'warning_remove',
            'permanent_ban',
            'give_ga',
            'remove_ga',
            'reset_password',
            'confirm_admin',
            'promote',
            'demote',
            'custom'
        ) NOT NULL");
    }

    public function down(): void
    {
        // Remove custom_action_description column
        Schema::table('admin_cards', function (Blueprint $table) {
            $table->dropColumn('custom_action_description');
        });

        // Revert action_type enum to original types
        DB::statement("ALTER TABLE admin_cards MODIFY COLUMN action_type ENUM(
            'warning_add',
            'warning_remove',
            'permanent_ban'
        ) NOT NULL");
    }
};
