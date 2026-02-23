<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE queue_logs MODIFY action ENUM('called', 'skipped', 'recalled', 'completed', 'reinstated')");
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE queue_logs MODIFY action ENUM('called', 'skipped', 'recalled', 'completed')");
        }
    }
};
