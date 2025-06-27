<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetDatabaseIds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:reset-ids {--table= : Specific table to reset}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset auto-increment IDs for database tables';

    /**
     * Execute the console command.
     */
    public function handle()
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

        $specificTable = $this->option('table');

        if ($specificTable) {
            if (!in_array($specificTable, $tables)) {
                $this->error("Table '{$specificTable}' not found in the list of available tables.");
                return 1;
            }
            $tables = [$specificTable];
        }

        $this->info('Resetting auto-increment IDs...');

        foreach ($tables as $table) {
            try {
                DB::statement("ALTER TABLE {$table} AUTO_INCREMENT = 1");
                $this->info("✓ Reset auto-increment ID for table: {$table}");
            } catch (\Exception $e) {
                $this->error("✗ Failed to reset auto-increment ID for table: {$table} - {$e->getMessage()}");
            }
        }

        $this->info('Auto-increment ID reset completed!');
        return 0;
    }
} 