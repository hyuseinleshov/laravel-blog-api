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
- **Publishing Limits**: Enforces monthly post limits based on subscription plan (Basic: 2, Medium: 10, Premium: Unlimited), resets each calendar month, with a 403 Forbidden error response for violations.
- **Post Boost**: Authors can pay €1 to boost their post to the top of listings via Stripe payment
- **Subscription-Based Ordering**: Public post listings prioritize boosted posts, then Premium, Medium, and Basic authors
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

# For Stripe integration (optional), add test keys to .env:
# STRIPE_KEY=pk_test_...
# STRIPE_SECRET=sk_test_...
# STRIPE_WEBHOOK_SECRET=whsec_...
# STRIPE_BOOST_PRICE=100  (optional, defaults to 100 cents / €1)

# Then use Stripe CLI for local webhook testing:
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
- `GET /api/v1/posts` - List all posts (public, ordered by: boosted → Premium → Medium → Basic → date)
- `GET /api/v1/posts/{id}` - Get single post (public)
- `POST /api/v1/posts` - Create post (authenticated, auto-assigned to author, subject to plan limits)
- `PUT /api/v1/posts/{id}` - Update post (authenticated, author only, publishing drafts subject to limits)
- `DELETE /api/v1/posts/{id}` - Delete post (authenticated, author only)
- `POST /api/v1/posts/{post}/boost` - Boost post to top of listings (authenticated, author only, €1 Stripe payment)

**Publishing Limits:**
Posts are subject to monthly publishing limits based on subscription plans (resets each calendar month):
- Creating published posts checks the limit
- Publishing a draft (status change from `draft` to `published`) checks the limit
- Editing already published posts does NOT recheck the limit
- Drafts are unlimited and do not count toward limits

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

## Postman Collection

A comprehensive Postman collection is included for interactive API testing and demonstration of all features. The collection covers the complete user journey from registration to article boosting.

### Import Collection

1. Import `hyusein-blog-api.postman_collection.json` into Postman
2. Set the `base_url` collection variable (default: `http://localhost:8000/api/v1`)
3. Execute requests sequentially or explore individual endpoints

### Collection Structure

The collection is organized into four main folders:

**1. Auth**
- Register Author - Create new account (auto-assigns Basic subscription)
- Login - Authenticate and receive token (captured automatically)
- Get Current User - Verify authentication and get profile
- Logout - Revoke token and end session

**2. Subscriptions**
- Checkout Basic Plan - Free plan, activates immediately
- Checkout Medium Plan - €2/month, returns `client_secret` for payment
- Checkout Premium Plan - €10/month, returns `client_secret` for payment
- Get Active Subscription - View current subscription details

**3. Articles**
- Create Article (Draft) - Unlimited, does not count toward limits
- Create Article (Published - First) - Should succeed (1/2 for Basic)
- Create Article (Published - Second) - Should succeed (2/2 for Basic)
- Create Article (Published - Limit Exceeded) - Expected 403 error
- List Articles (Public) - View all articles with plan-based ordering
- Get Single Article - Retrieve specific article details
- Update Article - Modify content or status (author only)
- Delete Article - Remove article permanently (author only)

**4. Boost**
- Boost Article - Pay €1 to promote article to top position
- List Articles (Verify Boost Order) - Confirm boosted article appears first

### Automated Features

The collection includes several automation features for seamless testing:

**Variable Capture:**
- `auth_token` - Automatically captured from login response
- `author_id` - Captured from registration/login
- `article_id` - Captured from article creation
- `subscription_id` - Captured from subscription checkout
- `client_secret` - Captured from paid checkouts and boost

**Pre-request Scripts:**
- Validate authentication token presence before protected requests
- Provide helpful error messages if dependencies are missing

**Test Scripts:**
- Capture response data into variables for downstream requests
- Log important information to console for debugging
- Provide next-step guidance for complex flows

### Testing Workflows

**Complete Flow (Happy Path):**
1. Register Author → Login (captures token)
2. Checkout Basic Plan (free, activates immediately)
3. Create Article (Draft) → succeeds (unlimited drafts)
4. Create Published Article (First) → succeeds (1/2 limit)
5. Create Published Article (Second) → succeeds (2/2 limit reached)
6. List Articles → verify articles appear in correct order
7. Boost Article → receive `client_secret` for payment
8. List Articles (Verify Boost) → confirm boosted article at top

**Testing Publishing Limits:**
1. Login with Basic plan author
2. Create Published Article (First) → 201 Created
3. Create Published Article (Second) → 201 Created
4. Create Published Article (Third) → 403 Forbidden (limit exceeded)
5. Observe detailed error message with plan and limit information

**Testing Subscription Upgrades:**
1. Login → Get Active Subscription (shows Basic)
2. Checkout Medium Plan → receive `client_secret`
3. (In production: confirm payment via Stripe.js)
4. (Webhook activates subscription)
5. Get Active Subscription → should show Medium plan
6. Can now publish up to 10 articles/month

**Testing Article Boost:**
1. Create and publish an article
2. Boost Article → receive `client_secret`
3. (In production: confirm payment via Stripe.js)
4. (Webhook marks article as boosted)
5. List Articles → boosted article appears first, above Premium articles

### Stripe Testing

For paid subscriptions and boost payments, use Stripe test card:

```
Card Number: 4242 4242 4242 4242
Expiry: Any future date (e.g., 12/34)
CVC: Any 3 digits (e.g., 123)
ZIP: Any 5 digits (e.g., 12345)
```

**Note:** The Postman collection demonstrates the API checkout flow (receiving `client_secret`). In production, the frontend would use Stripe.js to confirm payments. For local testing, use Stripe CLI to simulate webhooks:

```bash
stripe listen --forward-to http://localhost:8000/api/v1/webhooks/stripe
```

### Collection Variables

The collection uses the following variables:

| Variable | Description | Auto-Captured |
|----------|-------------|---------------|
| `base_url` | API base URL | No (set manually) |
| `auth_token` | Bearer token for authentication | Yes (from login) |
| `author_id` | Current author ID | Yes (from register/login) |
| `article_id` | Last created article ID | Yes (from article creation) |
| `subscription_id` | Last created subscription ID | Yes (from checkout) |
| `client_secret` | Stripe PaymentIntent secret | Yes (from paid checkouts) |
| `boost_client_secret` | Stripe PaymentIntent for boost | Yes (from boost) |

### Expected Outcomes

**Successful Requests:**
- Registration: 201 Created with author profile
- Login: 200 OK with token and author data
- Article creation (within limit): 201 Created with article data
- Article update/delete: 204 No Content
- Subscription checkout (Basic): 201 with active status
- Subscription checkout (Paid): 201 with pending status and `client_secret`

**Expected Errors (Demonstrating Limits):**
- Publishing limit exceeded: 403 Forbidden with detailed error message
- Already boosted article: 409 Conflict
- Unauthorized access: 403 Forbidden
- Invalid credentials: 401 Unauthorized

### Tips for Using Collection

1. **Execute in Order:** Follow folder order (Auth → Subscriptions → Articles → Boost) for first run
2. **Check Console:** View captured variables and next steps in Postman console
3. **Re-run Flows:** After initial setup, you can jump to any endpoint
4. **Fresh Start:** Clear collection variables to reset state between test runs
5. **Multiple Authors:** Change email in registration to test with multiple accounts
6. **Plan Testing:** Use different subscription plans to test varying article limits

For detailed request/response documentation, see the descriptions within each Postman request.

### Error Responses

When an author attempts to publish a post and exceeds their monthly publishing limit based on their subscription plan, the API will return a `403 Forbidden` status with a structured JSON error response.

**Error Code:** `publishing_limit_exceeded`

**Example JSON Response:**
```json
{
    "message": "Monthly publishing limit reached. Your Basic plan allows 2 posts per month. You have published 2 posts this month. Upgrade to publish more.",
    "error": {
        "code": "publishing_limit_exceeded",
        "details": {
            "plan": "basic",
            "limit": 2,
            "current_count": 2
        }
    }
}
```

**Client Behavior:** Clients should parse this error response to inform the user about the exceeded limit, their current plan, and suggest upgrading their subscription to publish more posts.

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

# Run publishing limits tests
php artisan test --filter PublishingLimits
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
```

In production, configure your scheduler to run this command daily to mark expired subscriptions.

## Notes

- This is an **API-only** project (no frontend). Filament admin panel is for internal use only.
- Stripe integration uses sandbox mode (test keys). Payments are single charges, not recurring subscriptions.

## License

This project was created as a technical assessment.
