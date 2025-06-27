<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('customer_tracking', function (Blueprint $table) {
            $table->enum('status', ['waiting', 'called', 'served', 'cancelled'])->default('waiting');
            $table->integer('estimated_wait_time')->nullable()->comment('Estimated wait time in minutes');
            $table->integer('current_position')->nullable()->comment('Current position in queue');
            $table->timestamp('last_updated')->nullable()->comment('Last time position was updated');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_tracking', function (Blueprint $table) {
            $table->dropColumn(['status', 'estimated_wait_time', 'current_position', 'last_updated']);
        });
    }
};
