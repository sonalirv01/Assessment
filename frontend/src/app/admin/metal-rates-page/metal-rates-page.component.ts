import { Component, OnDestroy, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Subscription } from 'rxjs';
import { MetalTypesService } from '../../core/services/metal-types.service';
import { RealtimeService } from '../../core/services/realtime.service';
import { MetalType } from '../../core/models/metal-type.model';

@Component({
  selector: 'app-metal-rates-page',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './metal-rates-page.component.html',
})
export class MetalRatesPageComponent implements OnInit, OnDestroy {
  metalTypes = signal<MetalType[]>([]);
  isLoading = signal(false);
  errorMessage = signal('');

  editingKey = signal<string | null>(null);
  editingPricePerGram: number | null = null;
  isSaving = signal(false);
  fieldErrors = signal<Record<string, string[]>>({});

  private metalPriceSubscription?: Subscription;

  constructor(
    private metalTypesService: MetalTypesService,
    private realtimeService: RealtimeService
  ) {}

  ngOnInit(): void {
    this.loadMetalTypes();

    // Reflects rate changes made from another tab/admin immediately —
    // doesn't touch a row currently being edited, so it can't clobber
    // in-progress input.
    this.metalPriceSubscription = this.realtimeService.onMetalPriceUpdated().subscribe((updated) => {
      if (this.editingKey() === updated.key) {
        return;
      }
      this.metalTypes.update((types) =>
        types.map((type) => (type.key === updated.key ? updated : type))
      );
    });
  }

  ngOnDestroy(): void {
    this.metalPriceSubscription?.unsubscribe();
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
    // metalType.price_per_gram is the API's exact decimal string; converting
    // to a number here only feeds a raw edit input, not further arithmetic
    // (see core/utils/decimal.util.ts).
    this.editingPricePerGram = Number(metalType.price_per_gram);
    this.fieldErrors.set({});
  }

  cancelEditing(): void {
    this.editingKey.set(null);
    this.editingPricePerGram = null;
    this.fieldErrors.set({});
  }

  fieldError(fieldName: string): string | null {
    const errors = this.fieldErrors()[fieldName];
    return errors && errors.length > 0 ? errors[0] : null;
  }

  saveRate(key: string): void {
    this.errorMessage.set('');
    // Mirrors backend/app/Http/Requests/UpdateMetalPriceRequest.php's
    // min:0.01/max:1000000 rule, surfaced here instead of a silent no-op.
    if (this.editingPricePerGram === null) {
      this.fieldErrors.set({ price_per_gram: ['Price per gram is required.'] });
      return;
    }
    if (this.editingPricePerGram < 0.01 || this.editingPricePerGram > 1000000) {
      this.fieldErrors.set({ price_per_gram: ['Price per gram must be between 0.01 and 1,000,000.'] });
      return;
    }
    this.fieldErrors.set({});
    this.isSaving.set(true);
    this.metalTypesService.updateMetalTypeRate(key, this.editingPricePerGram).subscribe({
      next: () => {
        this.isSaving.set(false);
        this.cancelEditing();
        this.loadMetalTypes();
      },
      error: (error) => {
        this.isSaving.set(false);
        if (error.status === 422) {
          this.fieldErrors.set(error.error?.errors ?? {});
        } else {
          this.errorMessage.set(error?.error?.message ?? 'Could not update the rate.');
        }
      },
    });
  }
}
