import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';

export interface User {
  id: number;
  email: string;
  firstname: string;
  lastname: string;
  roles: string[];
}

export interface Student extends User {
  numStudent: string;
  dateNaissance: string;
  classe?: any;
  parent?: any;
}

export interface Timetable {
  id: number;
  title: string;
  description?: string;
  startTime: string;
  endTime: string;
  dayOfWeek: string;
  type: string;
  user: User;
  classe?: any;
  matiere?: any;
  location?: string;
  isRecurring: boolean;
  recurringPattern?: string;
}

export interface CalendarEvent {
  id: number;
  title: string;
  start: string;
  end?: string;
  allDay: boolean;
  description: string;
  type: string;
  priority: string;
  location?: string;
  color?: string;
  isPublic: boolean;
  creator?: User;
  class?: any;
  subject?: any;
}

export interface Notification {
  id: number;
  titre: string;
  contenu: string;
  priorite: string;
  dateCreation: string;
  lu: boolean;
  vue: boolean;
}

@Injectable({
  providedIn: 'root'
})
export class ApiService {
  private baseUrl = 'http://localhost:8000/api';

  constructor(private http: HttpClient) {}

  // User Management
  getAdminDashboard(): Observable<any> {
    return this.http.get(`${this.baseUrl}/users/admin/dashboard`);
  }

  getUsers(role?: string): Observable<User[]> {
    let params = new HttpParams();
    if (role) {
      params = params.set('role', role);
    }
    return this.http.get<User[]>(`${this.baseUrl}/users`, { params });
  }

  getUser(id: number): Observable<User> {
    return this.http.get<User>(`${this.baseUrl}/users/${id}`);
  }

  // Timetable Management
  getTimetables(filters?: any): Observable<Timetable[]> {
    let params = new HttpParams();
    if (filters) {
      Object.keys(filters).forEach(key => {
        if (filters[key]) {
          params = params.set(key, filters[key]);
        }
      });
    }
    return this.http.get<Timetable[]>(`${this.baseUrl}/timetables`, { params });
  }

  createTimetable(timetable: any): Observable<Timetable> {
    return this.http.post<Timetable>(`${this.baseUrl}/timetables`, timetable);
  }

  updateTimetable(id: number, timetable: any): Observable<Timetable> {
    return this.http.put<Timetable>(`${this.baseUrl}/timetables/${id}`, timetable);
  }

  deleteTimetable(id: number): Observable<any> {
    return this.http.delete(`${this.baseUrl}/timetables/${id}`);
  }

  getUserWeeklyTimetable(userId: number, weekStart?: string): Observable<Timetable[]> {
    let params = new HttpParams();
    if (weekStart) {
      params = params.set('week_start', weekStart);
    }
    return this.http.get<Timetable[]>(`${this.baseUrl}/timetables/user/${userId}/week`, { params });
  }

  bulkCreateTimetables(data: any): Observable<any> {
    return this.http.post(`${this.baseUrl}/timetables/admin/bulk`, data);
  }

  // Calendar Management
  getCalendarEvents(filters?: any): Observable<CalendarEvent[]> {
    let params = new HttpParams();
    if (filters) {
      Object.keys(filters).forEach(key => {
        if (filters[key]) {
          params = params.set(key, filters[key]);
        }
      });
    }
    return this.http.get<CalendarEvent[]>(`${this.baseUrl}/calendar`, { params });
  }

  createCalendarEvent(event: any): Observable<CalendarEvent> {
    return this.http.post<CalendarEvent>(`${this.baseUrl}/calendar/events`, event);
  }

  updateCalendarEvent(id: number, event: any): Observable<CalendarEvent> {
    return this.http.put<CalendarEvent>(`${this.baseUrl}/calendar/events/${id}`, event);
  }

  deleteCalendarEvent(id: number): Observable<any> {
    return this.http.delete(`${this.baseUrl}/calendar/events/${id}`);
  }

  getUserMonthlyCalendar(userId: number, year: number, month: number): Observable<CalendarEvent[]> {
    return this.http.get<CalendarEvent[]>(`${this.baseUrl}/calendar/user/${userId}/month/${year}/${month}`);
  }

  getUpcomingExams(userId: number, limit?: number): Observable<CalendarEvent[]> {
    let params = new HttpParams();
    if (limit) {
      params = params.set('limit', limit.toString());
    }
    return this.http.get<CalendarEvent[]>(`${this.baseUrl}/calendar/exams/upcoming/${userId}`, { params });
  }

  getVacations(filters?: any): Observable<CalendarEvent[]> {
    let params = new HttpParams();
    if (filters) {
      Object.keys(filters).forEach(key => {
        if (filters[key]) {
          params = params.set(key, filters[key]);
        }
      });
    }
    return this.http.get<CalendarEvent[]>(`${this.baseUrl}/calendar/vacations`, { params });
  }

  searchCalendarEvents(query: string, userId?: number): Observable<CalendarEvent[]> {
    let params = new HttpParams().set('q', query);
    if (userId) {
      params = params.set('user_id', userId.toString());
    }
    return this.http.get<CalendarEvent[]>(`${this.baseUrl}/calendar/search`, { params });
  }

  // Alert Management
  sendAlertToStudents(data: any): Observable<any> {
    return this.http.post(`${this.baseUrl}/alerts/send/students`, data);
  }

  sendAlertToParents(data: any): Observable<any> {
    return this.http.post(`${this.baseUrl}/alerts/send/parents`, data);
  }

  sendAlertToClass(classeId: number, data: any): Observable<any> {
    return this.http.post(`${this.baseUrl}/alerts/send/class/${classeId}`, data);
  }

  sendExamAlert(data: any): Observable<any> {
    return this.http.post(`${this.baseUrl}/alerts/send/exam`, data);
  }

  sendVacationAlert(data: any): Observable<any> {
    return this.http.post(`${this.baseUrl}/alerts/send/vacation`, data);
  }

  sendEmergencyAlert(data: any): Observable<any> {
    return this.http.post(`${this.baseUrl}/alerts/send/emergency`, data);
  }

  getUnreadAlertsCount(userId: number): Observable<any> {
    return this.http.get(`${this.baseUrl}/alerts/user/${userId}/unread-count`);
  }

  getRecentAlerts(userId: number, limit?: number): Observable<Notification[]> {
    let params = new HttpParams();
    if (limit) {
      params = params.set('limit', limit.toString());
    }
    return this.http.get<Notification[]>(`${this.baseUrl}/alerts/user/${userId}/recent`, { params });
  }

  markAlertAsRead(notificationId: number, userId: number): Observable<any> {
    return this.http.post(`${this.baseUrl}/alerts/mark-read/${notificationId}`, { userId });
  }

  markMultipleAlertsAsRead(notificationIds: number[], userId: number): Observable<any> {
    return this.http.post(`${this.baseUrl}/alerts/mark-multiple-read`, { notificationIds, userId });
  }

  // Class Management
  getClasses(): Observable<any[]> {
    return this.http.get<any[]>(`${this.baseUrl}/classes`);
  }

  getClass(id: number): Observable<any> {
    return this.http.get(`${this.baseUrl}/classes/${id}`);
  }

  // Subject Management
  getSubjects(): Observable<any[]> {
    return this.http.get<any[]>(`${this.baseUrl}/matieres`);
  }
}