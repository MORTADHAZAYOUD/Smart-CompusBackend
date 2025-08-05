import { Injectable } from '@angular/core';
import { HttpClient, HttpErrorResponse } from '@angular/common/http';
import { Observable, BehaviorSubject, throwError } from 'rxjs';
import { map, catchError, tap } from 'rxjs/operators';
import { Router } from '@angular/router';
import { 
  User, 
  AuthResponse, 
  LoginRequest, 
  RegisterRequest, 
  ApiResponse,
  UserRole 
} from '../models/user.model';

@Injectable({
  providedIn: 'root'
})
export class AuthService {
  private apiUrl = 'http://localhost:8000/api';
  private currentUserSubject = new BehaviorSubject<User | null>(null);
  public currentUser$ = this.currentUserSubject.asObservable();
  private tokenKey = 'smartcampus_token';

  constructor(
    private http: HttpClient,
    private router: Router
  ) {
    // Vérifier si l'utilisateur est déjà connecté au démarrage
    this.checkCurrentUser();
  }

  /**
   * Connexion de l'utilisateur
   */
  login(credentials: LoginRequest): Observable<AuthResponse> {
    return this.http.post<AuthResponse>(`${this.apiUrl}/login_check`, credentials)
      .pipe(
        tap(response => {
          if (response.token) {
            this.setToken(response.token);
            this.currentUserSubject.next(response.user);
          }
        }),
        catchError(this.handleError)
      );
  }

  /**
   * Inscription d'un nouvel utilisateur
   */
  register(userData: RegisterRequest): Observable<ApiResponse<User>> {
    return this.http.post<ApiResponse<User>>(`${this.apiUrl}/register`, userData)
      .pipe(
        catchError(this.handleError)
      );
  }

  /**
   * Déconnexion
   */
  logout(): void {
    localStorage.removeItem(this.tokenKey);
    this.currentUserSubject.next(null);
    this.router.navigate(['/login']);
  }

  /**
   * Récupérer le profil de l'utilisateur connecté
   */
  getProfile(): Observable<{ user: User }> {
    return this.http.get<{ user: User }>(`${this.apiUrl}/profile`)
      .pipe(
        tap(response => {
          this.currentUserSubject.next(response.user);
        }),
        catchError(this.handleError)
      );
  }

  /**
   * Mettre à jour le profil
   */
  updateProfile(userData: Partial<User>): Observable<ApiResponse<User>> {
    return this.http.put<ApiResponse<User>>(`${this.apiUrl}/profile`, userData)
      .pipe(
        tap(response => {
          if (response.data) {
            this.currentUserSubject.next(response.data);
          }
        }),
        catchError(this.handleError)
      );
  }

  /**
   * Vérifier si l'utilisateur est connecté
   */
  isAuthenticated(): boolean {
    return !!this.getToken() && !!this.currentUserSubject.value;
  }

  /**
   * Obtenir l'utilisateur actuel
   */
  getCurrentUser(): User | null {
    return this.currentUserSubject.value;
  }

  /**
   * Obtenir le token JWT
   */
  getToken(): string | null {
    return localStorage.getItem(this.tokenKey);
  }

  /**
   * Définir le token JWT
   */
  private setToken(token: string): void {
    localStorage.setItem(this.tokenKey, token);
  }

  /**
   * Vérifier l'utilisateur actuel au démarrage
   */
  private checkCurrentUser(): void {
    const token = this.getToken();
    if (token) {
      this.getProfile().subscribe({
        next: (response) => {
          this.currentUserSubject.next(response.user);
        },
        error: () => {
          this.logout();
        }
      });
    }
  }

  /**
   * Vérifier si l'utilisateur a un rôle spécifique
   */
  hasRole(role: UserRole): boolean {
    const user = this.getCurrentUser();
    return user ? user.roles.includes(role) : false;
  }

  /**
   * Vérifier si l'utilisateur a au moins un des rôles
   */
  hasAnyRole(roles: UserRole[]): boolean {
    const user = this.getCurrentUser();
    if (!user) return false;
    return roles.some(role => user.roles.includes(role));
  }

  /**
   * Vérifier si l'utilisateur est administrateur
   */
  isAdmin(): boolean {
    return this.hasRole(UserRole.ADMIN);
  }

  /**
   * Vérifier si l'utilisateur est enseignant
   */
  isTeacher(): boolean {
    return this.hasRole(UserRole.TEACHER);
  }

  /**
   * Vérifier si l'utilisateur est étudiant
   */
  isStudent(): boolean {
    return this.hasRole(UserRole.STUDENT);
  }

  /**
   * Vérifier si l'utilisateur est parent
   */
  isParent(): boolean {
    return this.hasRole(UserRole.PARENT);
  }

  /**
   * Obtenir le nom complet de l'utilisateur
   */
  getUserFullName(): string {
    const user = this.getCurrentUser();
    return user ? `${user.prenom} ${user.nom}` : '';
  }

  /**
   * Obtenir les rôles disponibles
   */
  getAvailableRoles(): Observable<{ roles: { [key: string]: string } }> {
    return this.http.get<{ roles: { [key: string]: string } }>(`${this.apiUrl}/roles`);
  }

  /**
   * Gestion des erreurs HTTP
   */
  private handleError(error: HttpErrorResponse): Observable<never> {
    let errorMessage = 'Une erreur inattendue s\'est produite';
    
    if (error.error instanceof ErrorEvent) {
      // Erreur côté client
      errorMessage = `Erreur: ${error.error.message}`;
    } else {
      // Erreur côté serveur
      if (error.error && error.error.error) {
        errorMessage = error.error.error;
      } else if (error.error && error.error.message) {
        errorMessage = error.error.message;
      } else {
        errorMessage = `Erreur ${error.status}: ${error.message}`;
      }
    }
    
    return throwError(() => new Error(errorMessage));
  }
}