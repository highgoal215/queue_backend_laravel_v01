<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Queue;
use App\Models\User;
use App\Services\CashierService;

try {
    echo "=== DEBUGGING CASHIER CREATION ===\n\n";
    
    // 1. Check if queues exist
    echo "1. Checking queues:\n";
    $queues = Queue::all(['id', 'name']);
    if ($queues->count() > 0) {
        foreach ($queues as $queue) {
            echo "   Queue ID: {$queue->id} - Name: {$queue->name}\n";
        }
    } else {
        echo "   No queues found in database\n";
    }
    
    // 2. Check if user exists
    echo "\n2. Checking user:\n";
    $user = User::first();
    if ($user) {
        echo "   User: {$user->name} ({$user->email})\n";
    } else {
        echo "   No users found\n";
        exit;
    }
    
    // 3. Test cashier creation with your exact data
    echo "\n3. Testing cashier creation:\n";
    $cashierData = [
        "name" => "John  ruff",
        "employee_id" => "EMP001",
        "email" => "john@exa.com",
        "phone" => "123456778890",
        "role" => "cashier-1",
        "shift_start" => "09:00",
        "shift_end" => "17:00",
        "status" => "active",
        "is_active" => true,
        "is_available" => true,
        "assigned_queue_id" => 2
    ];
    
    echo "   Data to create:\n";
    foreach ($cashierData as $key => $value) {
        echo "   - {$key}: " . (is_bool($value) ? ($value ? 'true' : 'false') : $value) . "\n";
    }
    
    // Check if queue ID 2 exists
    if (isset($cashierData['assigned_queue_id'])) {
        $queue = Queue::find($cashierData['assigned_queue_id']);
        if (!$queue) {
            echo "   ❌ ERROR: Queue ID {$cashierData['assigned_queue_id']} does not exist!\n";
            echo "   Available queue IDs: " . $queues->pluck('id')->implode(', ') . "\n";
        } else {
            echo "   ✅ Queue ID {$cashierData['assigned_queue_id']} exists: {$queue->name}\n";
        }
    }
    
    // 4. Try to create cashier
    echo "\n4. Attempting to create cashier:\n";
    $cashierService = new CashierService();
    
    try {
        $cashier = $cashierService->createCashier($cashierData);
        echo "   ✅ Cashier created successfully!\n";
        echo "   Cashier ID: {$cashier->id}\n";
        echo "   Cashier Name: {$cashier->name}\n";
    } catch (Exception $e) {
        echo "   ❌ Error creating cashier: " . $e->getMessage() . "\n";
        echo "   Stack trace: " . $e->getTraceAsString() . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
} 