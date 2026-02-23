<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('service_categories', function (Blueprint $table) {
            $table->string('prefix', 1)->after('name')->nullable();
        });

        // Update existing categories with default prefixes
        DB::table('service_categories')->where('name', 'LIKE', '%payment%')->update(['prefix' => 'T']);
        DB::table('service_categories')->where('name', 'LIKE', '%inquir%')->update(['prefix' => 'I']);
        DB::table('service_categories')->where('name', 'LIKE', '%enroll%')->update(['prefix' => 'E']);

        Schema::table('service_categories', function (Blueprint $table) {
            $table->string('prefix', 1)->nullable(false)->unique()->change();
        });

        Schema::table('queues', function (Blueprint $table) {
            $table->enum('client_type', ['student', 'parent', 'visitor'])->after('client_name')->default('student');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_categories', function (Blueprint $table) {
            $table->dropColumn('prefix');
        });

        Schema::table('queues', function (Blueprint $table) {
            $table->dropColumn('client_type');
        });
    }
};
