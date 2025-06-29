<?php

require_once 'vendor/autoload.php';

use App\Models\Cashier;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Checking existing cashiers...\n\n";

$cashiers = Cashier::all();

if ($cashiers->count() > 0) {
    echo "Found " . $cashiers->count() . " existing cashiers:\n";
    foreach ($cashiers as $cashier) {
        echo "ID: {$cashier->id}, Name: {$cashier->name}, Employee ID: {$cashier->employee_id}\n";
    }
    
    echo "\nCleaning up existing cashiers...\n";
    foreach ($cashiers as $cashier) {
        $cashier->delete();
        echo "Deleted cashier ID: {$cashier->id}\n";
    }
    echo "Cleanup completed.\n";
} else {
    echo "No existing cashiers found.\n";
}

echo "\nReady for testing!\n"; 