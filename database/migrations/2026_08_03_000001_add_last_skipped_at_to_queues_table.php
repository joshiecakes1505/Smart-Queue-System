<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('queues', function (Blueprint $table) {
            $table->timestamp('last_skipped_at')->nullable()->after('is_reinstated');
        });
    }

    public function down(): void
    {
        Schema::table('queues', function (Blueprint $table) {
            $table->dropColumn('last_skipped_at');
        });
    }
};
