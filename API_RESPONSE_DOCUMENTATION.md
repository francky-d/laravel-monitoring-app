# API Response Structure Documentation

This Laravel monitoring application uses a consistent API response structure across all endpoints. All API responses follow this unified format to ensure predictable client-side handling.

## Response Structure

### Success Response Structure
```json
{
    "status": "success",
    "message": "Descriptive success message",
    "data": { ... },
    "meta": { ... },    // Optional: For pagination or additional metadata
    "links": { ... }    // Optional: For pagination links
}
```

### Error Response Structure
```json
{
    "status": "error",
    "message": "Descriptive error message",
    "errors": { ... },  // Optional: Validation errors or detailed error info
    "data": { ... }     // Optional: Additional error context
}
```

## Status Codes and Their Usage

### Success Responses
- `200 OK` - Successful GET, PUT, PATCH requests
- `201 Created` - Successful POST requests that create resources
- `204 No Content` - Successful DELETE requests or operations with no response body

### Error Responses
- `400 Bad Request` - General client errors
- `401 Unauthorized` - Authentication required
- `403 Forbidden` - Insufficient permissions
- `404 Not Found` - Resource not found
- `422 Unprocessable Entity` - Validation errors
- `429 Too Many Requests` - Rate limiting
- `500 Internal Server Error` - Server errors

## Response Examples

### Authentication

#### Register User (Success)
```json
{
    "status": "success",
    "message": "User registered successfully",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "created_at": "2025-08-21T20:00:00.000000Z",
            "updated_at": "2025-08-21T20:00:00.000000Z"
        },
        "token": "1|abc123def456..."
    }
}
```

#### Login (Success)
```json
{
    "status": "success",
    "message": "Login successful",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com"
        },
        "token": "2|xyz789..."
    }
}
```

#### Login (Error - Invalid Credentials)
```json
{
    "status": "error",
    "message": "Validation failed",
    "errors": {
        "email": ["The provided credentials are incorrect."]
    }
}
```

### Applications

#### List Applications (Success with Pagination)
```json
{
    "status": "success",
    "message": "Applications retrieved successfully",
    "data": [
        {
            "id": "550e8400-e29b-41d4-a716-446655440001",
            "name": "My Website",
            "url": "https://example.com",
            "url_to_watch": null,
            "expected_http_code": 200,
            "monitor_url": "https://example.com",
            "user_id": 1,
            "application_group_id": null,
            "created_at": "2025-08-21T20:00:00.000000Z",
            "updated_at": "2025-08-21T20:00:00.000000Z",
            "incidents_count": 2,
            "active_incidents_count": 0
        }
    ],
    "meta": {
        "current_page": 1,
        "per_page": 15,
        "total": 1,
        "last_page": 1
    },
    "links": {
        "first": "http://api.example.com/applications?page=1",
        "last": "http://api.example.com/applications?page=1",
        "prev": null,
        "next": null
    }
}
```

#### Create Application (Success)
```json
{
    "status": "success",
    "message": "Application created successfully",
    "data": {
        "id": "550e8400-e29b-41d4-a716-446655440002",
        "name": "New App",
        "url": "https://newapp.com",
        "url_to_watch": "https://newapp.com/health",
        "expected_http_code": 200,
        "monitor_url": "https://newapp.com/health",
        "user_id": 1,
        "application_group_id": null,
        "created_at": "2025-08-21T20:05:00.000000Z",
        "updated_at": "2025-08-21T20:05:00.000000Z"
    }
}
```

#### Create Application (Validation Error)
```json
{
    "status": "error",
    "message": "Validation failed",
    "errors": {
        "name": ["The name field is required."],
        "url": ["The url field must be a valid URL."]
    }
}
```

### Incidents

#### Create Incident (Success)
```json
{
    "status": "success",
    "message": "Incident created successfully",
    "data": {
        "id": "550e8400-e29b-41d4-a716-446655440003",
        "title": "Website Down",
        "description": "The main website is not responding",
        "application_id": "550e8400-e29b-41d4-a716-446655440001",
        "user_id": 1,
        "status": "OPEN",
        "severity": "HIGH",
        "started_at": "2025-08-21T20:10:00.000000Z",
        "ended_at": null,
        "duration": null,
        "is_active": true,
        "is_closed": false,
        "created_at": "2025-08-21T20:10:00.000000Z",
        "updated_at": "2025-08-21T20:10:00.000000Z",
        "severity_color": "#ffc107",
        "status_transitions": ["IN_PROGRESS", "CLOSED"]
    }
}
```

### Error Handling

#### Unauthorized Access
```json
{
    "status": "error",
    "message": "Authentication required"
}
```

#### Forbidden Access
```json
{
    "status": "error",
    "message": "You do not have permission to perform this action"
}
```

#### Resource Not Found
```json
{
    "status": "error",
    "message": "The requested resource was not found"
}
```

#### Server Error (Production)
```json
{
    "status": "error",
    "message": "An unexpected error occurred"
}
```

#### Server Error (Development)
```json
{
    "status": "error",
    "message": "SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry..."
}
```

## Implementation Notes

### Response Classes
- `App\Http\Responses\ApiResponse` - Static methods for generating responses
- `App\Http\Traits\HasApiResponses` - Trait for controllers to use response methods

### Available Response Methods
- `successResponse($data, $message, $statusCode, $meta)` - General success
- `createdResponse($data, $message)` - Resource creation (201)
- `paginatedResponse($collection, $message)` - Paginated data
- `errorResponse($message, $statusCode, $errors, $data)` - General error
- `validationErrorResponse($errors, $message)` - Validation errors (422)
- `unauthorizedResponse($message)` - Authentication errors (401)
- `forbiddenResponse($message)` - Authorization errors (403)
- `notFoundResponse($message)` - Not found errors (404)
- `serverErrorResponse($message)` - Server errors (500)
- `noContentResponse($message)` - No content (204)

### Exception Handling
The application automatically converts Laravel exceptions to consistent API responses:
- `ValidationException` → 422 with validation errors
- `AuthenticationException` → 401 unauthorized
- `AuthorizationException` → 403 forbidden
- `NotFoundHttpException` → 404 not found
- `ModelNotFoundException` → 404 not found
- `QueryException` → 500 server error (with detailed message in development)

### Best Practices
1. Always use the response trait methods in controllers
2. Provide descriptive, user-friendly messages
3. Include relevant data in the response
4. Use appropriate HTTP status codes
5. Handle errors consistently across all endpoints
6. Include pagination metadata for list endpoints
7. Protect sensitive information in production error messages
