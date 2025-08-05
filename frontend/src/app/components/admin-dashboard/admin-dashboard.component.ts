import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { MatCardModule } from '@angular/material/card';
import { MatIconModule } from '@angular/material/icon';
import { MatButtonModule } from '@angular/material/button';
import { MatTableModule } from '@angular/material/table';
import { MatTabsModule } from '@angular/material/tabs';
import { MatChipsModule } from '@angular/material/chips';
import { RouterLink } from '@angular/router';

import { ApiService } from '../../services/api.service';

@Component({
  selector: 'app-admin-dashboard',
  standalone: true,
  imports: [
    CommonModule,
    MatCardModule,
    MatIconModule,
    MatButtonModule,
    MatTableModule,
    MatTabsModule,
    MatChipsModule,
    RouterLink
  ],
  template: `
    <div class="dashboard-container">
      <h1>Tableau de bord Administrateur</h1>
      
      <!-- Statistics Cards -->
      <div class="stats-grid" *ngIf="dashboardData">
        <mat-card class="stat-card">
          <mat-card-header>
            <mat-card-title>
              <mat-icon>people</mat-icon>
              Total Utilisateurs
            </mat-card-title>
          </mat-card-header>
          <mat-card-content>
            <div class="stat-number">{{ dashboardData.statistics.total_users }}</div>
          </mat-card-content>
        </mat-card>

        <mat-card class="stat-card students">
          <mat-card-header>
            <mat-card-title>
              <mat-icon>school</mat-icon>
              Étudiants
            </mat-card-title>
          </mat-card-header>
          <mat-card-content>
            <div class="stat-number">{{ dashboardData.statistics.total_students }}</div>
          </mat-card-content>
        </mat-card>

        <mat-card class="stat-card teachers">
          <mat-card-header>
            <mat-card-title>
              <mat-icon>person</mat-icon>
              Enseignants
            </mat-card-title>
          </mat-card-header>
          <mat-card-content>
            <div class="stat-number">{{ dashboardData.statistics.total_teachers }}</div>
          </mat-card-content>
        </mat-card>

        <mat-card class="stat-card parents">
          <mat-card-header>
            <mat-card-title>
              <mat-icon>family_restroom</mat-icon>
              Parents
            </mat-card-title>
          </mat-card-header>
          <mat-card-content>
            <div class="stat-number">{{ dashboardData.statistics.total_parents }}</div>
          </mat-card-content>
        </mat-card>
      </div>

      <!-- Students by Class Chart -->
      <mat-card class="chart-card" *ngIf="dashboardData?.statistics.students_by_class">
        <mat-card-header>
          <mat-card-title>Répartition des étudiants par classe</mat-card-title>
        </mat-card-header>
        <mat-card-content>
          <div class="class-distribution">
            <div class="class-item" *ngFor="let item of getClassDistribution()">
              <span class="class-name">{{ item.className }}</span>
              <span class="class-count">{{ item.count }} étudiants</span>
            </div>
          </div>
        </mat-card-content>
      </mat-card>

      <!-- User Management Tabs -->
      <mat-card class="users-card">
        <mat-card-header>
          <mat-card-title>Gestion des utilisateurs</mat-card-title>
          <mat-card-subtitle>Vue d'ensemble des utilisateurs par type</mat-card-subtitle>
        </mat-card-header>
        <mat-card-content>
          <mat-tab-group>
            <!-- Students Tab -->
            <mat-tab label="Étudiants">
              <div class="tab-content">
                <div class="tab-header">
                  <h3>Étudiants ({{ dashboardData?.students?.length || 0 }})</h3>
                  <button mat-raised-button color="primary" routerLink="/users/students">
                    Gérer les étudiants
                  </button>
                </div>
                <mat-table [dataSource]="getStudentsPreview()" class="preview-table">
                  <ng-container matColumnDef="name">
                    <mat-header-cell *matHeaderCellDef>Nom</mat-header-cell>
                    <mat-cell *matCellDef="let student">
                      {{ student.firstname }} {{ student.lastname }}
                    </mat-cell>
                  </ng-container>
                  <ng-container matColumnDef="email">
                    <mat-header-cell *matHeaderCellDef>Email</mat-header-cell>
                    <mat-cell *matCellDef="let student">{{ student.email }}</mat-cell>
                  </ng-container>
                  <ng-container matColumnDef="class">
                    <mat-header-cell *matHeaderCellDef>Classe</mat-header-cell>
                    <mat-cell *matCellDef="let student">
                      <mat-chip *ngIf="student.classe">{{ student.classe.nom }}</mat-chip>
                      <span *ngIf="!student.classe" class="no-class">Non assigné</span>
                    </mat-cell>
                  </ng-container>
                  <mat-header-row *matHeaderRowDef="['name', 'email', 'class']"></mat-header-row>
                  <mat-row *matRowDef="let row; columns: ['name', 'email', 'class']"></mat-row>
                </mat-table>
              </div>
            </mat-tab>

            <!-- Teachers Tab -->
            <mat-tab label="Enseignants">
              <div class="tab-content">
                <div class="tab-header">
                  <h3>Enseignants ({{ dashboardData?.teachers?.length || 0 }})</h3>
                  <button mat-raised-button color="primary" routerLink="/users/teachers">
                    Gérer les enseignants
                  </button>
                </div>
                <mat-table [dataSource]="getTeachersPreview()" class="preview-table">
                  <ng-container matColumnDef="name">
                    <mat-header-cell *matHeaderCellDef>Nom</mat-header-cell>
                    <mat-cell *matCellDef="let teacher">
                      {{ teacher.firstname }} {{ teacher.lastname }}
                    </mat-cell>
                  </ng-container>
                  <ng-container matColumnDef="email">
                    <mat-header-cell *matHeaderCellDef>Email</mat-header-cell>
                    <mat-cell *matCellDef="let teacher">{{ teacher.email }}</mat-cell>
                  </ng-container>
                  <ng-container matColumnDef="subjects">
                    <mat-header-cell *matHeaderCellDef>Matières</mat-header-cell>
                    <mat-cell *matCellDef="let teacher">
                      <mat-chip *ngFor="let subject of teacher.matieres" class="subject-chip">
                        {{ subject.nom }}
                      </mat-chip>
                    </mat-cell>
                  </ng-container>
                  <mat-header-row *matHeaderRowDef="['name', 'email', 'subjects']"></mat-header-row>
                  <mat-row *matRowDef="let row; columns: ['name', 'email', 'subjects']"></mat-row>
                </mat-table>
              </div>
            </mat-tab>

            <!-- Parents Tab -->
            <mat-tab label="Parents">
              <div class="tab-content">
                <div class="tab-header">
                  <h3>Parents ({{ dashboardData?.parents?.length || 0 }})</h3>
                  <button mat-raised-button color="primary" routerLink="/users/parents">
                    Gérer les parents
                  </button>
                </div>
                <mat-table [dataSource]="getParentsPreview()" class="preview-table">
                  <ng-container matColumnDef="name">
                    <mat-header-cell *matHeaderCellDef>Nom</mat-header-cell>
                    <mat-cell *matCellDef="let parent">
                      {{ parent.firstname }} {{ parent.lastname }}
                    </mat-cell>
                  </ng-container>
                  <ng-container matColumnDef="email">
                    <mat-header-cell *matHeaderCellDef>Email</mat-header-cell>
                    <mat-cell *matCellDef="let parent">{{ parent.email }}</mat-cell>
                  </ng-container>
                  <ng-container matColumnDef="children">
                    <mat-header-cell *matHeaderCellDef>Enfants</mat-header-cell>
                    <mat-cell *matCellDef="let parent">
                      <span *ngIf="parent.enfants?.length; else noChildren">
                        {{ parent.enfants.length }} enfant(s)
                      </span>
                      <ng-template #noChildren>
                        <span class="no-children">Aucun enfant</span>
                      </ng-template>
                    </mat-cell>
                  </ng-container>
                  <mat-header-row *matHeaderRowDef="['name', 'email', 'children']"></mat-header-row>
                  <mat-row *matRowDef="let row; columns: ['name', 'email', 'children']"></mat-row>
                </mat-table>
              </div>
            </mat-tab>
          </mat-tab-group>
        </mat-card-content>
      </mat-card>

      <!-- Quick Actions -->
      <mat-card class="actions-card">
        <mat-card-header>
          <mat-card-title>Actions rapides</mat-card-title>
        </mat-card-header>
        <mat-card-content>
          <div class="actions-grid">
            <button mat-raised-button color="primary" routerLink="/timetables/create">
              <mat-icon>schedule</mat-icon>
              Créer un emploi du temps
            </button>
            <button mat-raised-button color="accent" routerLink="/calendar/create-event">
              <mat-icon>event</mat-icon>
              Ajouter un événement
            </button>
            <button mat-raised-button color="warn" routerLink="/alerts/send">
              <mat-icon>notifications</mat-icon>
              Envoyer une alerte
            </button>
            <button mat-raised-button routerLink="/users/create">
              <mat-icon>person_add</mat-icon>
              Ajouter un utilisateur
            </button>
          </div>
        </mat-card-content>
      </mat-card>
    </div>
  `,
  styles: [`
    .dashboard-container {
      padding: 20px;
      max-width: 1400px;
      margin: 0 auto;
    }

    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }

    .stat-card {
      text-align: center;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
    }

    .stat-card.students {
      background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    }

    .stat-card.teachers {
      background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    }

    .stat-card.parents {
      background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    }

    .stat-card mat-card-header {
      justify-content: center;
    }

    .stat-card mat-card-title {
      display: flex;
      align-items: center;
      gap: 10px;
      color: white;
    }

    .stat-number {
      font-size: 3rem;
      font-weight: bold;
      margin-top: 10px;
    }

    .chart-card, .users-card, .actions-card {
      margin-bottom: 30px;
    }

    .class-distribution {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 15px;
    }

    .class-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 10px 15px;
      background: #f5f5f5;
      border-radius: 8px;
    }

    .class-name {
      font-weight: 500;
    }

    .class-count {
      color: #666;
      font-size: 0.9rem;
    }

    .tab-content {
      padding: 20px 0;
    }

    .tab-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }

    .preview-table {
      width: 100%;
    }

    .no-class, .no-children {
      color: #999;
      font-style: italic;
    }

    .subject-chip {
      margin-right: 5px;
      margin-bottom: 5px;
    }

    .actions-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 15px;
    }

    .actions-grid button {
      height: 60px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    h1 {
      color: #333;
      margin-bottom: 30px;
    }

    h3 {
      margin: 0;
      color: #555;
    }
  `]
})
export class AdminDashboardComponent implements OnInit {
  dashboardData: any = null;
  isLoading = true;

  constructor(private apiService: ApiService) {}

  ngOnInit() {
    this.loadDashboardData();
  }

  loadDashboardData() {
    this.apiService.getAdminDashboard().subscribe({
      next: (data) => {
        this.dashboardData = data;
        this.isLoading = false;
      },
      error: (error) => {
        console.error('Error loading dashboard data:', error);
        this.isLoading = false;
      }
    });
  }

  getClassDistribution() {
    if (!this.dashboardData?.statistics?.students_by_class) {
      return [];
    }
    
    return Object.entries(this.dashboardData.statistics.students_by_class).map(([className, count]) => ({
      className,
      count
    }));
  }

  getStudentsPreview() {
    return this.dashboardData?.students?.slice(0, 5) || [];
  }

  getTeachersPreview() {
    return this.dashboardData?.teachers?.slice(0, 5) || [];
  }

  getParentsPreview() {
    return this.dashboardData?.parents?.slice(0, 5) || [];
  }
}