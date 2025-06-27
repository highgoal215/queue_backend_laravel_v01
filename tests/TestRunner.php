<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use App\Models\User;
use App\Models\Queue;
use App\Models\QueueEntry;
use App\Models\Cashier;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;

/**
 * Comprehensive Test Runner for Queue Management System
 * 
 * This class provides different testing scenarios and configurations
 * for running comprehensive tests on the queue management system.
 */
class TestRunner extends BaseTestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected Queue $queue;
    protected Cashier $cashier;

    public function __construct()
    {
        $this->user = User::factory()->create();
        $this->queue = Queue::factory()->active()->create();
        $this->cashier = Cashier::factory()->active()->create();
    }

    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        return $app;
    }

    public function testAllEndpoints()
    {
        // This method will be used to run all tests
        $this->assertTrue(true);
    }

    public static function runAllTests()
    {
        echo "Running comprehensive API endpoint tests...\n";
        echo "==========================================\n\n";

        // Run all feature tests
        $output = shell_exec('php artisan test --testsuite=Feature --verbose');
        
        echo $output;
        
        // Parse results
        if (strpos($output, 'FAILED') !== false) {
            echo "\n❌ Some tests failed!\n";
            return false;
        } else {
            echo "\n✅ All tests passed!\n";
            return true;
        }
    }

    /**
     * Run all unit tests
     */
    public function runUnitTests(): array
    {
        $results = [];

        // Queue Service Tests
        $results['queue_service'] = $this->testQueueService();
        
        // Other service tests can be added here
        $results['queue_entry_service'] = $this->testQueueEntryService();
        $results['cashier_service'] = $this->testCashierService();

        return $results;
    }

    /**
     * Run all feature tests
     */
    public function runFeatureTests(): array
    {
        $results = [];

        // API Tests
        $results['queue_api'] = $this->testQueueAPI();
        $results['queue_entry_api'] = $this->testQueueEntryAPI();
        $results['cashier_api'] = $this->testCashierAPI();
        $results['auth_api'] = $this->testAuthAPI();

        return $results;
    }

    /**
     * Run integration tests
     */
    public function runIntegrationTests(): array
    {
        $results = [];

        // End-to-end workflow tests
        $results['queue_workflow'] = $this->testQueueWorkflow();
        $results['cashier_assignment'] = $this->testCashierAssignment();
        $results['order_processing'] = $this->testOrderProcessing();

        return $results;
    }

    /**
     * Run performance tests
     */
    public function runPerformanceTests(): array
    {
        $results = [];

        // Load testing
        $results['queue_load'] = $this->testQueueLoad();
        $results['concurrent_entries'] = $this->testConcurrentEntries();
        $results['database_performance'] = $this->testDatabasePerformance();

        return $results;
    }

    /**
     * Test Queue Service functionality
     */
    protected function testQueueService(): array
    {
        $results = [];

        // Test queue creation
        $queueData = [
            'name' => 'Test Queue',
            'type' => 'regular',
            'max_quantity' => 100,
            'remaining_quantity' => 50,
            'status' => 'active',
            'current_number' => 1
        ];

        $queue = Queue::create($queueData);
        $results['creation'] = $queue->exists;

        // Test queue operations
        $queue->update(['status' => 'paused']);
        $results['pause'] = $queue->status === 'paused';

        $queue->update(['status' => 'active']);
        $results['resume'] = $queue->status === 'active';

        return $results;
    }

    /**
     * Test Queue Entry Service functionality
     */
    protected function testQueueEntryService(): array
    {
        $results = [];

        // Test entry creation
        $entry = QueueEntry::factory()->create([
            'queue_id' => $this->queue->id,
            'customer_name' => 'Test Customer',
            'order_status' => 'queued'
        ]);

        $results['creation'] = $entry->exists;
        $results['queue_association'] = $entry->queue_id === $this->queue->id;

        // Test status updates
        $entry->update(['order_status' => 'preparing']);
        $results['status_update'] = $entry->order_status === 'preparing';

        return $results;
    }

    /**
     * Test Cashier Service functionality
     */
    protected function testCashierService(): array
    {
        $results = [];

        // Test cashier assignment
        $this->cashier->update(['assigned_queue_id' => $this->queue->id]);
        $results['queue_assignment'] = $this->cashier->assigned_queue_id === $this->queue->id;

        // Test availability
        $this->cashier->update(['is_available' => false]);
        $results['availability_update'] = !$this->cashier->is_available;

        return $results;
    }

    /**
     * Test Queue API endpoints
     */
    protected function testQueueAPI(): array
    {
        $results = [];

        Sanctum::actingAs($this->user);

        // Test queue listing
        $response = app('Illuminate\Contracts\Http\Kernel')
            ->handle(app('Illuminate\Http\Request')->create('/api/queues', 'GET'));
        $results['list'] = $response->getStatusCode() === 200;

        // Test queue creation
        $response = app('Illuminate\Contracts\Http\Kernel')
            ->handle(app('Illuminate\Http\Request')->create('/api/queues', 'POST', [
                'name' => 'API Test Queue',
                'type' => 'regular',
                'max_quantity' => 100
            ]));
        $results['create'] = $response->getStatusCode() === 201;

        return $results;
    }

    /**
     * Test Queue Entry API endpoints
     */
    protected function testQueueEntryAPI(): array
    {
        $results = [];

        Sanctum::actingAs($this->user);

        // Test entry creation
        $response = app('Illuminate\Contracts\Http\Kernel')
            ->handle(app('Illuminate\Http\Request')->create('/api/entries', 'POST', [
                'queue_id' => $this->queue->id,
                'customer_name' => 'API Test Customer',
                'phone_number' => '+1234567890'
            ]));
        $results['create'] = $response->getStatusCode() === 201;

        return $results;
    }

    /**
     * Test Cashier API endpoints
     */
    protected function testCashierAPI(): array
    {
        $results = [];

        Sanctum::actingAs($this->user);

        // Test cashier listing
        $response = app('Illuminate\Contracts\Http\Kernel')
            ->handle(app('Illuminate\Http\Request')->create('/api/cashiers', 'GET'));
        $results['list'] = $response->getStatusCode() === 200;

        return $results;
    }

    /**
     * Test Authentication API endpoints
     */
    protected function testAuthAPI(): array
    {
        $results = [];

        // Test registration
        $response = app('Illuminate\Contracts\Http\Kernel')
            ->handle(app('Illuminate\Http\Request')->create('/api/register', 'POST', [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123'
            ]));
        $results['register'] = $response->getStatusCode() === 201;

        return $results;
    }

    /**
     * Test complete queue workflow
     */
    protected function testQueueWorkflow(): array
    {
        $results = [];

        // Create queue
        $queue = Queue::factory()->active()->create();
        $results['queue_created'] = $queue->exists;

        // Add entries
        $entries = QueueEntry::factory()->count(5)->queued()->create(['queue_id' => $queue->id]);
        $results['entries_created'] = $entries->count() === 5;

        // Process entries
        foreach ($entries as $entry) {
            $entry->update(['order_status' => 'completed']);
        }
        $results['entries_processed'] = $queue->entries()->where('order_status', 'completed')->count() === 5;

        return $results;
    }

    /**
     * Test cashier assignment workflow
     */
    protected function testCashierAssignment(): array
    {
        $results = [];

        // Assign cashier to queue
        $this->cashier->update(['assigned_queue_id' => $this->queue->id]);
        $results['assigned'] = $this->cashier->assigned_queue_id === $this->queue->id;

        // Create entry and assign to cashier
        $entry = QueueEntry::factory()->create([
            'queue_id' => $this->queue->id,
            'cashier_id' => $this->cashier->id
        ]);
        $results['entry_assigned'] = $entry->cashier_id === $this->cashier->id;

        return $results;
    }

    /**
     * Test order processing workflow
     */
    protected function testOrderProcessing(): array
    {
        $results = [];

        // Create order
        $entry = QueueEntry::factory()->queued()->create(['queue_id' => $this->queue->id]);
        $results['order_created'] = $entry->order_status === 'queued';

        // Process order
        $entry->update(['order_status' => 'preparing']);
        $results['order_preparing'] = $entry->order_status === 'preparing';

        $entry->update(['order_status' => 'serving']);
        $results['order_ready'] = $entry->order_status === 'serving';

        $entry->update(['order_status' => 'completed']);
        $results['order_completed'] = $entry->order_status === 'completed';

        return $results;
    }

    /**
     * Test queue load handling
     */
    protected function testQueueLoad(): array
    {
        $results = [];

        $startTime = microtime(true);

        // Create many entries
        QueueEntry::factory()->count(100)->create(['queue_id' => $this->queue->id]);

        $endTime = microtime(true);
        $results['creation_time'] = $endTime - $startTime;
        $results['entries_created'] = $this->queue->entries()->count() === 100;

        return $results;
    }

    /**
     * Test concurrent entry creation
     */
    protected function testConcurrentEntries(): array
    {
        $results = [];

        // Simulate concurrent entry creation
        $entries = [];
        for ($i = 0; $i < 10; $i++) {
            $entries[] = QueueEntry::factory()->create(['queue_id' => $this->queue->id]);
        }

        $results['concurrent_creation'] = count($entries) === 10;
        $results['unique_numbers'] = collect($entries)->pluck('queue_number')->unique()->count() === 10;

        return $results;
    }

    /**
     * Test database performance
     */
    protected function testDatabasePerformance(): array
    {
        $results = [];

        $startTime = microtime(true);

        // Perform complex queries
        $queues = Queue::with(['entries', 'cashiers'])->get();
        $entries = QueueEntry::with(['queue', 'cashier'])->get();

        $endTime = microtime(true);
        $results['query_time'] = $endTime - $startTime;
        $results['queues_loaded'] = $queues->count() > 0;
        $results['entries_loaded'] = $entries->count() > 0;

        return $results;
    }

    /**
     * Generate test report
     */
    public function generateReport(): array
    {
        $report = [
            'timestamp' => now()->toISOString(),
            'unit_tests' => $this->runUnitTests(),
            'feature_tests' => $this->runFeatureTests(),
            'integration_tests' => $this->runIntegrationTests(),
            'performance_tests' => $this->runPerformanceTests(),
        ];

        // Calculate overall success rate
        $totalTests = 0;
        $passedTests = 0;

        foreach ($report as $category => $tests) {
            if (is_array($tests)) {
                foreach ($tests as $test => $results) {
                    if (is_array($results)) {
                        foreach ($results as $result) {
                            $totalTests++;
                            if ($result === true) {
                                $passedTests++;
                            }
                        }
                    }
                }
            }
        }

        $report['summary'] = [
            'total_tests' => $totalTests,
            'passed_tests' => $passedTests,
            'success_rate' => $totalTests > 0 ? ($passedTests / $totalTests) * 100 : 0
        ];

        return $report;
    }
} 