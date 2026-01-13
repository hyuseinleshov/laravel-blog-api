## Overview

This project demonstrates Laravel development practices including clean architecture, comprehensive testing, and meaningful application of design patterns. The API follows REST conventions and includes a Filament admin panel for easy resource management.

## Features

- **Author Authentication**: Sanctum-based token authentication with registration, login, and logout
- **Authorization Policies**: Role-based access control for posts and tags
- **Posts Management**: Full CRUD operations with status workflow (draft, published, archived)
- **Tags System**: Many-to-many relationship with posts
- **User Attribution**: Posts are associated with authors
- **Rate Limiting**: Throttled authentication endpoints (5 requests per minute)
- **Admin Panel**: Filament-powered UI for managing posts and tags
- **Comprehensive Testing**: Feature tests with Pest framework
- **API Versioning**: Structured v1 API routes

## Tech Stack

- **Framework**: Laravel 12
- **Database**: SQLite (development)
- **Authentication**: Laravel Sanctum
- **Testing**: Pest
- **Admin Panel**: Filament
- **Code Quality**: Laravel Pint

## Architectural Decisions

This project follows a clean architecture with clear separation of concerns:

### Repository/Action/Service Architecture

**Repositories** (`app/Repositories/`)
- Handle data access only (queries, CRUD operations)
- No business logic or side effects
- Example: `PostRepository`, `TagRepository`

**Actions** (`app/Actions/`)
- Contain business operations with side effects or multiple steps
- Used when operations are more complex than simple CRUD
- Example: `StorePostAction` (creates post + attaches tags), `UpdatePostAction` (updates post + syncs tags)

**Services** (`app/Services/`)
- Reserved for external integrations only (Stripe, email providers, etc.)
- Currently empty - will be used for third-party API integrations in the future

**When to use Actions vs. inline operations:**
- **Use Actions**: Multi-step operations, side effects, event dispatching, complex business logic
- **Keep inline**: Simple single-model operations with no side effects (e.g., `$tag->delete()`)

### Query Builder Pattern

Listing endpoints use **Spatie Query Builder** (`app/Queries/`) for consistent filtering, sorting, and relationship loading:

**Example Usage:**
```http
# Filter posts by status
GET /api/v1/posts?filter[status]=published

# Search posts by partial title match
GET /api/v1/posts?filter[title]=Laravel

# Sort posts by title descending
GET /api/v1/posts?sort=-title

# Include relationships to avoid N+1 queries
GET /api/v1/posts?include=author,tags

# Combine multiple parameters
GET /api/v1/posts?filter[status]=published&include=author&sort=-created_at
```

See [PostQuery](app/Queries/PostQuery.php) and [TagQuery](app/Queries/TagQuery.php) for available filters, sorts, and includes.

### Form Request Validation
All input validation is handled through dedicated Form Request classes (`StorePostRequest`, `UpdatePostRequest`, etc.) to keep controllers clean and validation logic reusable.

### API Resources
JSON responses use API Resource classes to decouple internal data structures from API responses and ensure consistent output formatting. Nested resources use dedicated resource classes (e.g., `AuthorResource`, `TagResource`) with `whenLoaded()` for conditional relationship loading.

## Installation
```bash
# Clone the repository
git clone https://github.com/hyuseinleshov/laravel-blog-api
cd laravel-blog-api

# Install dependencies
composer install

# Set up environment
cp .env.example .env
php artisan key:generate

# Create database and run migrations
touch database/database.sqlite
php artisan migrate

# Seed the database with sample data
php artisan db:seed

# Create admin user for Filament
php artisan make:filament-user

# Start development server
php artisan serve
```

## API Documentation

Base URL: `http://localhost:8000/api/v1`

### Authentication & Authorization

The API uses **Laravel Sanctum** for token-based authentication. Authentication is required for creating, updating, and deleting posts and tags.

**Authentication Endpoints:**
- `POST /api/v1/auth/register` - Register a new author
- `POST /api/v1/auth/login` - Login and receive access token
- `POST /api/v1/auth/logout` - Logout and revoke current token
- `GET /api/v1/auth/me` - Get authenticated author details

**Rate Limiting:** Authentication endpoints are limited to 5 requests per minute to prevent brute force attacks.

**Authorization Policies:**
- **Posts**: Authors can only update/delete their own posts ([PostPolicy](app/Policies/PostPolicy.php))
- **Tags**: Tags can only be updated/deleted if they have no associated posts ([TagPolicy](app/Policies/TagPolicy.php))

### Resource Endpoints

**Posts:**
- `GET /api/v1/posts` - List all posts (public)
- `GET /api/v1/posts/{id}` - Get single post (public)
- `POST /api/v1/posts` - Create post (authenticated, auto-assigned to author)
- `PUT /api/v1/posts/{id}` - Update post (authenticated, author only)
- `DELETE /api/v1/posts/{id}` - Delete post (authenticated, author only)

**Tags:**
- `GET /api/v1/tags` - List all tags (public)
- `GET /api/v1/tags/{id}` - Get single tag (public)
- `POST /api/v1/tags` - Create tag (authenticated)
- `PUT /api/v1/tags/{id}` - Update tag (authenticated, only if unused)
- `DELETE /api/v1/tags/{id}` - Delete tag (authenticated, only if unused)

### Security Features

- **Password Security**: Bcrypt hashing
- **Token Authentication**: Stateless, prevents session hijacking
- **Account Status**: Inactive accounts cannot login
- **Rate Limiting**: 5 requests/minute on auth endpoints
- **Policy Authorization**: Prevents unauthorized resource access
- **Auto-assignment**: `author_id` prevents privilege escalation
- **Token Revocation**: Immediate access termination on logout

For detailed API requests, responses, and interactive testing, see the Postman collection below.

## Admin Panel

Access the Filament admin panel at `http://localhost:8000/admin`

Features:
- Visual management of posts and tags
- Multi-select tag attachment to posts
- Post status management with badges
- Search and filter capabilities

## Testing

The project includes comprehensive feature tests covering authentication, authorization, CRUD operations, and validation.

```bash
# Run all tests
php artisan test

# Run tests with coverage
php artisan test --coverage
```

## Code Quality

The codebase follows Laravel coding standards enforced by Laravel Pint:
```bash
# Check code style
./vendor/bin/pint --test

# Fix code style issues
./vendor/bin/pint
```

## Design Patterns
- **Token-Based Authentication**: Laravel Sanctum for stateless API authentication
- **Policy-Based Authorization**: Separate policy classes for resource access control
- **Repository Pattern**: Data access layer for CRUD operations
- **Action Pattern**: Business operations with side effects or multiple steps
- **Query Builder Pattern**: Spatie Query Builder for filtering, sorting, and includes
- **Form Request Validation**: Dedicated validation classes
- **API Resources**: Consistent JSON transformation with nested resource classes
- **Eager Loading**: Conditional relationship loading with `whenLoaded()` to prevent N+1 queries
- **Enum Types**: Type-safe status values (PostStatus, AuthorStatus)
- **Factory Pattern**: Test data generation
- **Database Transactions**: RefreshDatabase in tests

## Development Notes

This is an **API-only** project with no public frontend. The Filament admin panel provides a visual interface for content management but is separate from the public API surface.

## License

This project was created as a technical assessment.
