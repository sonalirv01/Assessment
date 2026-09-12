import { Component, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { MetalTypesService } from '../../core/services/metal-types.service';
import { MetalType } from '../../core/models/metal-type.model';

@Component({
  selector: 'app-metal-rates-page',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './metal-rates-page.component.html',
})
export class MetalRatesPageComponent implements OnInit {
  metalTypes = signal<MetalType[]>([]);
  isLoading = signal(false);
  errorMessage = signal('');

  editingKey = signal<string | null>(null);
  editingPricePerGram: number | null = null;
  isSaving = signal(false);

  constructor(private metalTypesService: MetalTypesService) {}

  ngOnInit(): void {
    this.loadMetalTypes();
  }

  loadMetalTypes(): void {
    this.isLoading.set(true);
    this.metalTypesService.getMetalTypes().subscribe({
      next: (metalTypes) => {
        this.metalTypes.set(metalTypes);
        this.isLoading.set(false);
      },
      error: () => {
        this.errorMessage.set('Could not load metal rates.');
        this.isLoading.set(false);
      },
    });
  }

  startEditing(metalType: MetalType): void {
    this.editingKey.set(metalType.key);
    this.editingPricePerGram = metalType.price_per_gram;
  }

  cancelEditing(): void {
    this.editingKey.set(null);
    this.editingPricePerGram = null;
  }

  saveRate(key: string): void {
    if (this.editingPricePerGram === null || this.editingPricePerGram < 0) {
      return;
    }
    // note: doesn't clear errorMessage here, so a stale error from a previous
    // failed save can stick around on screen even after this one succeeds
    this.isSaving.set(true);
    this.metalTypesService.updateMetalTypeRate(key, this.editingPricePerGram).subscribe({
      next: () => {
        this.isSaving.set(false);
        this.cancelEditing();
        this.loadMetalTypes();
      },
      error: (error) => {
        this.isSaving.set(false);
        this.errorMessage.set(error?.error?.message ?? 'Could not update the rate.');
      },
    });
  }
}
