import { Component, OnInit, signal } from '@angular/core';

import { FormsModule } from '@angular/forms';
import { TaxesService, TaxPayload } from '../../core/services/taxes.service';
import { Tax } from '../../core/models/tax.model';
import { AuthService } from '../../core/services/auth.service';

@Component({
  selector: 'app-taxes-page',
  standalone: true,
  imports: [FormsModule],
  templateUrl: './taxes-page.component.html',
})
export class TaxesPageComponent implements OnInit {
  taxes = signal<Tax[]>([]);
  isLoading = signal(false);
  errorMessage = signal('');

  newTaxName = '';
  newTaxPercentage: number | null = null;
  newTaxIsActive = true;
  isCreating = signal(false);
  fieldErrors = signal<Record<string, string[]>>({});

  editingTaxId = signal<number | null>(null);
  editingTaxName = '';
  editingTaxPercentage: number | null = null;
  editingTaxIsActive = true;

  constructor(
    private taxesService: TaxesService,
    private authService: AuthService
  ) {}

  get canDelete(): boolean {
    return this.authService.isAdmin();
  }

  fieldError(fieldName: string): string | null {
    const errors = this.fieldErrors()[fieldName];
    return errors && errors.length > 0 ? errors[0] : null;
  }

  ngOnInit(): void {
    this.loadTaxes();
  }

  loadTaxes(): void {
    this.isLoading.set(true);
    this.taxesService.getTaxes().subscribe({
      next: (taxes) => {
        this.taxes.set(taxes);
        this.isLoading.set(false);
      },
      error: () => {
        this.errorMessage.set('Could not load taxes.');
        this.isLoading.set(false);
      },
    });
  }

  createTax(): void {
    const name = this.newTaxName.trim();
    this.errorMessage.set('');
    const errors = this.validateTaxInput(name, this.newTaxPercentage);
    if (errors) {
      this.fieldErrors.set(errors);
      return;
    }
    const payload: TaxPayload = {
      name,
      percentage: this.newTaxPercentage!,
      is_active: this.newTaxIsActive,
    };
    this.fieldErrors.set({});
    this.isCreating.set(true);
    this.taxesService.createTax(payload).subscribe({
      next: () => {
        this.newTaxName = '';
        this.newTaxPercentage = null;
        this.newTaxIsActive = true;
        this.isCreating.set(false);
        this.loadTaxes();
      },
      error: (error) => {
        this.isCreating.set(false);
        if (error.status === 422) {
          this.fieldErrors.set(error.error?.errors ?? {});
        } else {
          this.errorMessage.set(error?.error?.message ?? 'Could not create tax.');
        }
      },
    });
  }

  startEditing(tax: Tax): void {
    this.editingTaxId.set(tax.id);
    this.editingTaxName = tax.name;
    // tax.percentage is the API's exact decimal string; converting it to a
    // number here is safe because it only feeds a raw edit input, not any
    // further arithmetic (see core/utils/decimal.util.ts).
    this.editingTaxPercentage = Number(tax.percentage);
    this.editingTaxIsActive = tax.is_active;
    this.fieldErrors.set({});
  }

  cancelEditing(): void {
    this.editingTaxId.set(null);
    this.fieldErrors.set({});
  }

  saveEditing(taxId: number): void {
    const name = this.editingTaxName.trim();
    this.errorMessage.set('');
    const errors = this.validateTaxInput(name, this.editingTaxPercentage);
    if (errors) {
      this.fieldErrors.set(errors);
      return;
    }
    const payload: TaxPayload = {
      name,
      percentage: this.editingTaxPercentage!,
      is_active: this.editingTaxIsActive,
    };
    this.fieldErrors.set({});
    this.taxesService.updateTax(taxId, payload).subscribe({
      next: () => {
        this.cancelEditing();
        this.loadTaxes();
      },
      error: (error) => {
        if (error.status === 422) {
          this.fieldErrors.set(error.error?.errors ?? {});
        } else {
          this.errorMessage.set(error?.error?.message ?? 'Could not update tax.');
        }
      },
    });
  }

  // Mirrors backend/app/Http/Requests/Store|UpdateTaxRequest.php — surfaces
  // the same required/range checks client-side instead of a silent no-op.
  private validateTaxInput(name: string, percentage: number | null): Record<string, string[]> | null {
    const errors: Record<string, string[]> = {};
    if (!name) {
      errors['name'] = ['Name is required.'];
    }
    if (percentage === null) {
      errors['percentage'] = ['Percentage is required.'];
    } else if (percentage < 0 || percentage > 100) {
      errors['percentage'] = ['Percentage must be between 0 and 100.'];
    }
    return Object.keys(errors).length > 0 ? errors : null;
  }

  deleteTax(taxId: number): void {
    this.taxesService.deleteTax(taxId).subscribe({
      next: () => this.loadTaxes(),
      error: (error) => {
        this.errorMessage.set(error?.error?.message ?? 'Could not delete tax.');
      },
    });
  }
}
