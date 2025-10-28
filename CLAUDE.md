# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Application Overview

This is a Laravel 12 application using PostgreSQL as the database engine, built with Filament v4 for the admin interface. The application follows Laravel's modern streamlined structure and uses contemporary PHP 8.4 features.

## Core Technology Stack

- **PHP**: 8.4.13
- **Laravel Framework**: 12.35.1
- **Database**: PostgreSQL
- **Admin Panel**: Filament v4.1.10
- **Frontend**: Livewire v3.6.4, Tailwind CSS v4.1.16
- **Testing**: Pest v4.1.2
- **Code Formatting**: Laravel Pint v1.25.1

## Development Commands

### Setup and Installation
```bash
composer run setup  # Full setup: install dependencies, env setup, key generation, migrations, npm install & build
```

### Development Workflow
```bash
composer run dev     # Start all development services (server, queue, logs, vite) concurrently
npm run dev          # Frontend development with Vite
npm run build        # Build frontend assets for production
php artisan serve    # Start Laravel development server only
```

### Testing
```bash
composer run test              # Run full test suite with config clearing
php artisan test               # Run tests directly
php artisan test --filter=name # Run specific test by name
php artisan test tests/Feature/ExampleTest.php  # Run specific test file
```

### Code Quality
```bash
vendor/bin/pint --dirty  # Format code (run before committing)
```

### Database Operations
```bash
php artisan migrate           # Run pending migrations
php artisan migrate:fresh    # Drop all tables and re-run migrations
php artisan db:seed          # Run database seeders
```

## Architecture Overview

### Laravel 12 Modern Structure
- Uses streamlined structure introduced in Laravel 11+
- No `app/Http/Middleware/` directory - middleware registered in `bootstrap/app.php`
- No `app/Console/Kernel.php` - console configuration in `bootstrap/app.php` or `routes/console.php`
- Commands auto-register from `app/Console/Commands/`
- Service providers in `bootstrap/providers.php`

### Filament Admin Structure
The application uses a sophisticated Filament v4 architecture with separated concerns:

```
app/Filament/Resources/
├── Users/
│   ├── UserResource.php          # Main resource definition
│   ├── Schemas/
│   │   ├── UserForm.php         # Form schema configuration
│   │   └── UserInfolist.php     # Infolist schema configuration
│   ├── Tables/
│   │   └── UsersTable.php       # Table configuration
│   └── Pages/
│       ├── ListUsers.php        # Index page
│       ├── CreateUser.php       # Create page
│       ├── EditUser.php         # Edit page
│       └── ViewUser.php         # View page
```

This separation allows for:
- **Schemas**: Reusable form and infolist configurations
- **Tables**: Dedicated table configuration with filters, columns, actions
- **Pages**: Custom page logic and layouts
- **Resources**: Central resource definition and routing

### Database Schema
Current tables:
- `users` - Standard Laravel user authentication
- `cache`, `cache_locks` - Laravel caching system
- `jobs`, `failed_jobs`, `job_batches` - Queue system
- `sessions` - Session storage
- `password_reset_tokens` - Password reset functionality

## Testing Strategy

### Pest v4 Testing Framework
- All tests written in Pest syntax
- Feature tests in `tests/Feature/`
- Unit tests in `tests/Unit/`
- Browser tests available in `tests/Browser/` (Pest v4 capability)

### Filament Testing Patterns
```php
// Filament resource testing
livewire(CreateUser::class)
    ->fillForm(['name' => 'Test', 'email' => 'test@example.com'])
    ->call('create')
    ->assertNotified();

// Table testing
livewire(ListUsers::class)
    ->assertCanSeeTableRecords($users)
    ->searchTable('search term');
```

## Key Development Practices

### Code Standards
- PHP 8.4 constructor property promotion
- Explicit return type declarations required
- PHPDoc blocks for complex array shapes
- Laravel Pint for consistent formatting

### Laravel Conventions
- Use Artisan commands for file generation (`php artisan make:*`)
- Eloquent relationships over raw queries
- Form Request classes for validation
- Named routes with `route()` helper
- Environment variables only in config files

### Filament v4 Specifics
- File visibility defaults to `private`
- `deferFilters(false)` to disable deferred filtering
- Schema components moved to `Filament\Schemas\Components`
- All actions extend `Filament\Actions\Action`
- Use `Heroicon` enum for icons
- Use Table Repeater for forms Invoice Product Items and Payment Items. Reference docs at https://filamentphp.com/docs/4.x/forms/repeater#table-repeaters

### Frontend (Tailwind v4)
- Import with `@import "tailwindcss"` (not `@tailwind` directives)
- Use replacement utilities (e.g., `shrink-*` not `flex-shrink-*`)
- Gap utilities for spacing instead of margins

## MCP Integration

This application includes Laravel Boost MCP server providing:
- Database schema inspection and querying
- Artisan command listing and execution
- Application information and package versions
- Documentation search for Laravel ecosystem packages
- Tinker execution for debugging
- Log reading and error tracking

Always use MCP tools for Laravel-specific operations before falling back to manual approaches.

## Important Notes

- This application follows strict Cursor/Laravel Boost guidelines included in `.cursor/rules/laravel-boost.mdc`
- When frontend changes aren't reflected, check if `npm run build` or `composer run dev` needs to be run
- PostgreSQL is the configured database engine
- All tests must be written using Pest framework
- Code formatting with Pint is mandatory before commits

