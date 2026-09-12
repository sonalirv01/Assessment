import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { Category } from '../models/category.model';

@Injectable({ providedIn: 'root' })
export class CategoriesService {
  constructor(private http: HttpClient) {}

  getCategories(): Observable<Category[]> {
    return this.http.get<Category[]>(`${environment.apiUrl}/categories`);
  }

  createCategory(name: string): Observable<Category> {
    return this.http.post<Category>(`${environment.apiUrl}/categories`, { name });
  }

  updateCategory(id: number, name: string): Observable<Category> {
    return this.http.put<Category>(`${environment.apiUrl}/categories/${id}`, { name });
  }

  deleteCategory(id: number): Observable<{ message: string }> {
    return this.http.delete<{ message: string }>(`${environment.apiUrl}/categories/${id}`);
  }
}
