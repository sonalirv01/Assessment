import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { ItemImage, ItemPayload, ItemsQueryParams, ItemsResponse, JewelleryItem } from '../models/item.model';

@Injectable({ providedIn: 'root' })
export class ItemsService {
  constructor(private http: HttpClient) {}

  getItems(queryParams: ItemsQueryParams): Observable<ItemsResponse> {
    let params = new HttpParams();
    for (const [key, value] of Object.entries(queryParams)) {
      if (value !== undefined && value !== null && value !== '') {
        params = params.set(key, String(value));
      }
    }
    return this.http.get<ItemsResponse>(`${environment.apiUrl}/items`, { params });
  }

  getItem(id: number): Observable<JewelleryItem> {
    return this.http.get<JewelleryItem>(`${environment.apiUrl}/items/${id}`);
  }

  createItem(payload: ItemPayload): Observable<JewelleryItem> {
    return this.http.post<JewelleryItem>(`${environment.apiUrl}/items`, payload);
  }

  updateItem(id: number, payload: ItemPayload): Observable<JewelleryItem> {
    return this.http.put<JewelleryItem>(`${environment.apiUrl}/items/${id}`, payload);
  }

  deleteItem(id: number): Observable<{ message: string }> {
    return this.http.delete<{ message: string }>(`${environment.apiUrl}/items/${id}`);
  }

  uploadImages(itemId: number, files: File[]): Observable<ItemImage[]> {
    const formData = new FormData();
    for (const file of files) {
      formData.append('images[]', file);
    }
    return this.http.post<ItemImage[]>(`${environment.apiUrl}/items/${itemId}/images`, formData);
  }

  deleteImage(itemId: number, imageId: number): Observable<{ message: string }> {
    return this.http.delete<{ message: string }>(`${environment.apiUrl}/items/${itemId}/images/${imageId}`);
  }
}
