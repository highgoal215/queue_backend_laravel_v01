<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;

try {
    echo "=== TESTING API ENDPOINT ===\n\n";
    
    // Get fresh token
    $user = User::first();
    if (!$user) {
        echo "No user found\n";
        exit;
    }
    
    // Delete old tokens and create new one
    $user->tokens()->delete();
    $token = $user->createToken('api-test')->plainTextToken;
    
    echo "Fresh token: {$token}\n\n";
    
    // Test data
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
    
    // Simulate API request
    $request = \Illuminate\Http\Request::create('/api/cashiers', 'POST', $cashierData);
    $request->headers->set('Authorization', 'Bearer ' . $token);
    $request->headers->set('Accept', 'application/json');
    $request->headers->set('Content-Type', 'application/json');
    
    // Get the response
    $response = $app->handle($request);
    
    echo "Response Status: " . $response->getStatusCode() . "\n";
    echo "Response Headers:\n";
    foreach ($response->headers->all() as $name => $values) {
        echo "  {$name}: " . implode(', ', $values) . "\n";
    }
    
    echo "\nResponse Body:\n";
    echo $response->getContent() . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
} 