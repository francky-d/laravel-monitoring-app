# Laravel Monitoring Application Development Prompt

## Project Overview

Create a comprehensive web application monitoring API using **Laravel 12**, **PHP 8.4**, **Laravel Sanctum** for authentication, and **Pest 4** for testing. This system should replicate the functionality described in the Technical Specification document, allowing users to monitor web applications, track incidents, and manage subscriptions.

## Technical Requirements

### Core Technologies
- **Framework**: Laravel 12
- **PHP Version**: PHP 8.4
- **Authentication**: Laravel Sanctum (API tokens)
- **Database**: MySQL/PostgreSQL (production), SQLite (testing)
- **Testing Framework**: Pest 4
- **API Format**: RESTful JSON API
- **Validation**: Laravel Form Requests
- **Queue System**: Laravel Queues (for monitoring jobs)

### Development Standards
- Follow PSR-12 coding standards
- Use PHP 8.4 features (typed properties, readonly classes, etc.)
- Implement proper error handling and API responses
- Use Laravel's built-in features (Eloquent, Validation, Resources, etc.)
- Follow Laravel naming conventions

## Database Schema & Models

### 1. User Model
Create a `User` model extending Laravel's default User model:

**Key Fields:**
- id: Primary key (unsigned big integer)
- name: string(255), required
- email: string(255), required, unique
- email_verified_at: timestamp, nullable
- password: string(255), required, hashed
- remember_token: string(100), nullable
- notification_email: string(255), nullable (for alerts)
- slack_webhook_url: string(500), nullable
- teams_webhook_url: string(500), nullable
- discord_webhook_url: string(500), nullable
- created_at: timestamp
- updated_at: timestamp

**Relationships:**
- hasMany(Application::class)
- hasMany(ApplicationGroup::class)
- hasMany(Incident::class)
- hasMany(Subscription::class)

### 2. Application Model
Create an `Application` model:

**Key Fields:**
- id: UUID primary key
- name: string(255), required
- url: string(255), required
- url_to_watch: string(255), nullable
- expected_http_code: integer, nullable, default 200
- user_id: foreign key to users table
- application_group_id: foreign key to application_groups table, nullable
- created_at: timestamp
- updated_at: timestamp

**Relationships:**
- belongsTo(User::class)
- belongsTo(ApplicationGroup::class, 'application_group_id')
- hasMany(Incident::class)
- hasMany(Subscription::class)

**Business Rules:**
- Use UUID trait for primary keys
- Validate URL format
- Expected HTTP codes: 100-599 range
- Cascade delete incidents and subscriptions when deleted

### 3. ApplicationGroup Model
Create an `ApplicationGroup` model for grouping applications:

**Key Fields:**
- id: UUID primary key
- name: string(255), required
- description: text, nullable
- user_id: foreign key to users table (group owner)
- created_at: timestamp
- updated_at: timestamp

**Relationships:**
- belongsTo(User::class)
- hasMany(Application::class)
- hasMany(Subscription::class)

**Business Rules:**
- Use UUID trait for primary keys
- Group names must be unique per user
- Cascade delete applications and subscriptions when deleted
- Users can subscribe to entire groups for notifications

### 4. Incident Model
Create an `Incident` model with status state machine:

**Key Fields:**
- id: UUID primary key
- title: string(255), required
- description: text, required
- application_id: foreign key to applications table
- user_id: foreign key to users table (reporter)
- status: enum['OPEN', 'IN_PROGRESS', 'RESOLVED', 'CLOSED'], default 'OPEN'
- severity: enum['LOW', 'HIGH', 'CRITICAL'], default 'LOW'
- started_at: timestamp, required
- ended_at: timestamp, nullable
- created_at: timestamp
- updated_at: timestamp

**Relationships:**
- belongsTo(Application::class)
- belongsTo(User::class, 'user_id')

**Business Rules:**
- Status transitions: OPEN → IN_PROGRESS → RESOLVED → CLOSED
- ended_at must be null for OPEN/IN_PROGRESS status
- ended_at automatically set when status becomes RESOLVED/CLOSED
- ended_at cannot be earlier than started_at

### 5. Subscription Model
Create a `Subscription` model for user subscriptions to applications or application groups:

**Key Fields:**
- id: UUID primary key
- user_id: foreign key to users table
- subscribable_type: string (Application or ApplicationGroup)
- subscribable_id: UUID foreign key (polymorphic relationship)
- notification_channels: JSON array of enabled channels ['email', 'slack', 'teams', 'discord']
- created_at: timestamp
- updated_at: timestamp

**Relationships:**
- belongsTo(User::class)
- morphTo('subscribable') // Can be Application or ApplicationGroup

**Business Rules:**
- Unique constraint on user_id + subscribable_type + subscribable_id combination
- Cascade delete when user, application, or group is deleted
- notification_channels defaults to ['email'] if not specified
- Users automatically subscribe to their own applications and groups

## API Endpoints Specification

### Authentication Endpoints
- POST /api/auth/register - User registration
- POST /api/auth/login - User login (returns Sanctum token)
- POST /api/auth/logout - User logout (revoke current token)
- GET /api/auth/user - Get authenticated user details

### Application Management
- GET /api/applications - List user's applications (with nested incidents)
- POST /api/applications - Create new application
- GET /api/applications/{uuid} - Show specific application details
- PUT /api/applications/{uuid} - Update application (owner only)
- DELETE /api/applications/{uuid} - Delete application (owner only)

### Application Group Management
- GET /api/application-groups - List user's application groups
- POST /api/application-groups - Create new application group
- GET /api/application-groups/{uuid} - Show specific group with applications
- PUT /api/application-groups/{uuid} - Update group (owner only)
- DELETE /api/application-groups/{uuid} - Delete group (owner only)
- POST /api/application-groups/{uuid}/applications - Add application to group
- DELETE /api/application-groups/{uuid}/applications/{app_uuid} - Remove application from group

### Incident Management
- GET /api/incidents - List incidents for user's applications and subscriptions
- POST /api/incidents - Create new incident
- GET /api/incidents/{uuid} - Show specific incident
- PUT /api/incidents/{uuid} - Update incident status/details
- DELETE /api/incidents/{uuid} - Delete incident (soft delete recommended)

### Subscription Management
- GET /api/subscriptions - List user's subscriptions (applications and groups)
- POST /api/subscriptions - Subscribe to an application or application group
- PUT /api/subscriptions/{uuid} - Update notification channels for subscription
- DELETE /api/subscriptions/{uuid} - Unsubscribe from application or group
- GET /api/applications/{uuid}/subscribers - List application subscribers (owner only)
- GET /api/application-groups/{uuid}/subscribers - List group subscribers (owner only)

### Notification Settings
- GET /api/user/notification-settings - Get user's notification webhook URLs
- PUT /api/user/notification-settings - Update notification webhook URLs
- POST /api/user/test-notification/{channel} - Test notification channel (email/slack/teams/discord)

### Monitoring Endpoints
- POST /api/applications/{uuid}/check - Manual health check trigger
- GET /api/applications/{uuid}/status - Get current monitoring status

## Laravel Implementation Details

### 1. Authentication Setup
- Install and configure Laravel Sanctum
- Configure API middleware in config/sanctum.php
- Set up proper CORS configuration

### 2. Model Implementations

#### UUID Trait Implementation
- Create HasUuid trait for UUID primary keys
- Implement automatic UUID generation on model creation
- Set proper key type and incrementing properties

#### Status Enum Implementation
- Create IncidentStatus enum with transition logic
- Implement status transition validation
- Define allowed status transitions (OPEN → IN_PROGRESS → RESOLVED → CLOSED)

### 3. Form Request Validation
- Create Form Request classes for all API endpoints
- Implement validation rules for applications (name, url, http codes)
- Implement validation rules for incidents (title, description, status, severity)
- Implement validation rules for subscriptions and notification settings
- Add custom validation for status transitions

### 4. API Resources
- Create API Resource classes for consistent JSON responses
- Implement ApplicationResource with nested incidents
- Implement ApplicationGroupResource with nested applications
- Implement IncidentResource with application details
- Implement SubscriptionResource with subscribable details
- Use conditional loading for nested relationships

### 5. Controller Structure
- Implement API controllers with proper authorization using policies
- Use Form Requests for validation
- Implement pagination for list endpoints
- Use eager loading to prevent N+1 queries
- Return consistent API responses using Resources
- Handle errors gracefully with proper HTTP status codes

### 6. Policies and Authorization
- Create authorization policies for all models
- Implement owner-based authorization for applications and groups
- Allow users to view incidents for subscribed applications
- Restrict sensitive operations to owners only
- Use Gates for complex authorization logic

## Pest 4 Testing Requirements

### 1. Test Structure

**Feature Tests:**
- tests/Feature/Api/ApplicationTest.php
- tests/Feature/Api/ApplicationGroupTest.php
- tests/Feature/Api/IncidentTest.php
- tests/Feature/Api/SubscriptionTest.php
- tests/Feature/Api/NotificationTest.php
- tests/Feature/Api/AuthTest.php

**Unit Tests:**
- tests/Unit/Models/ApplicationTest.php
- tests/Unit/Models/ApplicationGroupTest.php
- tests/Unit/Models/IncidentTest.php
- tests/Unit/Models/SubscriptionTest.php
- tests/Unit/Enums/IncidentStatusTest.php
- tests/Unit/Jobs/NotifySubscribersJobTest.php

### 2. Test Coverage Requirements

#### Authentication Tests
- Test user registration with valid and invalid data
- Test user login and token generation
- Test token-based authentication for protected endpoints
- Test logout functionality

#### Application API Tests
- Test CRUD operations for applications
- Test authorization (users can only modify their own applications)
- Test application validation rules
- Test application grouping functionality

#### Application Group Tests
- Test creating, updating, and deleting application groups
- Test adding and removing applications from groups
- Test group ownership and authorization
- Test group-based notifications

#### Incident Management Tests
- Test incident creation and updates
- Test status transition validation
- Test automatic ended_at timestamp setting
- Test incident visibility based on subscriptions

#### Subscription Tests
- Test subscribing to applications and groups
- Test notification channel configuration
- Test automatic owner subscriptions
- Test polymorphic subscription relationships

#### Notification Tests
- Test email notifications
- Test webhook notifications (Slack, Teams, Discord)
- Test notification settings updates
- Test notification channel testing endpoints

### 3. Test Database Setup
- Configure Pest with TestCase and RefreshDatabase trait
- Create model factories for all entities (User, Application, ApplicationGroup, Incident, Subscription)
- Set up test database configuration using SQLite
- Implement database seeding for test scenarios
- Configure proper test environment variables

## Background Jobs and Monitoring

### 1. Monitoring Job Implementation
- Create MonitorApplicationJob for periodic application health checks
- Implement HTTP request monitoring with configurable timeout
- Create incidents automatically when applications fail health checks
- Dispatch notification jobs when incidents are created
- Handle different HTTP status codes and map to severity levels

### 2. Notification Job Implementation
- Create NotifySubscribersJob for sending alerts to subscribers
- Support multiple notification channels: email, Slack, Microsoft Teams, Discord
- Merge direct application subscribers and group subscribers
- Deduplicate subscribers to avoid duplicate notifications
- Implement individual notification methods for each channel:
  - **Email:** Use Laravel Mail with customizable notification email
  - **Slack:** Send formatted messages using webhook URLs
  - **Teams:** Send MessageCard format notifications
  - **Discord:** Send embedded messages with severity-based colors

### 3. Scheduled Monitoring
- Configure scheduled monitoring in Laravel's task scheduler
- Process applications in chunks to handle large datasets
- Run monitoring checks every 5 minutes
- Use queue system for scalable job processing

## Business Logic Implementation

### 1. Model Events and Observers
- **ApplicationObserver:** Auto-subscribe application owners when applications are created
- **ApplicationGroupObserver:** Auto-subscribe group owners when groups are created
- **IncidentObserver:** Validate status transitions and automatically set ended_at timestamps
- Implement proper event handling for model lifecycle events
- Ensure data consistency through observer patterns

### 2. Custom Validation Rules
- Create ValidStatusTransition rule for incident status changes
- Implement custom validation for webhook URL formats
- Add validation for HTTP status code ranges (100-599)
- Create validation rules for notification channel arrays
- Implement unique constraint validation for subscriptions

## Additional Requirements

### 1. CORS Configuration
- Configure CORS for API access to allow frontend applications
- Set appropriate allowed origins, methods, and headers
- Enable credentials support for authentication

### 2. Rate Limiting
- Implement API rate limiting to prevent abuse
- Configure different limits for authenticated vs anonymous users
- Add throttling middleware to API routes

### 3. Error Handling
- Implement consistent API error responses
- Handle validation errors with proper HTTP status codes
- Create custom exception classes for business logic errors
- Return structured error responses with meaningful messages

## Deliverables

1. **Complete Laravel 12 API application** with all models, controllers, and endpoints
2. **Comprehensive Pest test suite** with 90%+ code coverage
3. **Database migrations and seeders** for development data
4. **API documentation** (using Laravel's built-in tools or Swagger)
5. **Docker configuration** for development environment
6. **Postman/Insomnia collection** for API testing
7. **Monitoring job implementation** with queue processing
8. **Proper error handling and validation** throughout the application

## Success Criteria

- All API endpoints functional and properly authenticated with Sanctum
- Business rules from Technical Specification properly implemented
- Status state machine working correctly for incidents
- Comprehensive test coverage with Pest 4
- Proper validation and error handling
- Background monitoring jobs processing correctly
- Clean, maintainable code following Laravel best practices
- Documentation complete and accurate

This Laravel implementation should replicate all functionality described in the Technical Specification while leveraging Laravel's conventions and best practices for PHP 8.4 development.