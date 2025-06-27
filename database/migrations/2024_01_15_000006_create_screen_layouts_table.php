<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('screen_layouts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('device_id')->index();
            $table->json('layout_config');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('screen_layouts');
    }
};