<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('queue_logs', function (Blueprint $table) {
            $table->enum('action', ['called', 'skipped', 'recalled', 'completed', 'reinstated', 'expired'])->change();
        });
    }

    public function down(): void
    {
        Schema::table('queue_logs', function (Blueprint $table) {
            $table->enum('action', ['called', 'skipped', 'recalled', 'completed', 'reinstated'])->change();
        });
    }
};
