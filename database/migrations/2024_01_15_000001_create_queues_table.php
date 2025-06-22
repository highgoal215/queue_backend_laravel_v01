<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('queues', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['regular', 'inventory']);
            $table->integer('max_quantity')->nullable();
            $table->integer('remaining_quantity')->nullable();
            $table->enum('status', ['active', 'paused', 'closed'])->default('active');
            $table->integer('current_number')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('queues');
    }
};