## Overview

This project demonstrates Laravel development practices including clean architecture, comprehensive testing, and meaningful application of design patterns. The API follows REST conventions and includes a Filament admin panel for easy resource management.

## Features

- **Author Authentication**: Sanctum-based token authentication with registration, login, and logout
- **Subscription Plans**: Three-tier subscription system (Basic, Medium, Premium) with monthly limits
- **Stripe Payment Integration**: Secure payment processing using Stripe sandbox for paid subscriptions
- **Webhook Processing**: Automated subscription activation via Stripe payment_intent.succeeded webhooks
- **Authorization Policies**: Role-based access control for posts and tags
- **Posts Management**: Full CRUD operations with status workflow (draft, published, archived)
- **Tags System**: Many-to-many relationship with posts
- **User Attribution**: Posts are associated with authors
- **Transaction History**: Complete payment tracking and audit trail
- **Rate Limiting**: Throttled authentication endpoints (5 requests per minute)
- **Admin Panel**: Filament-powered UI for managing posts, tags, subscriptions, and transactions
- **Comprehensive Testing**: Feature tests with Pest framework covering all subscription flows
- **API Versioning**: Structured v1 API routes

## Tech Stack

- **Framework**: Laravel 12
- **Database**: SQLite (development)
- **Authentication**: Laravel Sanctum
- **Payment Processing**: Stripe API (sandbox mode)
- **Testing**: Pest
- **Admin Panel**: Filament
- **Code Quality**: Laravel Pint

## Architecture

**Clean Architecture Layers:**
- **Controllers**: HTTP layer, delegates to Actions
- **Actions**: Business operations with multiple steps (e.g., `CheckoutAction`, `ProcessStripeWebhookAction`)
- **Services**: External API integrations (e.g., `StripeService` for Stripe)
- **Repositories**: Data access layer
- **Form Requests**: Input validation
- **API Resources**: JSON response transformation

**Query Builder Pattern:**
Listing endpoints support filtering, sorting, and relationship loading via Spatie Query Builder.
```http
GET /api/v1/posts?filter[status]=published&include=author,tags&sort=-created_at
```

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

# For Stripe integration (optional), add test keys to .env and use Stripe CLI:
# stripe listen --forward-to http://localhost:8000/api/v1/webhooks/stripe
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

### Subscription Endpoints

**Subscription Plans:**
- **Basic**: Free plan with 2 posts per month limit
- **Medium**: €2/month with 10 posts per month limit
- **Premium**: €10/month with unlimited posts

**Subscription Management:**
- `POST /api/v1/subscriptions/checkout` - Checkout a subscription plan (authenticated)
  - Request: `{"plan": "basic"|"medium"|"premium"}`
  - Response (Basic): `{"subscription_id": 1, "plan": "basic", "status": "active"}`
  - Response (Paid): `{"subscription_id": 1, "plan": "medium", "status": "pending", "client_secret": "pi_..."}`
- `GET /api/v1/subscriptions/current` - Get authenticated author's active subscription
- `POST /api/v1/webhooks/stripe` - Stripe webhook endpoint (public, signature-verified)

**Payment Flow:**
Basic plan is free and activated immediately. Paid plans (Medium/Premium) return a Stripe `client_secret` for frontend payment, then activate automatically via webhook.

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
- **Posts & Tags Management**: Visual CRUD operations with multi-select relationships
- **Subscriptions**: View all author subscriptions with plan, status, and validity dates
  - Manual override capability for admin corrections
  - Filter by plan and status
  - Payment history via relation manager
- **Transactions**: Complete payment audit trail
  - View Stripe payment IDs, amounts, currencies
  - Filter by author, plan, and status
  - Linked to subscriptions for tracking
- Post status management with badges
- Search and filter capabilities across all resources

## Testing

The project includes comprehensive feature tests covering authentication, authorization, CRUD operations, subscriptions, payment processing, and validation.

```bash
# Run all tests
php artisan test

# Run tests with coverage
php artisan test --coverage

# Run subscription-related tests
php artisan test --filter Subscription

# Run webhook tests
php artisan test --filter Webhook
```


## Code Quality

The codebase follows Laravel coding standards enforced by Laravel Pint:
```bash
# Check code style
./vendor/bin/pint --test

# Fix code style issues
./vendor/bin/pint
```


## Scheduled Tasks

The application includes a command to mark expired subscriptions:

```bash
# Manually run the expiry check
php artisan subscriptions:expire

# Add to cron (production)
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

In production, configure your scheduler to run this command daily to mark expired subscriptions.

## Notes

- This is an **API-only** project (no frontend). Filament admin panel is for internal use only.
- Stripe integration uses sandbox mode (test keys). Payments are single charges, not recurring subscriptions.

## License

This project was created as a technical assessment.
