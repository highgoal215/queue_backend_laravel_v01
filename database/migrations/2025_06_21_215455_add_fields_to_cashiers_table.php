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
        Schema::table('cashiers', function (Blueprint $table) {
            $table->string('employee_id')->nullable()->after('name');
            $table->enum('status', ['active', 'inactive', 'break'])->default('active')->after('employee_id');
            $table->boolean('is_available')->default(true)->after('is_active');
            $table->foreignId('current_customer_id')->nullable()->after('is_available');
            $table->integer('total_served')->default(0)->after('current_customer_id');
            $table->integer('average_service_time')->default(0)->after('total_served');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cashiers', function (Blueprint $table) {
            $table->dropColumn(['employee_id', 'status', 'is_available', 'current_customer_id', 'total_served', 'average_service_time']);
        });
    }
};
