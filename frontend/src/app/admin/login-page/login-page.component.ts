import { Component, inject, signal } from '@angular/core';

import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { AuthService } from '../../core/services/auth.service';

@Component({
  selector: 'app-login-page',
  standalone: true,
  imports: [ReactiveFormsModule],
  templateUrl: './login-page.component.html',
})
export class LoginPageComponent {
  private formBuilder = inject(FormBuilder);

  loginForm = this.formBuilder.group({
    email: ['', [Validators.required, Validators.email]],
    password: ['', [Validators.required]],
  });

  isSubmitting = signal(false);
  errorMessage = signal('');

  constructor(
    private authService: AuthService,
    private router: Router
  ) {}

  submit(): void {
    if (this.loginForm.invalid) {
      this.loginForm.markAllAsTouched();
      return;
    }

    this.isSubmitting.set(true);
    this.errorMessage.set('');

    const { email, password } = this.loginForm.getRawValue();

    this.authService.login(email!, password!).subscribe({
      next: () => {
        this.router.navigate(['/admin/items']);
      },
      error: (error) => {
        this.isSubmitting.set(false);
        this.errorMessage.set(error?.error?.message ?? 'Login failed. Please try again.');
      },
    });
  }
}
