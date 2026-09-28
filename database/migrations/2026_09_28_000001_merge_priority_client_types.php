<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Collapses 'senior_citizen' and 'high_priority' into a single 'priority'
 * client type. Both already behaved identically (Queue::PRIORITY_CLIENT_TYPES
 * listed both and every branch treated them the same), so this only removes a
 * distinction the system never acted on.
 */
return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        // Widen the enum first so existing rows stay valid while we convert.
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE queues MODIFY client_type ENUM('student', 'parent', 'visitor', 'senior_citizen', 'high_priority', 'priority') NOT NULL DEFAULT 'student'");
        }

        DB::table('queues')
            ->whereIn('client_type', ['senior_citizen', 'high_priority'])
            ->update(['client_type' => 'priority']);

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE queues MODIFY client_type ENUM('student', 'parent', 'visitor', 'priority') NOT NULL DEFAULT 'student'");
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE queues MODIFY client_type ENUM('student', 'parent', 'visitor', 'senior_citizen', 'high_priority', 'priority') NOT NULL DEFAULT 'student'");
        }

        // The merge is lossy: everything comes back as senior_citizen.
        DB::table('queues')
            ->where('client_type', 'priority')
            ->update(['client_type' => 'senior_citizen']);

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE queues MODIFY client_type ENUM('student', 'parent', 'visitor', 'senior_citizen', 'high_priority') NOT NULL DEFAULT 'student'");
        }
    }
};
