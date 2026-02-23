<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('queues', function (Blueprint $table) {
            $table->id();
            $table->string('queue_number')->unique();
            $table->foreignId('service_category_id')->constrained('service_categories')->onDelete('restrict');
            $table->enum('status', ['waiting', 'called', 'skipped', 'completed'])->default('waiting');
            $table->foreignId('cashier_window_id')->nullable()->constrained('cashier_windows')->nullOnDelete();
            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable();
            $table->string('client_name')->nullable();
            $table->string('phone')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('service_category_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('queues');
    }
};
