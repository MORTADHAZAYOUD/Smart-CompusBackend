# Smart Campus Backend API

A comprehensive school management system backend built with Symfony 7.3, providing REST API endpoints for managing students, teachers, classes, messages, notifications, and more.

## Features

### 🔐 Authentication & Authorization
- JWT-based authentication with role-based access control
- Support for multiple user types: Admin, Teacher, Student, Parent
- Secure password hashing and token management
- Protected endpoints with proper authorization

### 👥 User Management
- **Students**: Complete CRUD operations with class assignment and parent linking
- **Teachers**: Management with specialization and class assignments
- **Parents**: Parent-child relationship management
- **Administrators**: Full system access and management

### 📚 Academic Management
- **Classes**: Class creation, student enrollment, and statistics
- **Sessions**: Class scheduling and session management
- **Subjects**: Subject management and teacher assignments
- **Grades**: Student grade tracking and management

### 💬 Communication
- **Messaging**: Real-time messaging between users
- **Notifications**: System-wide notification management
- **Conversations**: Group and private messaging support

### 📊 Dashboard & Analytics
- Role-specific dashboard statistics
- Recent activity tracking
- Quick stats for today, this week, and this month
- Class and student analytics

## API Endpoints

### Authentication
```
POST /api/login          - User login
POST /api/register       - User registration
```

### Students
```
GET    /api/students                - List all students (paginated)
GET    /api/students/{id}           - Get student details
POST   /api/students                - Create new student
PUT    /api/students/{id}           - Update student
DELETE /api/students/{id}           - Delete student
GET    /api/students/by-class/{id}  - Get students by class
```

### Classes
```
GET    /api/classes                 - List all classes (paginated)
GET    /api/classes/{id}            - Get class details
POST   /api/classes                 - Create new class
PUT    /api/classes/{id}            - Update class
DELETE /api/classes/{id}            - Delete class
GET    /api/classes/{id}/students   - Get students in class
GET    /api/classes/{id}/stats      - Get class statistics
```

### Dashboard
```
GET /api/dashboard/stats           - Get dashboard statistics
GET /api/dashboard/recent-activity - Get recent activity
GET /api/dashboard/notifications   - Get user notifications
GET /api/dashboard/quick-stats     - Get quick statistics
```

### System
```
GET /api/info                      - API information
GET /api/health                    - Health check
GET /api/doc                       - Swagger documentation
```

## Configuration

### Environment Variables
```bash
# Database
DATABASE_URL="mysql://root:@127.0.0.1:3306/Smart-Compus?serverVersion=mariadb-10.4.11"

# JWT Configuration
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=your_passphrase_here

# CORS
CORS_ALLOW_ORIGIN='^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$'
```

### JWT Keys Setup
```bash
# Generate private key
openssl genrsa -out config/jwt/private.pem -aes256 -passout pass:your_passphrase 4096

# Generate public key
openssl rsa -pubout -in config/jwt/private.pem -out config/jwt/public.pem -passin pass:your_passphrase
```

## Installation

1. **Clone and install dependencies**:
   ```bash
   composer install
   ```

2. **Configure environment**:
   ```bash
   cp .env .env.local
   # Edit .env.local with your database credentials
   ```

3. **Generate JWT keys**:
   ```bash
   mkdir -p config/jwt
   openssl genrsa -out config/jwt/private.pem -aes256 -passout pass:your_passphrase 4096
   openssl rsa -pubout -in config/jwt/private.pem -out config/jwt/public.pem -passin pass:your_passphrase
   ```

4. **Setup database**:
   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate
   ```

5. **Start the server**:
   ```bash
   symfony server:start
   # or
   php -S localhost:8000 -t public/
   ```

## API Usage Examples

### Authentication
```javascript
// Login
const response = await fetch('/api/login', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({
    email: 'admin@example.com',
    password: 'password'
  })
});

const data = await response.json();
const token = data.token;

// Use token for authenticated requests
const studentsResponse = await fetch('/api/students', {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  }
});
```

### Create a Student
```javascript
const studentData = {
  email: 'student@example.com',
  firstname: 'John',
  lastname: 'Doe',
  numStudent: 'STU001',
  dateNaissance: '2005-01-15',
  classeId: 1,
  parentId: 2
};

const response = await fetch('/api/students', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify(studentData)
});
```

### Get Dashboard Stats
```javascript
const response = await fetch('/api/dashboard/stats', {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  }
});

const stats = await response.json();
console.log(stats);
```

## Security Features

- **JWT Authentication**: Secure token-based authentication
- **Role-based Access**: Different access levels for different user types
- **Password Hashing**: Secure password storage using Symfony's password hasher
- **CORS Protection**: Configured CORS for frontend integration
- **Input Validation**: Comprehensive input validation and sanitization

## CORS Configuration

The API is configured to work with common frontend development ports:
- http://localhost:3000 (React default)
- http://localhost:3001 (React alternative)
- http://localhost:8080 (Vue.js default)
- http://localhost:4200 (Angular default)

## Error Handling

The API returns consistent error responses:

```json
{
  "error": "Error message",
  "code": "ERROR_CODE",
  "field": "field_name" // for validation errors
}
```

Common HTTP status codes:
- `200` - Success
- `201` - Created
- `400` - Bad Request (validation errors)
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `409` - Conflict (duplicate data)
- `500` - Internal Server Error

## Data Serialization

The API uses Symfony's serializer with groups for consistent data output:

- `user:read` - Basic user information
- `student:read` - Student-specific data
- `teacher:read` - Teacher-specific data
- `classe:read` - Class information
- `notification:read` - Notification data

## Development

### Adding New Endpoints

1. Create a new controller in `src/Controller/Api/`
2. Add proper OpenAPI documentation with `#[OA\Tag]` and `#[OA\Response]`
3. Implement proper authorization with `#[IsGranted]`
4. Add serialization groups to entities
5. Test the endpoints

### Database Changes

1. Modify entities in `src/Entity/`
2. Generate migration: `php bin/console make:migration`
3. Review and run migration: `php bin/console doctrine:migrations:migrate`

## Contributing

1. Follow Symfony coding standards
2. Add proper OpenAPI documentation
3. Include authorization checks
4. Write comprehensive tests
5. Update this README for new features

## License

This project is proprietary software for Smart Campus management system.