## Overview

This project demonstrates Laravel development practices including clean architecture, comprehensive testing, and meaningful application of design patterns. The API follows REST conventions and includes a Filament admin panel for easy resource management.

## Features

- **Posts Management**: Full CRUD operations with status workflow (draft, published, archived)
- **Tags System**: Many-to-many relationship with posts
- **User Attribution**: Posts are associated with authors
- **Admin Panel**: Filament-powered UI for managing posts and tags
- **Comprehensive Testing**: Feature tests with Pest framework
- **API Versioning**: Structured v1 API routes

## Tech Stack

- **Framework**: Laravel 12.41.1
- **PHP**: 8.5.0
- **Database**: SQLite (development)
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

**Available filters:**
- Posts: `status` (exact), `user_id` (exact), `title` (partial)
- Tags: `name` (partial)

**Available sorts:**
- Posts: `created_at`, `updated_at`, `title` (prefix with `-` for descending)
- Tags: `created_at`, `updated_at`, `name`

**Available includes:**
- Posts: `author`, `tags`

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

### Posts Endpoints

#### Get All Posts
```http
GET /api/v1/posts
```

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "title": "Getting Started with Laravel",
      "content": "Laravel is a powerful PHP framework...",
      "status": "published",
      "author": {
        "id": 1,
        "name": "John Doe"
      },
      "tags": [
        {"id": 1, "name": "Laravel"},
        {"id": 2, "name": "PHP"}
      ],
      "created_at": "2024-12-09T10:30:00.000000Z",
      "updated_at": "2024-12-09T10:30:00.000000Z"
    }
  ]
}
```

#### Create Post
```http
POST /api/v1/posts
Content-Type: application/json
```

**Request:**
```json
{
  "title": "My New Blog Post",
  "content": "This is the content of my blog post. It must be at least 200 characters long to meet validation requirements. Here's some additional content to meet that requirement and provide meaningful information.",
  "status": "published",
  "user_id": 1,
  "tag_ids": [1, 2]
}
```

**Validation Rules:**
- `title`: required, string, min:10, max:255
- `content`: nullable, string, min:200
- `status`: required, enum (draft, published, archived)
- `user_id`: required, exists in users table
- `tag_ids`: optional array of tag IDs

#### Get Single Post
```http
GET /api/v1/posts/{id}
```

#### Update Post
```http
PUT /api/v1/posts/{id}
Content-Type: application/json
```

**Request:**
```json
{
  "title": "Updated Title",
  "status": "archived",
  "tag_ids": [1, 3]
}
```

**Response:** 204 No Content

**Note:** Update endpoints return 204 No Content to avoid returning stale data. If you need the updated resource, issue a separate GET request to retrieve the current state from the database.

#### Delete Post
```http
DELETE /api/v1/posts/{id}
```

**Response:** 204 No Content

### Tags Endpoints

#### Get All Tags
```http
GET /api/v1/tags
```

#### Create Tag
```http
POST /api/v1/tags
Content-Type: application/json
```

**Request:**
```json
{
  "name": "JavaScript"
}
```

**Validation Rules:**
- `name`: required, unique, max:50

#### Get Single Tag
```http
GET /api/v1/tags/{id}
```

#### Update Tag
```http
PUT /api/v1/tags/{id}
Content-Type: application/json
```

**Response:** 204 No Content

**Note:** Update endpoints return 204 No Content to avoid returning stale data. If you need the updated resource, issue a separate GET request to retrieve the current state from the database.

#### Delete Tag
```http
DELETE /api/v1/tags/{id}
```

**Response:** 204 No Content

## Admin Panel

Access the Filament admin panel at `http://localhost:8000/admin`

Features:
- Visual management of posts and tags
- Multi-select tag attachment to posts
- Post status management with badges
- Search and filter capabilities

## Testing

The project includes comprehensive feature tests covering:
- CRUD operations for posts
- Input validation
- Relationship handling
- JSON response structure
```bash
# Run all tests
php artisan test

# Run tests with coverage
php artisan test --coverage
```

**Test Coverage:**
- ✅ Get all posts
- ✅ Create post with validation
- ✅ Get single post
- ✅ Update post
- ✅ Delete post
- ✅ Validation error handling

## Code Quality

The codebase follows Laravel coding standards enforced by Laravel Pint:
```bash
# Check code style
./vendor/bin/pint --test

# Fix code style issues
./vendor/bin/pint
```

## Project Structure
```
app/
├── Actions/                       # Business operations with side effects
│   ├── StorePostAction.php
│   └── UpdatePostAction.php
├── Enums/PostStatus.php           # Post status enum
├── Http/
│   ├── Controllers/Api/V1/        # API controllers
│   ├── Requests/                  # Form request validation
│   └── Resources/                 # API resource transformers
├── Models/                        # Eloquent models
├── Queries/                       # Spatie Query Builder configurations
│   ├── PostQuery.php
│   └── TagQuery.php
├── Repositories/                  # Data access layer
│   ├── PostRepository.php
│   └── TagRepository.php
└── Services/                      # External integrations (empty for now)

database/
├── factories/                     # Model factories for testing
├── migrations/                    # Database migrations
└── seeders/                       # Database seeders

tests/
└── Feature/                       # API feature tests
    └── PostApiTest.php
```

## Design Patterns
- **Repository Pattern**: Data access layer for CRUD operations
- **Action Pattern**: Business operations with side effects or multiple steps
- **Query Builder Pattern**: Spatie Query Builder for filtering, sorting, and includes
- **Form Request Validation**: Dedicated validation classes
- **API Resources**: Consistent JSON transformation with nested resource classes
- **Eager Loading**: Conditional relationship loading with `whenLoaded()` to prevent N+1 queries
- **Enum Types**: Type-safe status values
- **Factory Pattern**: Test data generation
- **Database Transactions**: RefreshDatabase in tests

## Development Notes

This is an **API-only** project with no public frontend. The Filament admin panel provides a visual interface for content management but is separate from the public API surface.

## License

This project was created as a technical assessment.
