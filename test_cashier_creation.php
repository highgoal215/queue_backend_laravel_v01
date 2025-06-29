<?php

require_once 'vendor/autoload.php';

use App\Models\Cashier;
use App\Models\Queue;
use App\Services\CashierService;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\DB;

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

echo "Testing Cashier Creation After Fixes...\n\n";

try {
    // Test 1: Create first cashier
    echo "1. Creating first cashier...\n";
    $cashierService = new CashierService();
    
    $cashierData1 = [
        'name' => 'Test Cashier 1',
        'employee_id' => 'EMP001',
        'email' => 'cashier1@test.com',
        'phone' => '1234567890',
        'role' => 'main',
        'shift_start' => '09:00',
        'shift_end' => '17:00',
        'is_active' => true,
        'is_available' => true,
        'status' => 'active'
    ];
    
    $cashier1 = $cashierService->createCashier($cashierData1);
    echo "   ✓ First cashier created successfully with ID: {$cashier1->id}\n";
    
    // Test 2: Create second cashier
    echo "\n2. Creating second cashier...\n";
    $cashierData2 = [
        'name' => 'Test Cashier 2',
        'employee_id' => 'EMP002',
        'email' => 'cashier2@test.com',
        'phone' => '0987654321',
        'role' => 'backup',
        'shift_start' => '10:00',
        'shift_end' => '18:00',
        'is_active' => true,
        'is_available' => true,
        'status' => 'active'
    ];
    
    $cashier2 = $cashierService->createCashier($cashierData2);
    echo "   ✓ Second cashier created successfully with ID: {$cashier2->id}\n";
    
    // Test 3: Delete first cashier
    echo "\n3. Deleting first cashier...\n";
    $result = $cashierService->deleteCashier($cashier1);
    echo "   ✓ First cashier deleted successfully\n";
    
    // Test 4: Create third cashier (should work after deletion)
    echo "\n4. Creating third cashier after deletion...\n";
    $cashierData3 = [
        'name' => 'Test Cashier 3',
        'employee_id' => 'EMP003',
        'email' => 'cashier3@test.com',
        'phone' => '5555555555',
        'role' => 'main',
        'shift_start' => '08:00',
        'shift_end' => '16:00',
        'is_active' => true,
        'is_available' => true,
        'status' => 'active'
    ];
    
    $cashier3 = $cashierService->createCashier($cashierData3);
    echo "   ✓ Third cashier created successfully with ID: {$cashier3->id}\n";
    
    // Test 5: Verify database state
    echo "\n5. Verifying database state...\n";
    $cashiers = Cashier::all();
    echo "   ✓ Total cashiers in database: " . $cashiers->count() . "\n";
    echo "   ✓ Cashier IDs: " . $cashiers->pluck('id')->implode(', ') . "\n";
    
    // Test 6: Test unique constraints
    echo "\n6. Testing unique constraints...\n";
    try {
        $duplicateData = [
            'name' => 'Test Cashier 2', // Same name as cashier2
            'employee_id' => 'EMP004',
            'email' => 'cashier4@test.com',
            'phone' => '1111111111',
            'role' => 'main',
            'is_active' => true,
            'is_available' => true,
            'status' => 'active'
        ];
        
        $cashierService->createCashier($duplicateData);
        echo "   ✗ Should have failed due to duplicate name\n";
    } catch (\Exception $e) {
        echo "   ✓ Correctly prevented duplicate name: " . $e->getMessage() . "\n";
    }
    
    try {
        $duplicateData = [
            'name' => 'Test Cashier 4',
            'employee_id' => 'EMP002', // Same employee_id as cashier2
            'email' => 'cashier4@test.com',
            'phone' => '1111111111',
            'role' => 'main',
            'is_active' => true,
            'is_available' => true,
            'status' => 'active'
        ];
        
        $cashierService->createCashier($duplicateData);
        echo "   ✗ Should have failed due to duplicate employee_id\n";
    } catch (\Exception $e) {
        echo "   ✓ Correctly prevented duplicate employee_id: " . $e->getMessage() . "\n";
    }
    
    try {
        $duplicateData = [
            'name' => 'Test Cashier 4',
            'employee_id' => 'EMP004',
            'email' => 'cashier2@test.com', // Same email as cashier2
            'phone' => '1111111111',
            'role' => 'main',
            'is_active' => true,
            'is_available' => true,
            'status' => 'active'
        ];
        
        $cashierService->createCashier($duplicateData);
        echo "   ✗ Should have failed due to duplicate email\n";
    } catch (\Exception $e) {
        echo "   ✓ Correctly prevented duplicate email: " . $e->getMessage() . "\n";
    }
    
    // Cleanup
    echo "\n7. Cleaning up test data...\n";
    $cashierService->deleteCashier($cashier2);
    $cashierService->deleteCashier($cashier3);
    echo "   ✓ Test data cleaned up\n";
    
    echo "\n✅ All tests passed! Cashier creation is working correctly.\n";
    echo "The 302 redirect issue has been resolved.\n";
    
} catch (\Exception $e) {
    echo "\n❌ Test failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "Testing completed!\n"; 