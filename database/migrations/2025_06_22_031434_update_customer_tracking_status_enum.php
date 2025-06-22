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
            // Drop the existing enum constraint
            $table->dropColumn('status');
        });

        Schema::table('customer_tracking', function (Blueprint $table) {
            // Add the column back with the expanded enum
            $table->enum('status', ['waiting', 'called', 'served', 'cancelled', 'no_show'])->default('waiting')->after('qr_code_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_tracking', function (Blueprint $table) {
            // Drop the expanded enum
            $table->dropColumn('status');
        });

        Schema::table('customer_tracking', function (Blueprint $table) {
            // Add back the original enum
            $table->enum('status', ['waiting', 'called', 'served', 'cancelled'])->default('waiting')->after('qr_code_url');
        });
    }
};
