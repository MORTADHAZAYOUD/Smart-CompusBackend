import { Routes } from '@angular/router';

export const routes: Routes = [
  {
    path: '',
    redirectTo: '/dashboard',
    pathMatch: 'full'
  },
  {
    path: 'dashboard',
    loadComponent: () => import('./components/admin-dashboard/admin-dashboard.component')
      .then(m => m.AdminDashboardComponent)
  },
  {
    path: 'calendar',
    loadComponent: () => import('./components/calendar/calendar.component')
      .then(m => m.CalendarComponent)
  },
  {
    path: 'users',
    children: [
      {
        path: '',
        loadComponent: () => import('./components/user-management/user-list.component')
          .then(m => m.UserListComponent)
      },
      {
        path: 'students',
        loadComponent: () => import('./components/user-management/student-list.component')
          .then(m => m.StudentListComponent)
      },
      {
        path: 'teachers',
        loadComponent: () => import('./components/user-management/teacher-list.component')
          .then(m => m.TeacherListComponent)
      },
      {
        path: 'parents',
        loadComponent: () => import('./components/user-management/parent-list.component')
          .then(m => m.ParentListComponent)
      },
      {
        path: 'create',
        loadComponent: () => import('./components/user-management/user-create.component')
          .then(m => m.UserCreateComponent)
      }
    ]
  },
  {
    path: 'timetables',
    children: [
      {
        path: '',
        loadComponent: () => import('./components/timetable/timetable-list.component')
          .then(m => m.TimetableListComponent)
      },
      {
        path: 'create',
        loadComponent: () => import('./components/timetable/timetable-create.component')
          .then(m => m.TimetableCreateComponent)
      },
      {
        path: 'weekly/:userId',
        loadComponent: () => import('./components/timetable/weekly-timetable.component')
          .then(m => m.WeeklyTimetableComponent)
      }
    ]
  },
  {
    path: 'alerts',
    children: [
      {
        path: '',
        loadComponent: () => import('./components/alerts/alert-list.component')
          .then(m => m.AlertListComponent)
      },
      {
        path: 'send',
        loadComponent: () => import('./components/alerts/send-alert.component')
          .then(m => m.SendAlertComponent)
      }
    ]
  },
  {
    path: '**',
    redirectTo: '/dashboard'
  }
];