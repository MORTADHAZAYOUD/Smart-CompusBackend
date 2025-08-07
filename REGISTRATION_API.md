# Registration API Documentation

This document describes the enhanced registration API endpoint that handles user registration from the Angular frontend.

## Endpoint

**POST** `/api/register`

## Features

- ✅ **Multi-user type support**: Student, Teacher, Parent, Admin
- ✅ **Comprehensive validation**: Matches Angular frontend validation
- ✅ **Class management**: Automatic class creation from class names
- ✅ **Children name handling**: Support for parent-child relationships
- ✅ **Detailed error responses**: Specific error codes for frontend handling
- ✅ **Database transactions**: Rollback on failures
- ✅ **Password hashing**: Secure password storage

## Request Format

### Common Fields (All User Types)
```json
{
  "email": "user@example.com",
  "password": "password123",
  "firstname": "John",
  "lastname": "Doe",
  "type": "Student|Teacher|Parent|Admin"
}
```

### Student Registration
```json
{
  "email": "student@example.com",
  "password": "password123", 
  "firstname": "Jean",
  "lastname": "Dupont",
  "type": "Student",
  "dateNaissance": "2008-05-15",
  "classe_id": "Terminale S"
}
```

**Note**: `classe_id` can be either:
- A numeric class ID (if class already exists)
- A class name string (will create new class if needed)

### Teacher Registration
```json
{
  "email": "teacher@example.com",
  "password": "password123",
  "firstname": "Marie", 
  "lastname": "Martin",
  "type": "Teacher",
  "specialite": "Mathématiques"
}
```

### Parent Registration
```json
{
  "email": "parent@example.com",
  "password": "password123",
  "firstname": "Pierre",
  "lastname": "Durand", 
  "type": "Parent",
  "profession": "Ingénieur",
  "telephone": "0123456789",
  "childrenNames": ["Jean Dupont", "Sophie Durand"]
}
```

## Validation Rules

### Common Validation
- **Email**: Valid email format, unique in database
- **Password**: Minimum 6 characters
- **Firstname**: Minimum 2 characters, only letters, spaces, hyphens, apostrophes
- **Lastname**: Minimum 2 characters, only letters, spaces, hyphens, apostrophes
- **Type**: Must be one of: Student, Teacher, Parent, Admin

### Student-Specific Validation
- **dateNaissance**: Required, valid date, age between 3-25 years
- **classe_id**: Required, class name or numeric ID

### Teacher-Specific Validation
- **specialite**: Required, minimum 3 characters

### Parent-Specific Validation
- **profession**: Required, minimum 2 characters
- **childrenNames**: Required array, each name minimum 2 characters, valid characters only

## Response Format

### Success Response
```json
{
  "success": true,
  "message": "Inscription réussie !",
  "user": {
    "id": 123,
    "email": "user@example.com",
    "firstname": "John",
    "lastname": "Doe", 
    "type": "Student"
  }
}
```

### Error Response
```json
{
  "success": false,
  "error": "Error message in French",
  "code": "ERROR_CODE",
  "field": "fieldName" // Optional, for field-specific errors
}
```

## Error Codes

### General Errors
- `INVALID_JSON`: Malformed JSON data
- `MISSING_FIELD`: Required field missing
- `INVALID_EMAIL`: Invalid email format
- `EMAIL_ALREADY_USED`: Email already registered
- `USER_EXISTS`: User with email already exists
- `PASSWORD_TOO_SHORT`: Password less than 6 characters
- `INVALID_USER_TYPE`: Invalid user type provided

### Name Validation Errors
- `INVALID_FIRSTNAME_LENGTH`: Firstname too short
- `INVALID_FIRSTNAME_CHARACTERS`: Invalid characters in firstname
- `INVALID_LASTNAME_LENGTH`: Lastname too short
- `INVALID_LASTNAME_CHARACTERS`: Invalid characters in lastname

### Student-Specific Errors
- `MISSING_BIRTH_DATE`: Birth date required for students
- `INVALID_DATE_FORMAT`: Invalid date format
- `INVALID_AGE`: Age not between 3-25 years
- `MISSING_CLASS_NAME`: Class name required
- `CLASS_NOT_FOUND`: Class ID not found
- `CLASS_MANAGEMENT_ERROR`: Error creating/finding class

### Teacher-Specific Errors  
- `MISSING_SPECIALITY`: Specialty required for teachers
- `INVALID_SPECIALITY_LENGTH`: Specialty too short

### Parent-Specific Errors
- `MISSING_PROFESSION`: Profession required for parents
- `INVALID_PROFESSION_LENGTH`: Profession too short
- `MISSING_CHILDREN_NAMES`: Children names required
- `MISSING_CHILD_NAME`: Specific child name missing
- `INVALID_CHILD_NAME_LENGTH`: Child name too short
- `INVALID_CHILD_NAME_CHARACTERS`: Invalid characters in child name

### Database Errors
- `DATABASE_CHECK_ERROR`: Error checking existing users
- `DATABASE_SAVE_ERROR`: Error saving to database
- `USER_CREATION_FAILED`: Error creating user object
- `UNEXPECTED_ERROR`: Unexpected system error

## Testing

Use the provided test script:

```bash
php test_registration.php
```

This generates sample cURL commands for testing all user types and error scenarios.

### Sample cURL Commands

**Student Registration:**
```bash
curl -X POST http://localhost:8000/api/register \
  -H 'Content-Type: application/json' \
  -d '{
    "email": "student@example.com",
    "password": "password123",
    "firstname": "Jean",
    "lastname": "Dupont", 
    "type": "Student",
    "dateNaissance": "2008-05-15",
    "classe_id": "Terminale S"
  }'
```

**Teacher Registration:**
```bash
curl -X POST http://localhost:8000/api/register \
  -H 'Content-Type: application/json' \
  -d '{
    "email": "teacher@example.com", 
    "password": "password123",
    "firstname": "Marie",
    "lastname": "Martin",
    "type": "Teacher",
    "specialite": "Mathématiques"
  }'
```

**Parent Registration:**
```bash
curl -X POST http://localhost:8000/api/register \
  -H 'Content-Type: application/json' \
  -d '{
    "email": "parent@example.com",
    "password": "password123", 
    "firstname": "Pierre",
    "lastname": "Durand",
    "type": "Parent",
    "profession": "Ingénieur", 
    "telephone": "0123456789",
    "childrenNames": ["Jean Dupont", "Sophie Durand"]
  }'
```

## Implementation Details

### Files Modified/Created

1. **`src/Controller/Api/RegistrationController.php`** - Enhanced registration endpoint
2. **`src/Service/UserRegistrationService.php`** - New service for registration logic
3. **`src/Entity/Student.php`** - Added parent relationship methods
4. **`src/Entity/ParentUser.php`** - Added children collection methods

### Key Features

- **Transaction Safety**: All database operations wrapped in transactions
- **Class Auto-Creation**: New classes created automatically from names
- **Flexible Class Assignment**: Supports both class IDs and names
- **Comprehensive Validation**: Matches Angular frontend validation exactly
- **Error Code Mapping**: Each error has specific code for frontend handling
- **Parent-Child Preparation**: Foundation for linking parents to children

### Future Enhancements

- **Parent-Child Linking**: Automatic linking of parents to existing student children
- **Email Verification**: Send confirmation emails
- **Role-Based Validation**: Different validation rules per user type
- **Bulk Registration**: Support for registering multiple users
- **Advanced Password Rules**: Complex password requirements

## Angular Frontend Integration

The API is designed to work seamlessly with the provided Angular registration component. All error codes and validation rules match the frontend expectations.

### Frontend Error Handling

```typescript
// The frontend can handle specific errors like this:
switch (errorResponse.code) {
  case 'EMAIL_ALREADY_USED':
    this.showFieldError('email', errorResponse.error);
    break;
  case 'INVALID_AGE':
    this.showFieldError('birthDate', errorResponse.error);
    break;
  // ... other error cases
}
```

### Frontend Success Handling

```typescript
// On successful registration:
if (response.success) {
  this.successMessage = response.message;
  setTimeout(() => {
    this.router.navigate(['/login']);
  }, 2000);
}
```

## Database Schema

The API works with the existing Symfony entity structure:

- **User** (base class)
  - **Student** (extends User)
  - **Teacher** (extends User) 
  - **ParentUser** (extends User)
  - **Administrator** (extends User)
- **Classe** (class/grade management)

## Security Considerations

- ✅ Password hashing with Symfony's UserPasswordHasher
- ✅ Email uniqueness validation
- ✅ Input sanitization and validation
- ✅ Transaction rollback on failures
- ✅ Proper error handling without data leakage

## Getting Started

1. **Start the Symfony server:**
   ```bash
   symfony serve
   # or
   php -S localhost:8000 -t public
   ```

2. **Test the endpoint:**
   ```bash
   php test_registration.php
   ```

3. **Run the generated cURL commands to verify functionality**

The registration API is now fully compatible with the Angular frontend and provides comprehensive user registration capabilities with proper validation and error handling.