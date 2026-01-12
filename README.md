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

### Service Layer Pattern (Posts)
Posts use a dedicated service layer (`PostService`) because they contain business logic:
- Creating posts with tag attachments
- Updating posts and syncing tag relationships
- Eager loading author and tags to prevent N+1 queries

### Direct Model Access (Tags)
Tags use direct Eloquent operations in the controller because they only require simple CRUD with no business logic. This follows the KISS principle - avoid unnecessary abstraction.

### Form Request Validation
All input validation is handled through dedicated Form Request classes (`StorePostRequest`, `UpdatePostRequest`, etc.) to keep controllers clean and validation logic reusable.

### API Resources
JSON responses use API Resource classes to decouple internal data structures from API responses and ensure consistent output formatting.

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
├── Enums/PostStatus.php           # Post status enum
├── Http/
│   ├── Controllers/Api/V1/        # API controllers
│   ├── Requests/                  # Form request validation
│   └── Resources/                 # API resource transformers
├── Models/                        # Eloquent models
└── Services/PostService.php       # Business logic layer

database/
├── factories/                     # Model factories for testing
├── migrations/                    # Database migrations
└── seeders/                       # Database seeders

tests/
└── Feature/PostApiTest.php        # API feature tests
```

## Design Patterns
- **Repository Pattern**: Service layer for complex business logic
- **Form Request Validation**: Dedicated validation classes
- **API Resources**: Consistent JSON transformation
- **Eager Loading**: Prevents N+1 query problems
- **Enum Types**: Type-safe status values
- **Factory Pattern**: Test data generation
- **Database Transactions**: RefreshDatabase in tests

## Development Notes

This is an **API-only** project with no public frontend. The Filament admin panel provides a visual interface for content management but is separate from the public API surface.

## License

This project was created as a technical assessment.
