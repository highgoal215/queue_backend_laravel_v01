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
        Schema::table('queue_entries', function (Blueprint $table) {
            $table->string('customer_name')->nullable()->after('queue_id');
            $table->string('phone_number')->nullable()->after('customer_name');
            $table->json('order_details')->nullable()->after('phone_number');
            $table->integer('estimated_wait_time')->nullable()->after('order_details');
            $table->text('notes')->nullable()->after('estimated_wait_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('queue_entries', function (Blueprint $table) {
            $table->dropColumn(['customer_name', 'phone_number', 'order_details', 'estimated_wait_time', 'notes']);
        });
    }
};
