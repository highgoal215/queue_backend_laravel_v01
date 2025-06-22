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
        Schema::table('widgets', function (Blueprint $table) {
            // Drop the existing enum constraint
            $table->dropColumn('type');
        });

        Schema::table('widgets', function (Blueprint $table) {
            // Add the column back with the expanded enum
            $table->enum('type', ['time', 'date', 'weather', 'queue', 'announcement', 'custom'])->after('screen_layout_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('widgets', function (Blueprint $table) {
            // Drop the expanded enum constraint
            $table->dropColumn('type');
        });

        Schema::table('widgets', function (Blueprint $table) {
            // Add the column back with the original enum
            $table->enum('type', ['time', 'date', 'weather'])->after('screen_layout_id');
        });
    }
};
