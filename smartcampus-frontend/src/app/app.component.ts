import { Component, OnInit } from '@angular/core';
import { AuthService } from './services/auth.service';
import { Router } from '@angular/router';

@Component({
  selector: 'app-root',
  template: `
    <div class="app-container">
      <router-outlet></router-outlet>
    </div>
  `,
  styles: [`
    .app-container {
      height: 100vh;
      width: 100vw;
    }
  `]
})
export class AppComponent implements OnInit {
  title = 'SmartCampus - ERP Scolaire';

  constructor(
    private authService: AuthService,
    private router: Router
  ) {}

  ngOnInit(): void {
    // Vérifier l'état d'authentification au démarrage
    if (this.authService.isAuthenticated()) {
      // Si l'utilisateur est connecté et sur la page de login, rediriger vers le dashboard
      if (this.router.url === '/login' || this.router.url === '/') {
        this.router.navigate(['/dashboard']);
      }
    } else {
      // Si l'utilisateur n'est pas connecté et pas sur la page de login, rediriger vers login
      if (this.router.url !== '/login') {
        this.router.navigate(['/login']);
      }
    }
  }
}