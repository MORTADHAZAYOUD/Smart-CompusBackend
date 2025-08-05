import { Component, OnInit, ViewChild } from '@angular/core';
import { CommonModule } from '@angular/common';
import { MatCardModule } from '@angular/material/card';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatDialogModule, MatDialog } from '@angular/material/dialog';
import { MatSelectModule } from '@angular/material/select';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { FullCalendarModule } from '@fullcalendar/angular';
import { CalendarOptions, DateSelectArg, EventClickArg, EventApi } from '@fullcalendar/core';
import interactionPlugin from '@fullcalendar/interaction';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';

import { ApiService, CalendarEvent } from '../../services/api.service';

@Component({
  selector: 'app-calendar',
  standalone: true,
  imports: [
    CommonModule,
    MatCardModule,
    MatButtonModule,
    MatIconModule,
    MatDialogModule,
    MatSelectModule,
    MatFormFieldModule,
    MatInputModule,
    FullCalendarModule
  ],
  template: `
    <div class="calendar-container">
      <mat-card class="calendar-header">
        <mat-card-header>
          <mat-card-title>
            <mat-icon>event</mat-icon>
            Calendrier Scolaire
          </mat-card-title>
          <div class="header-actions">
            <mat-form-field appearance="outline" class="filter-field">
              <mat-label>Filtrer par type</mat-label>
              <mat-select [(value)]="selectedEventType" (selectionChange)="onFilterChange()">
                <mat-option value="">Tous les événements</mat-option>
                <mat-option value="exam">Examens</mat-option>
                <mat-option value="vacation">Vacances</mat-option>
                <mat-option value="meeting">Réunions</mat-option>
                <mat-option value="personal">Personnel</mat-option>
                <mat-option value="general">Général</mat-option>
              </mat-select>
            </mat-form-field>
            <button mat-raised-button color="primary" (click)="openCreateEventDialog()">
              <mat-icon>add</mat-icon>
              Nouvel événement
            </button>
          </div>
        </mat-card-header>
      </mat-card>

      <div class="calendar-content">
        <mat-card class="calendar-card">
          <mat-card-content>
            <full-calendar
              [options]="calendarOptions"
              #calendar>
            </full-calendar>
          </mat-card-content>
        </mat-card>

        <mat-card class="sidebar-card">
          <mat-card-header>
            <mat-card-title>Événements à venir</mat-card-title>
          </mat-card-header>
          <mat-card-content>
            <div class="upcoming-events">
              <div class="event-item" *ngFor="let event of upcomingEvents">
                <div class="event-date">
                  {{ formatEventDate(event.start) }}
                </div>
                <div class="event-details">
                  <div class="event-title">{{ event.title }}</div>
                  <div class="event-type" [ngClass]="'type-' + event.type">
                    {{ getEventTypeLabel(event.type) }}
                  </div>
                  <div class="event-location" *ngIf="event.location">
                    <mat-icon>location_on</mat-icon>
                    {{ event.location }}
                  </div>
                </div>
                <div class="event-actions">
                  <button mat-icon-button (click)="editEvent(event)">
                    <mat-icon>edit</mat-icon>
                  </button>
                  <button mat-icon-button color="warn" (click)="deleteEvent(event)">
                    <mat-icon>delete</mat-icon>
                  </button>
                </div>
              </div>
              
              <div class="no-events" *ngIf="upcomingEvents.length === 0">
                <mat-icon>event_note</mat-icon>
                <p>Aucun événement à venir</p>
              </div>
            </div>
          </mat-card-content>
        </mat-card>
      </div>

      <!-- Event Legend -->
      <mat-card class="legend-card">
        <mat-card-header>
          <mat-card-title>Légende</mat-card-title>
        </mat-card-header>
        <mat-card-content>
          <div class="legend-items">
            <div class="legend-item">
              <div class="legend-color exam"></div>
              <span>Examens</span>
            </div>
            <div class="legend-item">
              <div class="legend-color vacation"></div>
              <span>Vacances</span>
            </div>
            <div class="legend-item">
              <div class="legend-color meeting"></div>
              <span>Réunions</span>
            </div>
            <div class="legend-item">
              <div class="legend-color personal"></div>
              <span>Personnel</span>
            </div>
            <div class="legend-item">
              <div class="legend-color general"></div>
              <span>Général</span>
            </div>
          </div>
        </mat-card-content>
      </mat-card>
    </div>
  `,
  styles: [`
    .calendar-container {
      padding: 20px;
      max-width: 1400px;
      margin: 0 auto;
    }

    .calendar-header {
      margin-bottom: 20px;
    }

    .calendar-header mat-card-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      width: 100%;
    }

    .calendar-header mat-card-title {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .header-actions {
      display: flex;
      align-items: center;
      gap: 15px;
    }

    .filter-field {
      width: 200px;
    }

    .calendar-content {
      display: grid;
      grid-template-columns: 1fr 300px;
      gap: 20px;
      margin-bottom: 20px;
    }

    .calendar-card {
      min-height: 600px;
    }

    .sidebar-card {
      height: fit-content;
    }

    .upcoming-events {
      max-height: 500px;
      overflow-y: auto;
    }

    .event-item {
      display: flex;
      align-items: center;
      gap: 15px;
      padding: 15px;
      border: 1px solid #e0e0e0;
      border-radius: 8px;
      margin-bottom: 10px;
      transition: box-shadow 0.2s;
    }

    .event-item:hover {
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .event-date {
      font-weight: 500;
      color: #666;
      font-size: 0.9rem;
      min-width: 80px;
    }

    .event-details {
      flex: 1;
    }

    .event-title {
      font-weight: 500;
      margin-bottom: 5px;
    }

    .event-type {
      font-size: 0.8rem;
      padding: 2px 8px;
      border-radius: 12px;
      display: inline-block;
      margin-bottom: 5px;
    }

    .event-type.type-exam {
      background: #ffebee;
      color: #c62828;
    }

    .event-type.type-vacation {
      background: #e8f5e8;
      color: #2e7d32;
    }

    .event-type.type-meeting {
      background: #e3f2fd;
      color: #1565c0;
    }

    .event-type.type-personal {
      background: #fce4ec;
      color: #ad1457;
    }

    .event-type.type-general {
      background: #f3e5f5;
      color: #7b1fa2;
    }

    .event-location {
      display: flex;
      align-items: center;
      gap: 5px;
      font-size: 0.8rem;
      color: #666;
    }

    .event-location mat-icon {
      font-size: 16px;
      width: 16px;
      height: 16px;
    }

    .event-actions {
      display: flex;
      gap: 5px;
    }

    .no-events {
      text-align: center;
      padding: 40px 20px;
      color: #999;
    }

    .no-events mat-icon {
      font-size: 48px;
      width: 48px;
      height: 48px;
      margin-bottom: 15px;
    }

    .legend-card {
      margin-top: 20px;
    }

    .legend-items {
      display: flex;
      gap: 20px;
      flex-wrap: wrap;
    }

    .legend-item {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .legend-color {
      width: 16px;
      height: 16px;
      border-radius: 50%;
    }

    .legend-color.exam {
      background: #f44336;
    }

    .legend-color.vacation {
      background: #4caf50;
    }

    .legend-color.meeting {
      background: #2196f3;
    }

    .legend-color.personal {
      background: #e91e63;
    }

    .legend-color.general {
      background: #9c27b0;
    }

    /* FullCalendar custom styles */
    :host ::ng-deep .fc-event {
      border: none !important;
      cursor: pointer;
    }

    :host ::ng-deep .fc-event.event-exam {
      background-color: #f44336 !important;
    }

    :host ::ng-deep .fc-event.event-vacation {
      background-color: #4caf50 !important;
    }

    :host ::ng-deep .fc-event.event-meeting {
      background-color: #2196f3 !important;
    }

    :host ::ng-deep .fc-event.event-personal {
      background-color: #e91e63 !important;
    }

    :host ::ng-deep .fc-event.event-general {
      background-color: #9c27b0 !important;
    }

    @media (max-width: 768px) {
      .calendar-content {
        grid-template-columns: 1fr;
      }
      
      .header-actions {
        flex-direction: column;
        align-items: flex-end;
        gap: 10px;
      }
    }
  `]
})
export class CalendarComponent implements OnInit {
  calendarOptions: CalendarOptions = {
    initialView: 'dayGridMonth',
    plugins: [interactionPlugin, dayGridPlugin, timeGridPlugin],
    headerToolbar: {
      left: 'prev,next today',
      center: 'title',
      right: 'dayGridMonth,timeGridWeek,timeGridDay'
    },
    editable: true,
    selectable: true,
    selectMirror: true,
    dayMaxEvents: true,
    select: this.handleDateSelect.bind(this),
    eventClick: this.handleEventClick.bind(this),
    eventsSet: this.handleEvents.bind(this),
    height: 'auto',
    locale: 'fr',
    buttonText: {
      today: 'Aujourd\'hui',
      month: 'Mois',
      week: 'Semaine',
      day: 'Jour'
    }
  };

  currentEvents: EventApi[] = [];
  upcomingEvents: CalendarEvent[] = [];
  selectedEventType = '';
  currentUserId = 1; // This should come from auth service

  constructor(
    private apiService: ApiService,
    private dialog: MatDialog
  ) {}

  ngOnInit() {
    this.loadCalendarEvents();
    this.loadUpcomingEvents();
  }

  loadCalendarEvents() {
    const filters: any = {};
    
    if (this.selectedEventType) {
      filters.type = this.selectedEventType;
    }
    
    filters.user_id = this.currentUserId;

    this.apiService.getCalendarEvents(filters).subscribe({
      next: (events) => {
        this.calendarOptions = {
          ...this.calendarOptions,
          events: events.map(event => ({
            id: event.id.toString(),
            title: event.title,
            start: event.start,
            end: event.end,
            allDay: event.allDay,
            className: `event-${event.type}`,
            extendedProps: {
              description: event.description,
              type: event.type,
              priority: event.priority,
              location: event.location,
              creator: event.creator,
              originalEvent: event
            }
          }))
        };
      },
      error: (error) => {
        console.error('Error loading calendar events:', error);
      }
    });
  }

  loadUpcomingEvents() {
    this.apiService.getUpcomingExams(this.currentUserId, 5).subscribe({
      next: (exams) => {
        this.upcomingEvents = exams;
      },
      error: (error) => {
        console.error('Error loading upcoming events:', error);
      }
    });
  }

  onFilterChange() {
    this.loadCalendarEvents();
  }

  handleDateSelect(selectInfo: DateSelectArg) {
    this.openCreateEventDialog(selectInfo.start, selectInfo.end);
  }

  handleEventClick(clickInfo: EventClickArg) {
    const event = clickInfo.event.extendedProps['originalEvent'];
    this.openEventDetailDialog(event);
  }

  handleEvents(events: EventApi[]) {
    this.currentEvents = events;
  }

  openCreateEventDialog(start?: Date, end?: Date) {
    // This would open a dialog for creating new events
    // Implementation would include a form with all event fields
    console.log('Open create event dialog', { start, end });
  }

  openEventDetailDialog(event: CalendarEvent) {
    // This would open a dialog showing event details
    console.log('Open event detail dialog', event);
  }

  editEvent(event: CalendarEvent) {
    // Open edit dialog
    console.log('Edit event', event);
  }

  deleteEvent(event: CalendarEvent) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cet événement ?')) {
      this.apiService.deleteCalendarEvent(event.id).subscribe({
        next: () => {
          this.loadCalendarEvents();
          this.loadUpcomingEvents();
        },
        error: (error) => {
          console.error('Error deleting event:', error);
        }
      });
    }
  }

  formatEventDate(dateString: string): string {
    const date = new Date(dateString);
    return date.toLocaleDateString('fr-FR', {
      day: '2-digit',
      month: 'short',
      hour: '2-digit',
      minute: '2-digit'
    });
  }

  getEventTypeLabel(type: string): string {
    const labels: { [key: string]: string } = {
      exam: 'Examen',
      vacation: 'Vacances',
      meeting: 'Réunion',
      personal: 'Personnel',
      general: 'Général'
    };
    return labels[type] || type;
  }
}