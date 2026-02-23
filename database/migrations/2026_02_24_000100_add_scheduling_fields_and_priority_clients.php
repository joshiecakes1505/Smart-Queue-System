<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('queue_counters', function (Blueprint $table) {
            $table->foreignId('last_assigned_window_id')
                ->nullable()
                ->after('service_category_id')
                ->constrained('cashier_windows')
                ->nullOnDelete();
            $table->unsignedTinyInteger('regular_served_in_cycle')
                ->default(0)
                ->after('last_assigned_window_id');
        });

        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE queues MODIFY client_type ENUM('student', 'parent', 'visitor', 'senior_citizen', 'high_priority') NOT NULL DEFAULT 'student'");
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE queues MODIFY client_type ENUM('student', 'parent', 'visitor') NOT NULL DEFAULT 'student'");
        }

        Schema::table('queue_counters', function (Blueprint $table) {
            $table->dropConstrainedForeignId('last_assigned_window_id');
            $table->dropColumn('regular_served_in_cycle');
        });
    }
};
