# Smart Campus API Testing Guide

This guide provides comprehensive testing instructions for all API endpoints in the Smart Campus backend system.

## Prerequisites

1. **Server Running**: Ensure the Symfony server is running
   ```bash
   symfony server:start
   # or
   php -S localhost:8000 -t public/
   ```

2. **Database Setup**: Make sure the database is created and migrations are run
   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate
   ```

3. **JWT Keys**: Ensure JWT keys are generated and configured
   ```bash
   mkdir -p config/jwt
   openssl genrsa -out config/jwt/private.pem -aes256 -passout pass:moro 4096
   openssl rsa -pubout -in config/jwt/private.pem -out config/jwt/public.pem -passin pass:moro
   ```

## Testing Tools

You can use any of these tools to test the API:
- **Postman**: Import the provided collection
- **cURL**: Use the command-line examples below
- **Insomnia**: REST client
- **Browser**: For GET endpoints only

## Base URL

```
Local Development: http://localhost:8000
```

## Authentication Flow

### 1. Register a New User (Admin)

```bash
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@smartcampus.com",
    "password": "admin123",
    "type": "admin",
    "firstname": "Admin",
    "lastname": "User"
  }'
```

### 2. Login to Get JWT Token

```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@smartcampus.com",
    "password": "admin123"
  }'
```

**Expected Response:**
```json
{
  "message": "Login successful",
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
  "user": {
    "id": 1,
    "email": "admin@smartcampus.com",
    "firstname": "Admin",
    "lastname": "User",
    "roles": ["ROLE_ADMIN", "ROLE_USER"]
  }
}
```

**Save the token for subsequent requests!**

## API Endpoints Testing

### System Endpoints

#### Health Check
```bash
curl http://localhost:8000/api/health
```

#### API Information
```bash
curl http://localhost:8000/api/info
```

#### Swagger Documentation
```bash
# Open in browser
http://localhost:8000/api/doc
```

### Class Management

#### Create a Class
```bash
curl -X POST http://localhost:8000/api/classes \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "nom": "Class A"
  }'
```

#### List All Classes
```bash
curl -X GET http://localhost:8000/api/classes \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

#### Get Class Details
```bash
curl -X GET http://localhost:8000/api/classes/1 \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

#### Update Class
```bash
curl -X PUT http://localhost:8000/api/classes/1 \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "nom": "Updated Class A"
  }'
```

#### Get Class Statistics
```bash
curl -X GET http://localhost:8000/api/classes/1/stats \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

### Student Management

#### Create a Student
```bash
curl -X POST http://localhost:8000/api/students \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "student@example.com",
    "firstname": "John",
    "lastname": "Doe",
    "numStudent": "STU001",
    "dateNaissance": "2005-01-15",
    "classeId": 1,
    "password": "student123"
  }'
```

#### List All Students
```bash
curl -X GET http://localhost:8000/api/students \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

#### List Students with Pagination
```bash
curl -X GET "http://localhost:8000/api/students?page=1&limit=10" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

#### Search Students
```bash
curl -X GET "http://localhost:8000/api/students?search=John" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

#### Filter Students by Class
```bash
curl -X GET "http://localhost:8000/api/students?classe=1" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

#### Get Student Details
```bash
curl -X GET http://localhost:8000/api/students/1 \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

#### Update Student
```bash
curl -X PUT http://localhost:8000/api/students/1 \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "firstname": "Jane",
    "lastname": "Smith",
    "classeId": 2
  }'
```

#### Get Students by Class
```bash
curl -X GET http://localhost:8000/api/students/by-class/1 \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

### Dashboard Endpoints

#### Get Dashboard Statistics
```bash
curl -X GET http://localhost:8000/api/dashboard/stats \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

#### Get Recent Activity
```bash
curl -X GET http://localhost:8000/api/dashboard/recent-activity \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

#### Get Notifications
```bash
curl -X GET http://localhost:8000/api/dashboard/notifications \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

#### Get Quick Statistics
```bash
curl -X GET http://localhost:8000/api/dashboard/quick-stats \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

## Testing Scenarios

### Scenario 1: Complete User Flow

1. **Register an admin user**
2. **Login to get JWT token**
3. **Create a class**
4. **Create a student and assign to class**
5. **View dashboard statistics**
6. **List students in the class**

### Scenario 2: Error Handling

#### Test Invalid Data
```bash
# Try to create student with invalid email
curl -X POST http://localhost:8000/api/students \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "invalid-email",
    "firstname": "",
    "lastname": "Doe"
  }'
```

#### Test Unauthorized Access
```bash
# Try to access protected endpoint without token
curl -X GET http://localhost:8000/api/students
```

#### Test Non-existent Resources
```bash
curl -X GET http://localhost:8000/api/students/999 \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

### Scenario 3: Pagination and Filtering

```bash
# Test pagination
curl -X GET "http://localhost:8000/api/students?page=1&limit=5" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"

# Test search
curl -X GET "http://localhost:8000/api/students?search=John" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"

# Test class filtering
curl -X GET "http://localhost:8000/api/students?classe=1" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

## Expected HTTP Status Codes

- **200**: Success (GET, PUT)
- **201**: Created (POST)
- **204**: No Content (DELETE)
- **400**: Bad Request (validation errors)
- **401**: Unauthorized (missing/invalid token)
- **403**: Forbidden (insufficient permissions)
- **404**: Not Found (resource doesn't exist)
- **409**: Conflict (duplicate data)
- **500**: Internal Server Error

## Response Format

### Success Response
```json
{
  "data": [...],
  "total": 100,
  "page": 1,
  "limit": 20,
  "pages": 5
}
```

### Error Response
```json
{
  "error": "Error message",
  "code": "ERROR_CODE",
  "field": "field_name"
}
```

## Postman Collection

Create a Postman collection with the following structure:

1. **Environment Variables**:
   - `baseUrl`: `http://localhost:8000`
   - `token`: `{{token}}`

2. **Pre-request Script for Authentication**:
   ```javascript
   // Add this to requests that need authentication
   pm.request.headers.add({
     key: 'Authorization',
     value: 'Bearer ' + pm.environment.get('token')
   });
   ```

3. **Test Scripts**:
   ```javascript
   // Save token after login
   if (pm.response.json().token) {
     pm.environment.set('token', pm.response.json().token);
   }
   
   // Test status code
   pm.test("Status code is 200", function () {
     pm.response.to.have.status(200);
   });
   ```

## Troubleshooting

### Common Issues

1. **JWT Token Expired**: Login again to get a new token
2. **Database Connection**: Check MySQL/MariaDB is running
3. **CORS Errors**: Ensure CORS is properly configured
4. **Validation Errors**: Check request payload format
5. **Permission Denied**: Ensure user has proper role

### Debug Commands

```bash
# Check logs
tail -f var/log/dev.log

# Clear cache
php bin/console cache:clear

# Check routes
php bin/console debug:router

# Check security
php bin/console debug:security
```

## Performance Testing

### Load Testing with Apache Bench

```bash
# Test login endpoint
ab -n 100 -c 10 -p login.json -T application/json http://localhost:8000/api/login

# Test authenticated endpoint (with token)
ab -n 100 -c 10 -H "Authorization: Bearer YOUR_TOKEN" http://localhost:8000/api/students
```

## Security Testing

1. **Test SQL Injection**: Try malicious input in search parameters
2. **Test XSS**: Try script injection in text fields
3. **Test Authorization**: Try accessing endpoints with wrong roles
4. **Test Rate Limiting**: Make rapid requests to check limits

---

**Note**: Replace `YOUR_JWT_TOKEN` with the actual JWT token received from the login endpoint in all authenticated requests.