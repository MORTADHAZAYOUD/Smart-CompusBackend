import { Component } from '@angular/core';
import { RouterOutlet } from '@angular/router';

@Component({
  selector: 'app-root',
  standalone: true,
  imports: [RouterOutlet],
  template: `
    <div class="app-container">
      <header class="app-header">
        <mat-toolbar color="primary">
          <mat-toolbar-row>
            <span>École Management System</span>
            <span class="spacer"></span>
            <button mat-icon-button [matMenuTriggerFor]="userMenu">
              <mat-icon>account_circle</mat-icon>
            </button>
            <mat-menu #userMenu="matMenu">
              <button mat-menu-item>
                <mat-icon>person</mat-icon>
                <span>Profil</span>
              </button>
              <button mat-menu-item>
                <mat-icon>settings</mat-icon>
                <span>Paramètres</span>
              </button>
              <mat-divider></mat-divider>
              <button mat-menu-item>
                <mat-icon>exit_to_app</mat-icon>
                <span>Déconnexion</span>
              </button>
            </mat-menu>
          </mat-toolbar-row>
        </mat-toolbar>
      </header>

      <div class="app-content">
        <nav class="sidebar">
          <mat-nav-list>
            <a mat-list-item routerLink="/dashboard" routerLinkActive="active">
              <mat-icon matListIcon>dashboard</mat-icon>
              <span matLine>Tableau de bord</span>
            </a>
            <a mat-list-item routerLink="/users" routerLinkActive="active">
              <mat-icon matListIcon>people</mat-icon>
              <span matLine>Utilisateurs</span>
            </a>
            <a mat-list-item routerLink="/timetables" routerLinkActive="active">
              <mat-icon matListIcon>schedule</mat-icon>
              <span matLine>Emplois du temps</span>
            </a>
            <a mat-list-item routerLink="/calendar" routerLinkActive="active">
              <mat-icon matListIcon>event</mat-icon>
              <span matLine>Calendrier</span>
            </a>
            <a mat-list-item routerLink="/alerts" routerLinkActive="active">
              <mat-icon matListIcon>notifications</mat-icon>
              <span matLine>Alertes</span>
            </a>
          </mat-nav-list>
        </nav>

        <main class="main-content">
          <router-outlet></router-outlet>
        </main>
      </div>
    </div>
  `,
  styles: [`
    .app-container {
      height: 100vh;
      display: flex;
      flex-direction: column;
    }

    .app-header {
      flex-shrink: 0;
    }

    .spacer {
      flex: 1 1 auto;
    }

    .app-content {
      flex: 1;
      display: flex;
      overflow: hidden;
    }

    .sidebar {
      width: 250px;
      background-color: #f5f5f5;
      border-right: 1px solid #ddd;
      flex-shrink: 0;
    }

    .main-content {
      flex: 1;
      padding: 20px;
      overflow-y: auto;
    }

    .active {
      background-color: rgba(63, 81, 181, 0.12) !important;
      color: #3f51b5 !important;
    }

    mat-nav-list a {
      text-decoration: none;
      color: inherit;
    }

    mat-toolbar {
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
  `]
})
export class AppComponent {
  title = 'school-management-frontend';
}