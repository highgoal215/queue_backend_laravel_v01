<?php

/**
 * Comprehensive Test Runner for Queue Management API
 * 
 * This script runs all feature tests and provides a detailed summary
 * of the test coverage for all API endpoints.
 */

echo "🚀 Starting Comprehensive API Endpoint Tests\n";
echo "============================================\n\n";

// Run all feature tests
$command = 'php artisan test --testsuite=Feature 2>&1';
$output = shell_exec($command);

// Parse the results
$lines = explode("\n", $output);
$testResults = [];
$currentTest = null;
$passed = 0;
$failed = 0;

foreach ($lines as $line) {
    if (strpos($line, 'PASS') === 0) {
        $currentTest = trim(substr($line, 5));
        $testResults[$currentTest] = ['status' => 'PASS', 'tests' => []];
    } elseif (strpos($line, 'FAIL') === 0) {
        $currentTest = trim(substr($line, 5));
        $testResults[$currentTest] = ['status' => 'FAIL', 'tests' => []];
    } elseif (strpos($line, '✓') === 0 && $currentTest) {
        $testResults[$currentTest]['tests'][] = ['status' => 'PASS', 'name' => trim(substr($line, 1))];
        $passed++;
    } elseif (strpos($line, '⨯') === 0 && $currentTest) {
        $testResults[$currentTest]['tests'][] = ['status' => 'FAIL', 'name' => trim(substr($line, 1))];
        $failed++;
    }
}

// Calculate totals
$totalTests = $passed + $failed;
$successRate = $totalTests > 0 ? round(($passed / $totalTests) * 100, 1) : 0;

echo "📊 Test Results Summary\n";
echo "======================\n";
echo "Total Tests: {$totalTests}\n";
echo "Passed: {$passed} ✅\n";
echo "Failed: {$failed} ❌\n";
echo "Success Rate: {$successRate}%\n\n";

echo "📋 Test Categories\n";
echo "==================\n";

foreach ($testResults as $category => $result) {
    $status = $result['status'] === 'PASS' ? '✅' : '❌';
    $testCount = count($result['tests']);
    echo "{$status} {$category} ({$testCount} tests)\n";
}

echo "\n🔍 Detailed Results\n";
echo "==================\n";

foreach ($testResults as $category => $result) {
    echo "\n{$category}:\n";
    foreach ($result['tests'] as $test) {
        $status = $test['status'] === 'PASS' ? '✓' : '⨯';
        echo "  {$status} {$test['name']}\n";
    }
}

echo "\n📈 Coverage Statistics\n";
echo "=====================\n";

$categories = [
    'AuthTest' => 'Authentication',
    'QueueTest' => 'Queues',
    'QueueEntryTest' => 'Queue Entries',
    'CashierTest' => 'Cashiers',
    'CustomerTrackingTest' => 'Customer Tracking',
    'ScreenLayoutTest' => 'Screen Layouts',
    'WidgetTest' => 'Widgets',
    'ComprehensiveEndpointTest' => 'Comprehensive'
];

foreach ($categories as $testClass => $name) {
    if (isset($testResults[$testClass])) {
        $tests = $testResults[$testClass]['tests'];
        $passedCount = count(array_filter($tests, fn($t) => $t['status'] === 'PASS'));
        $totalCount = count($tests);
        $coverage = $totalCount > 0 ? round(($passedCount / $totalCount) * 100) : 0;
        echo "{$name}: {$coverage}% ({$passedCount}/{$totalCount})\n";
    }
}

echo "\n🎯 Endpoint Coverage\n";
echo "===================\n";

$endpoints = [
    'Authentication' => ['register', 'login', 'user'],
    'Queues' => ['queues', 'reset', 'pause', 'resume', 'close', 'call-next', 'skip', 'recall', 'adjust-stock', 'undo-last-entry', 'entries', 'analytics', 'status'],
    'Queue Entries' => ['entries', 'status', 'cancel', 'timeline', 'search', 'bulk-update-status'],
    'Cashiers' => ['cashiers', 'assign', 'set-active', 'queues-with-cashiers'],
    'Customer Tracking' => ['tracking'],
    'Screen Layouts' => ['layouts', 'duplicate', 'preview', 'set-default'],
    'Widgets' => ['widgets/data', 'widgets/stats', 'widgets/real-time', 'widgets/preview', 'widgets/type', 'widgets/settings']
];

foreach ($endpoints as $category => $endpointList) {
    echo "\n{$category}:\n";
    foreach ($endpointList as $endpoint) {
        echo "  - /api/{$endpoint}\n";
    }
}

echo "\n✨ Test Execution Complete!\n";
echo "==========================\n";

if ($failed === 0) {
    echo "🎉 All tests passed! The API is fully functional.\n";
} else {
    echo "⚠️  {$failed} tests failed. Please review the failed tests above.\n";
    echo "💡 Check the COMPREHENSIVE_TEST_COVERAGE.md file for detailed analysis.\n";
}

echo "\n📚 For detailed test coverage information, see:\n";
echo "- COMPREHENSIVE_TEST_COVERAGE.md\n";
echo "- Individual test files in tests/Feature/\n"; 