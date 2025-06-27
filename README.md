<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

# Queue Management System

A comprehensive Laravel-based queue management system with support for regular and inventory-based queues, cashier management, customer tracking, and real-time display features.

## Features

- **Dual Queue Types**: Support for both regular and inventory-based queues
- **Cashier Management**: Assign and manage cashiers for queues
- **Customer Tracking**: QR code-based customer tracking with real-time updates
- **Screen Layouts**: Customizable TV display layouts with widgets
- **Real-time Updates**: Live queue status and customer position updates
- **Inventory Management**: Stock tracking for inventory-based queues
- **ID Reset**: Auto-increment IDs reset to 0 after record deletion

## ID Reset Functionality

The system automatically resets auto-increment IDs to 0 after deleting records from all major tables:

- **Users**: `users` table
- **Queues**: `queues` table  
- **Queue Entries**: `queue_entries` table
- **Cashiers**: `cashiers` table
- **Screen Layouts**: `screen_layouts` table

This ensures that new records always start with ID 1 after deletion, maintaining clean and predictable ID sequences.

## API Endpoints

### Authentication
- `POST /api/register` - User registration
- `POST /api/login` - User login
- `GET /api/user` - Get authenticated user
- `PUT /api/user` - Update user profile
- `DELETE /api/user` - Delete user account

### Queues
- `GET /api/queues` - List all queues
- `POST /api/queues` - Create a new queue
- `GET /api/queues/{id}` - Get queue details
- `PUT /api/queues/{id}` - Update queue
- `DELETE /api/queues/{id}` - Delete queue (resets ID to 0)

### Queue Entries
- `GET /api/entries` - List all entries
- `POST /api/entries` - Create a new entry
- `GET /api/entries/{id}` - Get entry details
- `PUT /api/entries/{id}` - Update entry
- `DELETE /api/entries/{id}` - Delete entry (resets ID to 0)

### Cashiers
- `GET /api/cashiers` - List all cashiers
- `POST /api/cashiers` - Create a new cashier
- `GET /api/cashiers/{id}` - Get cashier details
- `PUT /api/cashiers/{id}` - Update cashier
- `DELETE /api/cashiers/{id}` - Delete cashier (resets ID to 0)

### Screen Layouts
- `GET /api/layouts` - List all layouts
- `POST /api/layouts` - Create a new layout
- `GET /api/layouts/{id}` - Get layout details
- `PUT /api/layouts/{id}` - Update layout
- `DELETE /api/layouts/{id}` - Delete layout (resets ID to 0)

## Installation

1. Clone the repository
2. Install dependencies: `composer install`
3. Copy `.env.example` to `.env` and configure your database
4. Run migrations: `php artisan migrate`
5. Seed the database: `php artisan db:seed`
6. Start the server: `php artisan serve`

## Testing

Run the comprehensive test suite:
```bash
php artisan test
```

Test the ID reset functionality:
```bash
php test_id_reset_demo.php
```

## Documentation

- [Queue API Documentation](QUEUE_API_DOCUMENTATION.md)
- [Queue Entry API Documentation](QUEUE_ENTRY_API_DOCUMENTATION.md)
- [Cashier API Documentation](CASHIER_API_DOCUMENTATION.md)
- [Customer Tracking API Documentation](CUSTOMER_TRACKING_API_DOCUMENTATION.md)
- [Screen Layout API Documentation](SCREEN_LAYOUT_API_DOCUMENTATION.md)
- [Widget API Documentation](WIDGET_API_DOCUMENTATION.md)
- [Testing Guide](TESTING_GUIDE.md)
