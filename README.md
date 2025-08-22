# 📊 Laravel Monitoring API

A professional application monitoring and incident management API built with Laravel 12. This powerful REST API provides comprehensive monitoring capabilities, intelligent incident tracking, and flexible notification systems for modern applications.

![Laravel Version](https://img.shields.io/badge/Laravel-12.25.0-red?style=flat-square&logo=laravel)
![PHP Version](https://img.shields.io/badge/PHP-8.4.1-blue?style=flat-square&logo=php)
![Pest Tests](https://img.shields.io/badge/Tests-Pest%204.0-green?style=flat-square)
![License](https://img.shields.io/badge/License-MIT-yellow?style=flat-square)

## ✨ Features

### 🏢 Application Management
- **Application Registration**: Create and manage multiple applications with metadata
- **Group Organization**: Organize applications into logical groups for better management
- **Real-time Status Monitoring**: Automated health checks and uptime tracking
- **Custom Configuration**: Flexible application settings and monitoring parameters

### 🚨 Advanced Incident Management
- **Incident Lifecycle**: Create, track, update, and resolve incidents with detailed logging
- **Severity Levels**: Multiple severity levels (low, medium, high, critical) with color coding
- **Status Transitions**: Controlled incident status changes with validation rules
- **Timeline Tracking**: Complete incident history with resolution tracking
- **Statistics & Analytics**: Comprehensive incident reporting and metrics

### 🔔 Intelligent Notification System
- **Multi-channel Support**: Email, Slack, Discord, Microsoft Teams notifications
- **Webhook Integration**: Robust webhook system with retry logic and validation
- **Subscription Management**: Flexible user subscription preferences per application
- **Test Notifications**: Built-in testing endpoints for all notification channels
- **Delivery Tracking**: Complete notification history and delivery status

### 🔐 Security & Authentication
- **JWT Authentication**: Secure token-based authentication using Laravel Sanctum
- **User Management**: Complete user registration, login, and profile management
- **Policy-based Authorization**: Fine-grained access control for all resources
- **API Rate Limiting**: Built-in protection against abuse with configurable limits
- **Webhook Security**: Signature validation for secure webhook delivery

## 🚀 Quick Start

### Prerequisites

- PHP 8.4.1 or higher
- Composer
- SQLite (default) or MySQL/PostgreSQL
- Node.js & NPM (for frontend assets)

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/francky-d/laravel-monitoring-app.git
   cd laravel-monitoring-app
   ```

2. **Install dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Database setup**
   ```bash
   php artisan migrate
   php artisan db:seed  # Optional: seed with sample data
   ```

5. **Build assets**
   ```bash
   npm run build
   ```

6. **Start the application**
   ```bash
   php artisan serve
   ```

The API will be available at `http://localhost:8000` and the beautiful welcome page at `http://localhost:8000/`.

## 📚 API Documentation

### Base URL
```
http://localhost:8000/api
```

### Authentication
All protected endpoints require a Bearer token in the Authorization header:
```bash
Authorization: Bearer YOUR_JWT_TOKEN
```

### Quick Examples

#### User Registration
```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com", 
    "password": "secure123"
  }'
```

#### Create Application
```bash
curl -X POST http://localhost:8000/api/applications \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "My Application",
    "url": "https://myapp.com",
    "description": "Production web application"
  }'
```

#### Check Application Status
```bash
curl http://localhost:8000/api/applications/1/status \
  -H "Authorization: Bearer YOUR_TOKEN"
```

#### Create Incident
```bash
curl -X POST http://localhost:8000/api/incidents \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "application_id": 1,
    "title": "API Response Time High",
    "description": "API endpoints responding slowly",
    "severity": "medium"
  }'
```

### Complete Documentation
Visit `http://localhost:8000/docs` for complete API documentation with interactive examples.

## 🏗️ Architecture

### Core Models
- **User**: Application users with authentication and preferences
- **Application**: Monitored applications with health status
- **ApplicationGroup**: Logical grouping of related applications
- **Incident**: Issue tracking with severity levels and status management
- **Subscription**: User notification preferences per application

### API Endpoints Overview

| Category | Endpoint | Description |
|----------|----------|-------------|
| **Authentication** | `POST /api/auth/register` | User registration |
| | `POST /api/auth/login` | User login |
| | `GET /api/auth/user` | Get authenticated user |
| **Applications** | `GET /api/applications` | List all applications |
| | `POST /api/applications` | Create new application |
| | `GET /api/applications/{id}/status` | Check application status |
| **Incidents** | `GET /api/incidents` | List incidents with filtering |
| | `POST /api/incidents` | Create new incident |
| | `PUT /api/incidents/{id}/resolve` | Resolve incident |
| **Notifications** | `GET /api/subscriptions` | Manage subscriptions |
| | `POST /api/subscriptions/{id}/test` | Test notifications |
| | `GET /api/user/notification-history` | View notification history |

## 🧪 Testing

This application uses **Pest 4.0** for comprehensive testing coverage.

### Run Tests
```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/Api/ApplicationTest.php

# Run tests with filter
php artisan test --filter="can create application"
```

### Test Categories
- **Feature Tests**: Full API endpoint testing with authentication
- **Unit Tests**: Individual component and method testing
- **Browser Tests**: End-to-end testing with Pest 4.0 browser capabilities

## 🛠️ Development

### Code Standards
This project follows Laravel best practices and uses:
- **Laravel Pint**: Code formatting (PSR-12)
- **Pest**: Testing framework
- **Laravel Sanctum**: API authentication
- **Eloquent ORM**: Database interactions

### Format Code
```bash
vendor/bin/pint
```

### Available Commands
```bash
# Create new model with factory and migration
php artisan make:model ModelName -mf

# Create new API controller
php artisan make:controller Api/ControllerName --api

# Create new test
php artisan make:test --pest TestName

# Create new job
php artisan make:job JobName
```

## 🔧 Configuration

### Environment Variables

```env
# Application
APP_NAME="Laravel Monitoring API"
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/database.sqlite

# Notifications
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password

# Queue (for background notifications)
QUEUE_CONNECTION=database
```

### Notification Channels
Configure webhook URLs in user notification settings:
- **Slack**: Slack webhook URL for team notifications
- **Discord**: Discord webhook URL for community alerts
- **Microsoft Teams**: Teams webhook URL for enterprise notifications

## 📈 Monitoring & Analytics

### Application Health Checks
- Automated status monitoring for registered applications
- Configurable check intervals and timeout settings
- Health score calculation based on uptime and response times

### Incident Analytics
- Incident frequency and resolution time metrics
- Severity distribution and trend analysis
- Application reliability scoring

### Notification Metrics
- Delivery success rates per channel
- Response time tracking for webhook notifications
- Failed delivery retry mechanisms

## 🤝 Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

### Development Guidelines
- Follow PSR-12 coding standards
- Write comprehensive tests for new features
- Update documentation for API changes
- Use descriptive commit messages

## 📄 License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## 🙏 Acknowledgments

- Built with [Laravel 12](https://laravel.com/) - The PHP Framework for Web Artisans
- Tested with [Pest 4.0](https://pestphp.com/) - The elegant PHP testing framework
- Formatted with [Laravel Pint](https://laravel.com/docs/pint) - Code style fixer for minimalists
- Authentication powered by [Laravel Sanctum](https://laravel.com/docs/sanctum) - Simple authentication for SPAs and APIs

---

<p align="center">
  Made with ❤️ for modern application monitoring
</p>
