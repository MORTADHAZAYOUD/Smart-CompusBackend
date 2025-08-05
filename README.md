# École Management System

A comprehensive school management system built with Symfony (backend) and Angular (frontend) featuring user management, timetables, alerts, and calendar functionality.

## Features

### ✅ Admin Dashboard
- **User Statistics**: Complete overview of all registered users by type (students, teachers, parents, admins)
- **User Management**: List and manage all users with filtering by role
- **Class Distribution**: Visual representation of students per class
- **Quick Actions**: Fast access to common administrative tasks

### ✅ User Management by Type
- **Students**: Student registration, class assignment, parent linking
- **Teachers**: Teacher profiles with subject assignments
- **Parents**: Parent accounts linked to their children
- **Administrators**: Admin privileges and system access control

### ✅ Timetable Management
- **Individual Timetables**: Personal schedules for each user
- **Weekly Views**: Weekly schedule display with filtering options
- **Bulk Operations**: Create multiple timetables simultaneously
- **Recurring Events**: Support for recurring schedule items
- **Class-based Scheduling**: Link timetables to specific classes and subjects

### ✅ Alert System
- **Student Alerts**: Send notifications to all or specific students
- **Parent Alerts**: Notify parents about important events
- **Class Alerts**: Send alerts to entire classes (students + parents)
- **Exam Alerts**: Specialized notifications for exam schedules
- **Vacation Alerts**: Notify about vacation periods
- **Emergency Alerts**: Priority notifications for urgent situations
- **Read Status**: Track which alerts have been read by users

### ✅ Calendar System
- **Event Management**: Create, edit, and delete calendar events
- **Event Types**: Support for exams, vacations, meetings, personal events
- **Student Calendar**: Students can add personal events, view exams and vacations
- **Interactive Calendar**: FullCalendar integration with drag-and-drop
- **Recurring Events**: Support for daily, weekly, monthly recurring events
- **Event Search**: Search functionality across all calendar events

### ✅ Angular Frontend
- **Material Design**: Modern UI using Angular Material
- **Responsive Design**: Mobile-friendly interface
- **Component-based Architecture**: Modular and maintainable code structure
- **Real-time Updates**: Live data synchronization with backend
- **Interactive Calendar**: FullCalendar integration for event management

## Technology Stack

### Backend (Symfony 7.3)
- **PHP 8.2+**
- **Symfony Framework 7.3**
- **Doctrine ORM** for database management
- **JWT Authentication** for secure API access
- **OpenAPI Documentation** for API endpoints
- **MySQL/PostgreSQL** database support

### Frontend (Angular 17)
- **Angular 17** with standalone components
- **Angular Material** for UI components
- **FullCalendar** for calendar functionality
- **RxJS** for reactive programming
- **TypeScript** for type safety
- **SCSS** for styling

## Installation & Setup

### Backend Setup (Symfony)

1. **Clone the repository**
```bash
git clone <repository-url>
cd school-management-system
```

2. **Install PHP dependencies**
```bash
composer install
```

3. **Configure environment**
```bash
cp .env .env.local
# Edit .env.local with your database credentials
```

4. **Set up database**
```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

5. **Start the development server**
```bash
symfony server:start
```

The Symfony API will be available at `http://localhost:8000`

### Frontend Setup (Angular)

1. **Navigate to frontend directory**
```bash
cd frontend
```

2. **Install Node.js dependencies**
```bash
npm install
```

3. **Start development server**
```bash
npm start
```

The Angular application will be available at `http://localhost:4200`

## API Documentation

### Admin Dashboard
- `GET /api/users/admin/dashboard` - Get complete admin dashboard data with user statistics

### User Management
- `GET /api/users` - List all users (with optional role filter)
- `GET /api/users/{id}` - Get specific user details
- `POST /api/users` - Create new user
- `PUT /api/users/{id}` - Update user
- `DELETE /api/users/{id}` - Delete user

### Timetable Management
- `GET /api/timetables` - List timetables (with filters)
- `POST /api/timetables` - Create new timetable
- `PUT /api/timetables/{id}` - Update timetable
- `DELETE /api/timetables/{id}` - Delete timetable
- `GET /api/timetables/user/{userId}/week` - Get weekly timetable for user
- `POST /api/timetables/admin/bulk` - Bulk create timetables

### Calendar Events
- `GET /api/calendar` - List calendar events
- `POST /api/calendar/events` - Create new event
- `PUT /api/calendar/events/{id}` - Update event
- `DELETE /api/calendar/events/{id}` - Delete event
- `GET /api/calendar/user/{userId}/month/{year}/{month}` - Monthly calendar for user
- `GET /api/calendar/exams/upcoming/{userId}` - Upcoming exams for user
- `GET /api/calendar/vacations` - List vacation periods
- `GET /api/calendar/search` - Search calendar events

### Alert System
- `POST /api/alerts/send/students` - Send alert to students
- `POST /api/alerts/send/parents` - Send alert to parents
- `POST /api/alerts/send/class/{classeId}` - Send alert to class
- `POST /api/alerts/send/exam` - Send exam alert
- `POST /api/alerts/send/vacation` - Send vacation alert
- `POST /api/alerts/send/emergency` - Send emergency alert
- `GET /api/alerts/user/{userId}/unread-count` - Get unread alerts count
- `GET /api/alerts/user/{userId}/recent` - Get recent alerts
- `POST /api/alerts/mark-read/{notificationId}` - Mark alert as read
- `POST /api/alerts/mark-multiple-read` - Mark multiple alerts as read

## Database Schema

### Core Entities

#### User (Base Entity)
- `id` - Primary key
- `email` - Unique email address
- `password` - Hashed password
- `firstname` - First name
- `lastname` - Last name
- `roles` - User roles array

#### Student (extends User)
- `numStudent` - Student number
- `dateNaissance` - Date of birth
- `classe` - Link to Class entity
- `parent` - Link to ParentUser entity

#### Teacher (extends User)
- `matieres` - Collection of subjects taught

#### ParentUser (extends User)
- `enfants` - Collection of children (Student entities)

#### Administrator (extends User)
- `privileges` - Array of admin privileges

#### Timetable
- `title` - Event title
- `description` - Event description
- `startTime` - Start date/time
- `endTime` - End date/time
- `dayOfWeek` - Day of the week
- `type` - Event type (class, exam, meeting)
- `user` - Associated user
- `classe` - Associated class
- `matiere` - Associated subject
- `location` - Event location
- `isRecurring` - Whether event recurs
- `recurringPattern` - Recurrence pattern

#### Evenement (Calendar Events)
- `titre` - Event title
- `description` - Event description
- `date` - Event start date
- `endDate` - Event end date
- `type` - Event type (exam, vacation, meeting, personal, general)
- `priority` - Priority level
- `location` - Event location
- `isPublic` - Public visibility
- `isAllDay` - All-day event flag
- `color` - Display color
- `creator` - User who created the event
- `classe` - Associated class
- `matiere` - Associated subject
- `attendees` - Array of attendee user IDs
- `isRecurring` - Recurring event flag
- `recurringPattern` - Recurrence pattern

#### Notification
- `titre` - Notification title
- `contenu` - Notification content
- `destinataire` - Target user
- `priorite` - Priority level (normale, haute, urgente)
- `dateCreation` - Creation date
- `lu` - Read status
- `vue` - Viewed status

## Features in Detail

### Admin User Management
Administrators can view comprehensive statistics including:
- Total number of users by type
- Student distribution across classes
- Recently registered users
- Quick access to manage each user type

### Timetable System
The timetable system supports:
- Individual schedules for students and teachers
- Class-wide timetables
- Weekly and daily views
- Recurring events (weekly, monthly patterns)
- Integration with class and subject entities
- Bulk creation for administrative efficiency

### Alert & Notification System
Comprehensive notification system featuring:
- **Targeted Alerts**: Send to specific user types or individuals
- **Class-wide Notifications**: Alert entire classes including parents
- **Exam Notifications**: Automated exam reminders
- **Vacation Announcements**: School holiday notifications
- **Emergency Alerts**: High-priority urgent notifications
- **Read Tracking**: Monitor which users have seen alerts
- **Priority Levels**: Normal, high, and urgent priority classification

### Calendar Integration
Full-featured calendar system with:
- **Event Types**: Exams, vacations, meetings, personal events
- **Student Features**: Students can add personal events and view school events
- **Visual Calendar**: FullCalendar integration with color-coded events
- **Search Functionality**: Find events by title or description
- **Event Management**: Create, edit, delete with proper permissions
- **Recurring Events**: Support for daily, weekly, monthly recurrence

### Angular Frontend Features
Modern, responsive frontend with:
- **Material Design**: Consistent, professional UI
- **Interactive Calendar**: Drag-and-drop event management
- **Real-time Data**: Live updates from backend
- **Responsive Design**: Works on desktop, tablet, and mobile
- **Component Architecture**: Modular, maintainable code structure
- **Type Safety**: Full TypeScript integration

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Support

For support, please open an issue in the GitHub repository or contact the development team.

---

**École Management System** - Complete school management solution with modern architecture and comprehensive features.