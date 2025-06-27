<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Reset auto-increment IDs for all tables
        $this->resetAutoIncrementIds();

        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Seed queue data
        $this->call([
            QueueSeeder::class,
        ]);
    }

    /**
     * Reset auto-increment IDs for all tables
     */
    private function resetAutoIncrementIds(): void
    {
        $tables = [
            'users',
            'queues',
            'cashiers',
            'queue_entries',
            'customer_tracking',
            'screen_layouts',
            'widgets',
            'personal_access_tokens',
            'notifications',
            'jobs',
            'failed_jobs'
        ];

        foreach ($tables as $table) {
            DB::statement("ALTER TABLE {$table} AUTO_INCREMENT = 1");
        }
    }
}
