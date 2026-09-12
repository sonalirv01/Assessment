import { Component } from '@angular/core';

import { Router, RouterLink, RouterLinkActive, RouterOutlet } from '@angular/router';
import { AuthService } from '../../core/services/auth.service';

@Component({
  selector: 'app-admin-layout',
  standalone: true,
  imports: [RouterLink, RouterLinkActive, RouterOutlet],
  templateUrl: './admin-layout.component.html',
})
export class AdminLayoutComponent {
  constructor(
    private authService: AuthService,
    private router: Router
  ) {}

  get currentUser() {
    return this.authService.currentUser();
  }

  logout(): void {
    this.authService.logout().subscribe({
      next: () => this.router.navigate(['/admin/login']),
      error: () => this.router.navigate(['/admin/login']),
    });
  }
}
