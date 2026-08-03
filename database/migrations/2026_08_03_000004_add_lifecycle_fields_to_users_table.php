<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_login_at')->nullable()->after('remember_token');
            $table->timestamp('archived_at')->nullable()->after('disabled_at');
            $table->softDeletes()->after('archived_at');
        });

        // Existing accounts have no login history yet. Treat this migration's
        // deploy time as their inactivity baseline so the automatic lifecycle
        // sweep doesn't immediately flag long-lived accounts as inactive.
        DB::table('users')->whereNull('last_login_at')->update(['last_login_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn(['last_login_at', 'archived_at']);
        });
    }
};
