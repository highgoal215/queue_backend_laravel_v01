<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Queue;

echo "Checking queue statuses:\n";
echo "=======================\n";

$queues = Queue::all();

if ($queues->count() === 0) {
    echo "No queues found in database.\n";
} else {
    foreach ($queues as $queue) {
        echo "ID: {$queue->id}, Name: {$queue->name}, Status: {$queue->status}\n";
    }
}

echo "\nTo fix the 'Queue is not active' error, you need to:\n";
echo "1. Make sure the queue status is 'active'\n";
echo "2. Or update the queue status to 'active' using the API\n";
echo "\nAPI endpoint to update queue status:\n";
echo "PUT /api/queues/{queue_id}\n";
echo "Body: {\"status\": \"active\"}\n"; 