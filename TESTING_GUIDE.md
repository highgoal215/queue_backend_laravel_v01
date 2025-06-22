# Comprehensive Testing Guide

This guide provides detailed information about testing the Laravel Queue Management System, including how to run tests, what each test covers, and best practices.

## Table of Contents

1. [Overview](#overview)
2. [Test Structure](#test-structure)
3. [Running Tests](#running-tests)
4. [Test Categories](#test-categories)
5. [Test Coverage](#test-coverage)
6. [Best Practices](#best-practices)
7. [Troubleshooting](#troubleshooting)

## Overview

The testing suite is designed to provide comprehensive coverage of the queue management system, including:

- **Unit Tests**: Test individual components and business logic
- **Feature Tests**: Test API endpoints and user interactions
- **Integration Tests**: Test complete workflows and system integration
- **Performance Tests**: Test system performance under load

## Test Structure

```
tests/
├── Unit/                    # Unit tests for services and models
│   ├── QueueServiceTest.php
│   └── ExampleTest.php
├── Feature/                 # Feature tests for API endpoints
│   ├── QueueTest.php
│   ├── QueueEntryTest.php
│   ├── CashierTest.php
│   ├── AuthTest.php
│   └── ExampleTest.php
├── TestRunner.php          # Custom test runner for comprehensive testing
└── TestCase.php           # Base test case with common utilities
```

## Running Tests

### Basic Test Commands

```bash
# Run all tests
php artisan test

# Run specific test suites
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature

# Run specific test file
php artisan test tests/Feature/QueueTest.php

# Run specific test method
php artisan test --filter=testMethodName
```

### Comprehensive Test Command

```bash
# Run comprehensive tests with detailed report
php artisan test:comprehensive --report

# Run specific test types
php artisan test:comprehensive --type=unit
php artisan test:comprehensive --type=feature
php artisan test:comprehensive --type=integration
php artisan test:comprehensive --type=performance

# Run with coverage
php artisan test:comprehensive --coverage

# Run in parallel for faster execution
php artisan test:comprehensive --parallel

# Filter tests by name pattern
php artisan test:comprehensive --filter=Queue
```

### PHPUnit Commands

```bash
# Run with verbose output
./vendor/bin/phpunit --verbose

# Run with coverage report
./vendor/bin/phpunit --coverage-html=coverage

# Run specific test class
./vendor/bin/phpunit tests/Feature/QueueTest.php

# Run with specific configuration
./vendor/bin/phpunit --configuration=phpunit.xml
```

## Test Categories

### 1. Unit Tests (`tests/Unit/`)

Unit tests focus on testing individual components in isolation.

#### QueueServiceTest
- **Purpose**: Test QueueService business logic
- **Coverage**:
  - Queue creation and management
  - Queue status operations (pause, resume, close)
  - Queue number management (call next, skip, recall)
  - Stock adjustment for inventory queues
  - Queue statistics and analytics
  - Error handling and edge cases

#### Key Test Methods:
```php
it_can_get_all_queues()
it_can_create_a_queue()
it_can_get_queue_statistics()
it_can_reset_queue()
it_can_pause_queue()
it_can_resume_queue()
it_can_call_next_number()
it_can_skip_current_number()
it_can_adjust_stock_for_inventory_queue()
it_can_undo_last_entry()
it_can_get_queue_analytics()
```

### 2. Feature Tests (`tests/Feature/`)

Feature tests test complete API endpoints and user interactions.

#### QueueTest
- **Purpose**: Test Queue API endpoints
- **Coverage**:
  - CRUD operations for queues
  - Queue control operations (reset, pause, resume, close)
  - Queue number operations (call next, skip, recall)
  - Stock management for inventory queues
  - Queue analytics and statistics
  - Authentication and authorization

#### QueueEntryTest
- **Purpose**: Test Queue Entry API endpoints
- **Coverage**:
  - CRUD operations for queue entries
  - Entry status management
  - Entry search and filtering
  - Bulk operations
  - Entry timeline and history
  - Cashier assignment

#### CashierTest
- **Purpose**: Test Cashier API endpoints
- **Coverage**:
  - CRUD operations for cashiers
  - Cashier assignment to queues
  - Cashier status management
  - Queue-cashier relationships

#### AuthTest
- **Purpose**: Test Authentication API endpoints
- **Coverage**:
  - User registration
  - User login/logout
  - Token management
  - User profile management
  - Input validation

### 3. Integration Tests

Integration tests test complete workflows and system integration.

#### Queue Workflow
- Complete queue lifecycle from creation to completion
- Entry processing workflow
- Cashier assignment and management
- Real-time status updates

#### Order Processing
- End-to-end order processing
- Status transitions
- Error handling and recovery
- Performance under load

### 4. Performance Tests

Performance tests ensure the system can handle expected load.

#### Load Testing
- Queue creation with many entries
- Concurrent entry creation
- Database query performance
- Memory usage optimization

## Test Coverage

### API Endpoints Covered

#### Queue Management
- `GET /api/queues` - List all queues
- `POST /api/queues` - Create new queue
- `GET /api/queues/{id}` - Get queue details
- `PUT /api/queues/{id}` - Update queue
- `DELETE /api/queues/{id}` - Delete queue
- `POST /api/queues/{id}/reset` - Reset queue
- `POST /api/queues/{id}/pause` - Pause queue
- `POST /api/queues/{id}/resume` - Resume queue
- `POST /api/queues/{id}/close` - Close queue
- `GET /api/queues/{id}/status` - Get queue status
- `POST /api/queues/{id}/call-next` - Call next number
- `POST /api/queues/{id}/skip` - Skip current number
- `POST /api/queues/{id}/recall` - Recall current number
- `POST /api/queues/{id}/adjust-stock` - Adjust stock
- `POST /api/queues/{id}/undo-last-entry` - Undo last entry
- `GET /api/queues/{id}/entries` - Get queue entries
- `GET /api/queues/{id}/analytics` - Get queue analytics

#### Queue Entry Management
- `GET /api/entries` - List all entries
- `POST /api/entries` - Create new entry
- `GET /api/entries/{id}` - Get entry details
- `PUT /api/entries/{id}` - Update entry
- `DELETE /api/entries/{id}` - Delete entry
- `PATCH /api/entries/{id}/status` - Update entry status
- `POST /api/entries/{id}/cancel` - Cancel entry
- `GET /api/entries/{id}/timeline` - Get entry timeline
- `GET /api/entries/status/{status}` - Get entries by status
- `GET /api/entries/cashier/{id}` - Get entries by cashier
- `GET /api/entries/stats` - Get entry statistics
- `GET /api/entries/search` - Search entries
- `POST /api/entries/bulk-update-status` - Bulk update status

#### Cashier Management
- `GET /api/cashiers` - List all cashiers
- `POST /api/cashiers` - Create new cashier
- `GET /api/cashiers/{id}` - Get cashier details
- `PUT /api/cashiers/{id}` - Update cashier
- `DELETE /api/cashiers/{id}` - Delete cashier
- `POST /api/cashiers/{id}/assign` - Assign cashier to queue
- `POST /api/cashiers/{id}/set-active` - Set cashier active status
- `GET /api/queues-with-cashiers` - Get queues with cashiers

#### Authentication
- `POST /api/register` - User registration
- `POST /api/login` - User login
- `GET /api/user` - Get authenticated user
- `PUT /api/user` - Update user profile

### Business Logic Covered

#### Queue Operations
- Queue creation with validation
- Queue status management (active, paused, closed)
- Queue number progression
- Stock management for inventory queues
- Queue analytics and reporting

#### Entry Management
- Entry creation with automatic numbering
- Status transitions (queued → preparing → ready → completed)
- Entry cancellation and modification
- Search and filtering capabilities
- Bulk operations

#### Cashier Management
- Cashier assignment to queues
- Availability management
- Performance tracking
- Queue-cashier relationships

#### Authentication & Authorization
- User registration and validation
- Token-based authentication
- Profile management
- Input validation and security

## Best Practices

### 1. Test Organization

- **Arrange-Act-Assert**: Structure tests with clear sections
- **Descriptive Names**: Use descriptive test method names
- **Single Responsibility**: Each test should test one specific behavior
- **Independent Tests**: Tests should not depend on each other

### 2. Test Data Management

- **Factories**: Use Laravel factories for test data generation
- **Database Transactions**: Use database transactions for test isolation
- **Clean State**: Ensure tests start with a clean database state

### 3. API Testing

- **Status Codes**: Verify correct HTTP status codes
- **Response Structure**: Validate JSON response structure
- **Authentication**: Test both authenticated and unauthenticated access
- **Validation**: Test input validation and error handling

### 4. Performance Testing

- **Load Testing**: Test system performance under expected load
- **Memory Usage**: Monitor memory usage during tests
- **Database Queries**: Optimize database queries for performance
- **Concurrent Access**: Test concurrent access scenarios

### 5. Error Handling

- **Edge Cases**: Test edge cases and error conditions
- **Validation Errors**: Test input validation
- **Database Errors**: Test database constraint violations
- **Network Errors**: Test network-related error scenarios

## Troubleshooting

### Common Issues

#### 1. Database Connection Issues
```bash
# Ensure test database is configured
php artisan config:clear
php artisan migrate --env=testing
```

#### 2. Factory Issues
```bash
# Regenerate autoload files
composer dump-autoload
```

#### 3. Test Environment Issues
```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

#### 4. Memory Issues
```bash
# Increase memory limit for tests
php -d memory_limit=512M artisan test
```

### Debugging Tests

#### 1. Verbose Output
```bash
php artisan test --verbose
```

#### 2. Specific Test Debugging
```bash
# Run specific test with debug output
php artisan test --filter=testMethodName --verbose
```

#### 3. Database Debugging
```php
// In test methods, use dd() for debugging
dd($response->json());
dd($model->toArray());
```

### Test Reports

#### 1. Coverage Reports
```bash
# Generate HTML coverage report
php artisan test:comprehensive --coverage
```

#### 2. Custom Reports
```bash
# Generate detailed test report
php artisan test:comprehensive --report
```

#### 3. Report Location
- Coverage reports: `coverage/` directory
- Test reports: `storage/app/test-reports/` directory

## Continuous Integration

### GitHub Actions Example

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    steps:
    - uses: actions/checkout@v2
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.2'
        
    - name: Install dependencies
      run: composer install -q --no-ansi --no-interaction --no-scripts --no-progress --prefer-dist
      
    - name: Copy environment file
      run: cp .env.example .env
      
    - name: Generate key
      run: php artisan key:generate
      
    - name: Create database
      run: |
        mkdir -p database
        touch database/database.sqlite
      
    - name: Run migrations
      run: php artisan migrate --force
      
    - name: Run tests
      run: php artisan test
```

## Conclusion

This comprehensive testing suite ensures that your queue management system is robust, reliable, and performant. By following the testing best practices outlined in this guide, you can maintain high code quality and catch issues early in the development process.

For additional support or questions about testing, refer to:
- [Laravel Testing Documentation](https://laravel.com/docs/testing)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Laravel Sanctum Documentation](https://laravel.com/docs/sanctum) 