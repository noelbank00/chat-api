# Chat API

## Overview

Chat API is a Laravel 12-based REST API that provides backend services for a real-time messaging system. The system supports user registration, email verification, friend management, and secure messaging.

## Technical Requirements

- PHP 8.4+
- Composer
- SQLite/MySQL/PostgreSQL
- Laravel 12

## Installation

### 1. Clone Repository

```bash
git clone <repository-url>
cd chat-api
```

### 2. Install Dependencies

```bash
composer install
```

### 3. Environment Configuration

```bash
cp .env.example .env
php artisan key:generate
```

### 4. Database Configuration

Edit the `.env` file with database settings:

```env
DB_CONNECTION=sqlite
DB_DATABASE=/path/to/database.sqlite
```

Or for MySQL/PostgreSQL:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=chat_api
DB_USERNAME=root
DB_PASSWORD=
```

### 5. Email Configuration

Configure email settings in `.env` file for email verification:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="${APP_NAME}"
```

### 6. Run Migrations

```bash
php artisan migrate
```

## Testing

### Running Tests

```bash
# All tests
php artisan test
```

### Test Coverage

- **Unit tests**: Service class business logic
- **Feature tests**: API endpoints and validations
- **Authentication tests**: Sanctum token handling
- **Validation tests**: Request validations

## API Testing

A Postman collection is available in the project root as `postman_collection.json`. Import this collection into Postman to test all API endpoints.

## Development Environment
