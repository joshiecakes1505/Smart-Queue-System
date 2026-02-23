<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('queues', function (Blueprint $table) {
            $table->unsignedInteger('skip_count')->default(0)->after('status');
            $table->boolean('is_reinstated')->default(false)->after('skip_count');
        });
    }

    public function down(): void
    {
        Schema::table('queues', function (Blueprint $table) {
            $table->dropColumn(['skip_count', 'is_reinstated']);
        });
    }
};
