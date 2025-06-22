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
            $table->string('email')->nullable()->unique()->after('is_active');
            $table->string('phone', 20)->nullable()->after('email');
            $table->string('role', 100)->nullable()->after('phone');
            $table->time('shift_start')->nullable()->after('role');
            $table->time('shift_end')->nullable()->after('shift_start');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cashiers', function (Blueprint $table) {
            $table->dropColumn(['email', 'phone', 'role', 'shift_start', 'shift_end']);
        });
    }
};
