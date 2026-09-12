import { Injectable, signal } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, tap } from 'rxjs';
import { environment } from '../../../environments/environment';
import { AuthUser, LoginResponse } from '../models/user.model';

const TOKEN_STORAGE_KEY = 'jewellery_auth_token';
const USER_STORAGE_KEY = 'jewellery_auth_user';

@Injectable({ providedIn: 'root' })
export class AuthService {
  currentUser = signal<AuthUser | null>(this.readStoredUser());

  constructor(private http: HttpClient) {}

  login(email: string, password: string): Observable<LoginResponse> {
    return this.http
      .post<LoginResponse>(`${environment.apiUrl}/auth/login`, { email, password })
      .pipe(tap((response) => this.storeSession(response.token, response.user)));
  }

  logout(): Observable<{ message: string }> {
    return this.http
      .post<{ message: string }>(`${environment.apiUrl}/auth/logout`, {})
      .pipe(tap(() => this.clearSession()));
  }

  fetchCurrentUser(): Observable<AuthUser> {
    return this.http.get<AuthUser>(`${environment.apiUrl}/auth/me`);
  }

  storeSession(token: string, user: AuthUser): void {
    localStorage.setItem(TOKEN_STORAGE_KEY, token);
    localStorage.setItem(USER_STORAGE_KEY, JSON.stringify(user));
    this.currentUser.set(user);
  }

  clearSession(): void {
    localStorage.removeItem(TOKEN_STORAGE_KEY);
    localStorage.removeItem(USER_STORAGE_KEY);
    this.currentUser.set(null);
  }

  getToken(): string | null {
    return localStorage.getItem(TOKEN_STORAGE_KEY);
  }

  canAccessAdminPanel(): boolean {
    const storedUser = this.readStoredUser();
    return !!this.getToken() && (storedUser?.role === 'admin' || storedUser?.role === 'manager');
  }

  isAdmin(): boolean {
    return this.currentUser()?.role === 'admin';
  }

  isManager(): boolean {
    return this.currentUser()?.role === 'manager';
  }

  private readStoredUser(): AuthUser | null {
    const rawUser = localStorage.getItem(USER_STORAGE_KEY);
    if (!rawUser) {
      return null;
    }
    try {
      return JSON.parse(rawUser) as AuthUser;
    } catch {
      return null;
    }
  }
}
