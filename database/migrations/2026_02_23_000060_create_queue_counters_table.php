<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('queue_counters', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->integer('last_number')->default(0);
            $table->foreignId('service_category_id')->nullable()->constrained('service_categories')->nullOnDelete();
            $table->timestamps();

            $table->unique(['date', 'service_category_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('queue_counters');
    }
};
