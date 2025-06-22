<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('cashiers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('assigned_queue_id')->nullable()->constrained('queues')->onDelete('set null');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('cashiers');
    }
};
