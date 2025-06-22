<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Tests\TestRunner;
use Illuminate\Support\Facades\Artisan;

class RunComprehensiveTests extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:comprehensive 
                            {--type=all : Type of tests to run (unit, feature, integration, performance, all)}
                            {--report : Generate detailed test report}
                            {--coverage : Run tests with coverage report}
                            {--parallel : Run tests in parallel}
                            {--filter= : Filter tests by name pattern}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run comprehensive tests for the queue management system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Starting Comprehensive Test Suite...');
        $this->newLine();

        $type = $this->option('type');
        $generateReport = $this->option('report');
        $withCoverage = $this->option('coverage');
        $parallel = $this->option('parallel');
        $filter = $this->option('filter');

        // Run PHPUnit tests
        $this->runPhpUnitTests($type, $withCoverage, $parallel, $filter);

        // Run custom test runner
        if ($generateReport) {
            $this->runCustomTests();
        }

        $this->info('✅ Comprehensive testing completed!');
    }

    /**
     * Run PHPUnit tests
     */
    protected function runPhpUnitTests(string $type, bool $withCoverage, bool $parallel, ?string $filter): void
    {
        $this->info('📋 Running PHPUnit Tests...');

        $command = 'test';

        // Add test type filter
        if ($type !== 'all') {
            $command .= " --testsuite={$type}";
        }

        // Add coverage if requested
        if ($withCoverage) {
            $command .= ' --coverage-html=coverage';
            $command .= ' --coverage-text';
        }

        // Add parallel execution if requested
        if ($parallel) {
            $command .= ' --parallel';
        }

        // Add filter if specified
        if ($filter) {
            $command .= " --filter={$filter}";
        }

        // Add verbose output
        $command .= ' --verbose';

        $this->info("Executing: php artisan {$command}");
        
        $exitCode = Artisan::call($command);
        
        if ($exitCode === 0) {
            $this->info('✅ PHPUnit tests passed!');
        } else {
            $this->error('❌ PHPUnit tests failed!');
        }

        $this->newLine();
    }

    /**
     * Run custom test runner
     */
    protected function runCustomTests(): void
    {
        $this->info('🔧 Running Custom Test Runner...');

        try {
            $testRunner = new TestRunner();
            $report = $testRunner->generateReport();

            $this->displayReport($report);
        } catch (\Exception $e) {
            $this->error("❌ Custom test runner failed: {$e->getMessage()}");
        }

        $this->newLine();
    }

    /**
     * Display test report
     */
    protected function displayReport(array $report): void
    {
        $this->info('📊 Test Report');
        $this->info('=============');

        // Display summary
        $summary = $report['summary'];
        $this->info("Total Tests: {$summary['total_tests']}");
        $this->info("Passed Tests: {$summary['passed_tests']}");
        $this->info("Success Rate: {$summary['success_rate']}%");

        $this->newLine();

        // Display detailed results
        $this->displayTestCategory('Unit Tests', $report['unit_tests'] ?? []);
        $this->displayTestCategory('Feature Tests', $report['feature_tests'] ?? []);
        $this->displayTestCategory('Integration Tests', $report['integration_tests'] ?? []);
        $this->displayTestCategory('Performance Tests', $report['performance_tests'] ?? []);

        // Save report to file
        $this->saveReportToFile($report);
    }

    /**
     * Display test category results
     */
    protected function displayTestCategory(string $category, array $tests): void
    {
        $this->info("📁 {$category}");
        
        foreach ($tests as $testName => $results) {
            $this->line("  └─ {$testName}:");
            
            if (is_array($results)) {
                foreach ($results as $resultName => $result) {
                    $status = $result ? '✅' : '❌';
                    $this->line("    └─ {$resultName}: {$status}");
                }
            } else {
                $status = $results ? '✅' : '❌';
                $this->line("    └─ {$status}");
            }
        }
        
        $this->newLine();
    }

    /**
     * Save report to file
     */
    protected function saveReportToFile(array $report): void
    {
        $filename = 'test-report-' . now()->format('Y-m-d-H-i-s') . '.json';
        $path = storage_path('app/test-reports/' . $filename);
        
        // Create directory if it doesn't exist
        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }
        
        file_put_contents($path, json_encode($report, JSON_PRETTY_PRINT));
        
        $this->info("📄 Test report saved to: {$path}");
    }

    /**
     * Get test statistics
     */
    protected function getTestStatistics(): array
    {
        $stats = [
            'total_tests' => 0,
            'passed_tests' => 0,
            'failed_tests' => 0,
            'test_files' => 0,
        ];

        // Count test files
        $testDirs = ['tests/Unit', 'tests/Feature'];
        
        foreach ($testDirs as $dir) {
            if (is_dir($dir)) {
                $files = glob($dir . '/*.php');
                $stats['test_files'] += count($files);
            }
        }

        return $stats;
    }
} 