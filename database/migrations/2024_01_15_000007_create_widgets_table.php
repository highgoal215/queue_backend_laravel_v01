<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('widgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('screen_layout_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['time', 'date', 'weather']);
            $table->string('position');
            $table->json('settings_json')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('widgets');
    }
};