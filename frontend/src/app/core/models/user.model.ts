export type UserRole = 'admin' | 'manager' | 'customer';

export interface AuthUser {
  id: number;
  name: string;
  email: string;
  role: UserRole;
}

export interface LoginResponse {
  token: string;
  token_type: string;
  user: AuthUser;
}
