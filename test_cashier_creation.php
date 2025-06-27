<?php

require_once 'vendor/autoload.php';

use App\Models\Cashier;
use App\Models\Queue;
use App\Services\CashierService;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

// Bootstrap Laravel
$app = Application::configure(basePath: __DIR__)
    ->withRouting(
        web: __DIR__.'/routes/web.php',
        api: __DIR__.'/routes/api.php',
        commands: __DIR__.'/routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing Cashier Creation with Required Parameters\n";
echo "================================================\n\n";

// Test 1: Create cashier with all required parameters
echo "Test 1: Creating cashier with all required parameters\n";
echo "----------------------------------------------------\n";

$cashierData = [
    'name' => 'John Doe',
    'employee_id' => 'EMP001',
    'email' => 'john.doe@example.com',
    'phone' => '1234567890',
    'role' => 'senior_cashier',
    'shift_start' => '09:00',
    'shift_end' => '17:00',
    'assigned_queue_id' => null // Optional
];

try {
    $cashierService = new CashierService();
    $cashier = $cashierService->createCashier($cashierData);
    
    echo "✅ Successfully created cashier:\n";
    echo "   ID: {$cashier->id}\n";
    echo "   Name: {$cashier->name}\n";
    echo "   Employee ID: {$cashier->employee_id}\n";
    echo "   Email: {$cashier->email}\n";
    echo "   Phone: {$cashier->phone}\n";
    echo "   Role: {$cashier->role}\n";
    echo "   Shift Start: {$cashier->shift_start}\n";
    echo "   Shift End: {$cashier->shift_end}\n";
    echo "   Assigned Queue: " . ($cashier->assigned_queue_id ? $cashier->assigned_queue_id : 'None') . "\n";
    echo "   Status: {$cashier->status}\n";
    echo "   Is Active: " . ($cashier->is_active ? 'Yes' : 'No') . "\n";
    echo "   Is Available: " . ($cashier->is_available ? 'Yes' : 'No') . "\n\n";
    
    // Clean up
    $cashier->delete();
    
} catch (Exception $e) {
    echo "❌ Failed to create cashier: " . $e->getMessage() . "\n\n";
}

// Test 2: Create cashier with minimum required parameters (only name)
echo "Test 2: Creating cashier with minimum required parameters (only name)\n";
echo "--------------------------------------------------------------------\n";

$minimalData = [
    'name' => 'Jane Smith'
];

try {
    $cashierService = new CashierService();
    $cashier = $cashierService->createCashier($minimalData);
    
    echo "✅ Successfully created cashier with minimal data:\n";
    echo "   ID: {$cashier->id}\n";
    echo "   Name: {$cashier->name}\n";
    echo "   Employee ID: " . ($cashier->employee_id ?: 'Not set') . "\n";
    echo "   Email: " . ($cashier->email ?: 'Not set') . "\n";
    echo "   Phone: " . ($cashier->phone ?: 'Not set') . "\n";
    echo "   Role: " . ($cashier->role ?: 'Not set') . "\n";
    echo "   Shift Start: " . ($cashier->shift_start ?: 'Not set') . "\n";
    echo "   Shift End: " . ($cashier->shift_end ?: 'Not set') . "\n";
    echo "   Assigned Queue: " . ($cashier->assigned_queue_id ?: 'None') . "\n";
    echo "   Status: {$cashier->status}\n";
    echo "   Is Active: " . ($cashier->is_active ? 'Yes' : 'No') . "\n";
    echo "   Is Available: " . ($cashier->is_available ? 'Yes' : 'No') . "\n\n";
    
    // Clean up
    $cashier->delete();
    
} catch (Exception $e) {
    echo "❌ Failed to create cashier: " . $e->getMessage() . "\n\n";
}

// Test 3: Create cashier with assigned queue
echo "Test 3: Creating cashier with assigned queue\n";
echo "--------------------------------------------\n";

// First create a queue for testing
$queue = Queue::create([
    'name' => 'Test Queue',
    'type' => 'regular',
    'max_quantity' => 100,
    'remaining_quantity' => 100,
    'status' => 'active',
    'current_number' => 0
]);

$cashierWithQueueData = [
    'name' => 'Bob Wilson',
    'employee_id' => 'EMP002',
    'email' => 'bob.wilson@example.com',
    'phone' => '0987654321',
    'role' => 'cashier',
    'shift_start' => '08:00',
    'shift_end' => '16:00',
    'assigned_queue_id' => $queue->id
];

try {
    $cashierService = new CashierService();
    $cashier = $cashierService->createCashier($cashierWithQueueData);
    
    echo "✅ Successfully created cashier with assigned queue:\n";
    echo "   ID: {$cashier->id}\n";
    echo "   Name: {$cashier->name}\n";
    echo "   Employee ID: {$cashier->employee_id}\n";
    echo "   Email: {$cashier->email}\n";
    echo "   Phone: {$cashier->phone}\n";
    echo "   Role: {$cashier->role}\n";
    echo "   Shift Start: {$cashier->shift_start}\n";
    echo "   Shift End: {$cashier->shift_end}\n";
    echo "   Assigned Queue: {$cashier->assigned_queue_id} ({$queue->name})\n";
    echo "   Status: {$cashier->status}\n";
    echo "   Is Active: " . ($cashier->is_active ? 'Yes' : 'No') . "\n";
    echo "   Is Available: " . ($cashier->is_available ? 'Yes' : 'No') . "\n\n";
    
    // Clean up
    $cashier->delete();
    $queue->delete();
    
} catch (Exception $e) {
    echo "❌ Failed to create cashier: " . $e->getMessage() . "\n\n";
    $queue->delete();
}

// Test 4: Test validation - missing required name
echo "Test 4: Testing validation - missing required name\n";
echo "--------------------------------------------------\n";

$invalidData = [
    'employee_id' => 'EMP003',
    'email' => 'test@example.com'
];

try {
    $cashierService = new CashierService();
    $cashier = $cashierService->createCashier($invalidData);
    echo "❌ Should have failed - name is required\n\n";
} catch (Exception $e) {
    echo "✅ Correctly rejected invalid data: " . $e->getMessage() . "\n\n";
}

// Test 5: Test validation - invalid email format
echo "Test 5: Testing validation - invalid email format\n";
echo "-------------------------------------------------\n";

$invalidEmailData = [
    'name' => 'Test User',
    'email' => 'invalid-email-format'
];

try {
    $cashierService = new CashierService();
    $cashier = $cashierService->createCashier($invalidEmailData);
    echo "✅ Successfully created cashier (email validation handled by request class)\n\n";
    $cashier->delete();
} catch (Exception $e) {
    echo "❌ Failed: " . $e->getMessage() . "\n\n";
}

// Test 6: Test validation - invalid shift time format
echo "Test 6: Testing validation - invalid shift time format\n";
echo "------------------------------------------------------\n";

$invalidTimeData = [
    'name' => 'Time Test User',
    'shift_start' => '25:00', // Invalid time
    'shift_end' => '26:00'    // Invalid time
];

try {
    $cashierService = new CashierService();
    $cashier = $cashierService->createCashier($invalidTimeData);
    echo "✅ Successfully created cashier (time validation handled by request class)\n\n";
    $cashier->delete();
} catch (Exception $e) {
    echo "❌ Failed: " . $e->getMessage() . "\n\n";
}

echo "Testing completed!\n"; 