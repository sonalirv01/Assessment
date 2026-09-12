import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { MetalType } from '../models/metal-type.model';

@Injectable({ providedIn: 'root' })
export class MetalTypesService {
  constructor(private http: HttpClient) {}

  getMetalTypes(): Observable<MetalType[]> {
    return this.http.get<MetalType[]>(`${environment.apiUrl}/metal-types`);
  }

  updateMetalTypeRate(key: string, pricePerGram: number): Observable<MetalType> {
    return this.http.put<MetalType>(`${environment.apiUrl}/metal-types/${key}`, {
      price_per_gram: pricePerGram,
    });
  }
}
