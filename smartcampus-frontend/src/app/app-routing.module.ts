import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';

// Guards
import { AuthGuard, AdminGuard, TeacherGuard, StudentGuard, ParentGuard } from './guards/auth.guard';

// Components
import { LoginComponent } from './components/login/login.component';
import { DashboardComponent } from './components/dashboard/dashboard.component';
import { LayoutComponent } from './components/layout/layout.component';

// Models
import { UserRole } from './models/user.model';

const routes: Routes = [
  // Route par défaut - redirection vers le dashboard
  { path: '', redirectTo: '/dashboard', pathMatch: 'full' },
  
  // Route de connexion (accessible sans authentification)
  { path: 'login', component: LoginComponent },
  
  // Routes protégées avec layout
  {
    path: '',
    component: LayoutComponent,
    canActivate: [AuthGuard],
    children: [
      // Dashboard principal
      { 
        path: 'dashboard', 
        component: DashboardComponent,
        canActivate: [AuthGuard]
      },
      
      // Module de gestion des utilisateurs (SmartProfile)
      {
        path: 'users',
        loadChildren: () => import('./modules/users/users.module').then(m => m.UsersModule),
        canActivate: [TeacherGuard]
      },
      
      // Module de gestion des classes
      {
        path: 'classes',
        loadChildren: () => import('./modules/classes/classes.module').then(m => m.ClassesModule),
        canActivate: [TeacherGuard]
      },
      
      // Module SessionTracker (gestion des séances)
      {
        path: 'seances',
        loadChildren: () => import('./modules/seances/seances.module').then(m => m.SeancesModule),
        canActivate: [TeacherGuard]
      },
      
      // Module SmartCalendar (calendrier et planning)
      {
        path: 'calendar',
        loadChildren: () => import('./modules/calendar/calendar.module').then(m => m.CalendarModule),
        canActivate: [AuthGuard]
      },
      
      // Module ConnectRoom (messagerie)
      {
        path: 'messages',
        loadChildren: () => import('./modules/messages/messages.module').then(m => m.MessagesModule),
        canActivate: [AuthGuard]
      },
      
      // Module de notifications
      {
        path: 'notifications',
        loadChildren: () => import('./modules/notifications/notifications.module').then(m => m.NotificationsModule),
        canActivate: [AuthGuard]
      },
      
      // Module d'administration
      {
        path: 'admin',
        loadChildren: () => import('./modules/admin/admin.module').then(m => m.AdminModule),
        canActivate: [AdminGuard]
      },
      
      // Module pour les enseignants
      {
        path: 'teacher',
        loadChildren: () => import('./modules/teacher/teacher.module').then(m => m.TeacherModule),
        canActivate: [TeacherGuard]
      },
      
      // Module pour les étudiants
      {
        path: 'student',
        loadChildren: () => import('./modules/student/student.module').then(m => m.StudentModule),
        canActivate: [StudentGuard]
      },
      
      // Module pour les parents
      {
        path: 'parent',
        loadChildren: () => import('./modules/parent/parent.module').then(m => m.ParentModule),
        canActivate: [ParentGuard]
      },
      
      // Profil utilisateur
      {
        path: 'profile',
        loadChildren: () => import('./modules/profile/profile.module').then(m => m.ProfileModule),
        canActivate: [AuthGuard]
      }
    ]
  },
  
  // Route 404 - page non trouvée
  { path: '**', redirectTo: '/dashboard' }
];

@NgModule({
  imports: [RouterModule.forRoot(routes, {
    enableTracing: false, // Mettre à true pour debug
    useHash: false
  })],
  exports: [RouterModule]
})
export class AppRoutingModule { }