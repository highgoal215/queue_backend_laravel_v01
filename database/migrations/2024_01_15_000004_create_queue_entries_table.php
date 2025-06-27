<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('queue_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('queue_id')->constrained()->onDelete('cascade');
            $table->integer('queue_number');
            $table->integer('quantity_purchased')->nullable();
            $table->foreignId('cashier_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('order_status', ['queued', 'kitchen', 'preparing', 'serving', 'completed', 'cancelled'])->default('queued');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('queue_entries');
    }
};