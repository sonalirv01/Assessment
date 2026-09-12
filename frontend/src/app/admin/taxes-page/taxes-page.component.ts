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
    if (!this.newTaxName.trim() || this.newTaxPercentage === null) {
      return;
    }
    const payload: TaxPayload = {
      name: this.newTaxName.trim(),
      percentage: this.newTaxPercentage,
      is_active: this.newTaxIsActive,
    };
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
        this.errorMessage.set(error?.error?.message ?? 'Could not create tax.');
      },
    });
  }

  startEditing(tax: Tax): void {
    this.editingTaxId.set(tax.id);
    this.editingTaxName = tax.name;
    this.editingTaxPercentage = tax.percentage;
    this.editingTaxIsActive = tax.is_active;
  }

  cancelEditing(): void {
    this.editingTaxId.set(null);
  }

  saveEditing(taxId: number): void {
    if (!this.editingTaxName.trim() || this.editingTaxPercentage === null) {
      return;
    }
    const payload: TaxPayload = {
      name: this.editingTaxName.trim(),
      percentage: this.editingTaxPercentage,
      is_active: this.editingTaxIsActive,
    };
    this.taxesService.updateTax(taxId, payload).subscribe({
      next: () => {
        this.cancelEditing();
        this.loadTaxes();
      },
      error: (error) => {
        this.errorMessage.set(error?.error?.message ?? 'Could not update tax.');
      },
    });
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
