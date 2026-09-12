import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { Tax } from '../models/tax.model';

export interface TaxPayload {
  name: string;
  percentage: number;
  is_active: boolean;
}

@Injectable({ providedIn: 'root' })
export class TaxesService {
  constructor(private http: HttpClient) {}

  getTaxes(): Observable<Tax[]> {
    return this.http.get<Tax[]>(`${environment.apiUrl}/taxes`);
  }

  createTax(payload: TaxPayload): Observable<Tax> {
    return this.http.post<Tax>(`${environment.apiUrl}/taxes`, payload);
  }

  updateTax(id: number, payload: TaxPayload): Observable<Tax> {
    return this.http.put<Tax>(`${environment.apiUrl}/taxes/${id}`, payload);
  }

  deleteTax(id: number): Observable<{ message: string }> {
    return this.http.delete<{ message: string }>(`${environment.apiUrl}/taxes/${id}`);
  }
}
