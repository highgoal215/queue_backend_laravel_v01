<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('customer_tracking', function (Blueprint $table) {
            $table->id();
            $table->foreignId('queue_entry_id')->constrained('queue_entries')->onDelete('cascade');
            $table->text('qr_code_url');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('customer_tracking');
    }
};