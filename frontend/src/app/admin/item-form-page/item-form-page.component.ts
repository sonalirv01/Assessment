import { Component, OnDestroy, OnInit, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, ReactiveFormsModule, ValidationErrors, Validators } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { Subscription, forkJoin } from 'rxjs';
import { ItemsService } from '../../core/services/items.service';
import { CategoriesService } from '../../core/services/categories.service';
import { MetalTypesService } from '../../core/services/metal-types.service';
import { TaxesService } from '../../core/services/taxes.service';
import { RealtimeService } from '../../core/services/realtime.service';
import { Category } from '../../core/models/category.model';
import { MetalType } from '../../core/models/metal-type.model';
import { Tax } from '../../core/models/tax.model';
import { ItemImage, ItemPayload } from '../../core/models/item.model';
import { AuthService } from '../../core/services/auth.service';
import * as Decimal from '../../core/utils/decimal.util';

interface StagedImage {
  file: File;
  previewUrl: string;
}

// Decimal strings, not numbers — see core/utils/decimal.util.ts.
interface PricePreview {
  metalCost: string;
  taxableAmount: string;
  taxTotal: string;
  finalPrice: string;
}

@Component({
  selector: 'app-item-form-page',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule, RouterLink],
  templateUrl: './item-form-page.component.html',
})
export class ItemFormPageComponent implements OnInit, OnDestroy {
  private formBuilder = inject(FormBuilder);

  // Mirrors backend/app/Http/Requests/StoreJewelleryItemRequest.php exactly,
  // so a violation surfaces here before a round-trip to the server.
  itemForm = this.formBuilder.group({
    name: ['', [Validators.required, Validators.maxLength(255)]],
    description: ['', [Validators.required, Validators.maxLength(5000)]],
    category_id: this.formBuilder.control<number | null>(null, [Validators.required]),
    metal_type: ['', [Validators.required]],
    weight_grams: this.formBuilder.control<number | null>(null, [
      Validators.required,
      Validators.min(0.01),
      Validators.max(10000),
    ]),
    making_charges: this.formBuilder.control<number | null>(0, [
      Validators.required,
      Validators.min(0),
      Validators.max(10000000),
    ]),
    shipping_charges: this.formBuilder.control<number | null>(0, [
      Validators.required,
      Validators.min(0),
      Validators.max(10000000),
    ]),
    is_available: [true],
  });

  categories = signal<Category[]>([]);
  metalTypes = signal<MetalType[]>([]);
  taxes = signal<Tax[]>([]);
  selectedTaxIds = signal<number[]>([]);

  // TODO: let admin drag to reorder images once there's time to build it
  existingImages = signal<ItemImage[]>([]);
  stagedImages = signal<StagedImage[]>([]);
  isUploadingImages = signal(false);
  deletingImageId = signal<number | null>(null);
  imageError = signal('');
  private readonly maxImagesPerItem = 10; // mirrors the backend limit

  isEditMode = signal(false);
  editingItemId: number | null = null;
  isSubmitting = signal(false);
  isLoadingItem = signal(false);
  loadError = signal('');
  fieldErrors = signal<Record<string, string[]>>({});

  pricePreview = signal<PricePreview>({ metalCost: '0.00', taxableAmount: '0.00', taxTotal: '0.00', finalPrice: '0.00' });

  private metalPriceSubscription?: Subscription;

  constructor(
    private itemsService: ItemsService,
    private categoriesService: CategoriesService,
    private metalTypesService: MetalTypesService,
    private taxesService: TaxesService,
    private realtimeService: RealtimeService,
    private route: ActivatedRoute,
    private router: Router,
    private authService: AuthService
  ) {}

  get canDelete(): boolean {
    return this.authService.isAdmin();
  }

  ngOnInit(): void {
    this.itemForm.valueChanges.subscribe(() => this.recalculatePricePreview());

    // A rate change from another tab should update this preview immediately
    // — otherwise an admin could save an item priced off a stale rate.
    this.metalPriceSubscription = this.realtimeService.onMetalPriceUpdated().subscribe((updated) => {
      this.metalTypes.update((types) =>
        types.map((type) => (type.key === updated.key ? updated : type))
      );
      this.recalculatePricePreview();
    });

    forkJoin({
      categories: this.categoriesService.getCategories(),
      metalTypes: this.metalTypesService.getMetalTypes(),
      taxes: this.taxesService.getTaxes(),
    }).subscribe(({ categories, metalTypes, taxes }) => {
      this.categories.set(categories);
      this.metalTypes.set(metalTypes);
      this.taxes.set(taxes);
      this.recalculatePricePreview();
    });

    const idParam = this.route.snapshot.paramMap.get('id');
    if (idParam) {
      this.isEditMode.set(true);
      this.editingItemId = Number(idParam);
      this.isLoadingItem.set(true);
      this.itemsService.getItem(this.editingItemId).subscribe({
        next: (item) => {
          this.itemForm.patchValue({
            name: item.name,
            description: item.description,
            category_id: item.category.id,
            metal_type: item.metal_type,
            // item.*_charges/weight_grams are the API's exact decimal
            // strings; converting to a number here only feeds a raw edit
            // input, not further arithmetic (see decimal.util.ts).
            weight_grams: Number(item.weight_grams),
            making_charges: Number(item.making_charges),
            shipping_charges: Number(item.shipping_charges),
            is_available: item.is_available,
          });
          this.existingImages.set(item.images);
          this.selectedTaxIds.set(item.taxes.map((tax) => tax.id));
          this.isLoadingItem.set(false);
          this.recalculatePricePreview();
        },
        error: () => {
          this.loadError.set('Could not load this item.');
          this.isLoadingItem.set(false);
        },
      });
    }
  }

  ngOnDestroy(): void {
    for (const staged of this.stagedImages()) {
      URL.revokeObjectURL(staged.previewUrl);
    }
    this.metalPriceSubscription?.unsubscribe();
  }

  onFilesSelected(event: Event): void {
    const input = event.target as HTMLInputElement;
    const files = input.files ? Array.from(input.files) : [];
    input.value = '';
    if (files.length === 0) {
      return;
    }

    this.imageError.set('');

    if (this.isEditMode()) {
      this.isUploadingImages.set(true);
      // recieve the updated image list back from the server and swap it straight in
      this.itemsService.uploadImages(this.editingItemId!, files).subscribe({
        next: (response) => {
          this.existingImages.set(response);
          this.isUploadingImages.set(false);
        },
        error: (error) => {
          this.imageError.set(error.error?.message ?? 'Could not upload images. Please try again.');
          this.isUploadingImages.set(false);
        },
      });
    } else {
      const new_staged_images = files.map((file) => ({ file, previewUrl: URL.createObjectURL(file) }));
      this.stagedImages.set([...this.stagedImages(), ...new_staged_images]);
    }
  }

  removeStagedImage(index: number): void {
    const staged = this.stagedImages();
    URL.revokeObjectURL(staged[index].previewUrl);
    this.stagedImages.set(staged.filter((_, i) => i !== index));
  }

  removeExistingImage(imageId: number): void {
    this.deletingImageId.set(imageId);
    this.itemsService.deleteImage(this.editingItemId!, imageId).subscribe({
      next: () => {
        this.existingImages.set(this.existingImages().filter((image) => image.id !== imageId));
        this.deletingImageId.set(null);
      },
      error: (error) => {
        this.imageError.set(error.error?.message ?? 'Could not delete image. Please try again.');
        this.deletingImageId.set(null);
      },
    });
  }

  isTaxSelected(taxId: number): boolean {
    return this.selectedTaxIds().includes(taxId);
  }

  toggleTax(taxId: number): void {
    const currentIds = this.selectedTaxIds();
    this.selectedTaxIds.set(
      currentIds.includes(taxId) ? currentIds.filter((id) => id !== taxId) : [...currentIds, taxId]
    );
    this.recalculatePricePreview();
  }

  private static readonly FIELD_LABELS: Record<string, string> = {
    name: 'Name',
    description: 'Description',
    category_id: 'Category',
    metal_type: 'Metal type',
    weight_grams: 'Weight',
    making_charges: 'Making charges',
    shipping_charges: 'Shipping charges',
  };

  // Server-side 422 errors take priority (they're the source of truth); when
  // there are none, fall back to this field's own client-side Validators
  // errors once the user has interacted with it, so "Save" with an empty
  // form shows a message instead of silently doing nothing.
  fieldError(fieldName: string): string | null {
    const serverErrors = this.fieldErrors()[fieldName];
    if (serverErrors && serverErrors.length > 0) {
      return serverErrors[0];
    }

    const control = this.itemForm.get(fieldName);
    if (!control || !control.touched || !control.errors) {
      return null;
    }

    return this.describeValidationError(fieldName, control.errors);
  }

  private describeValidationError(fieldName: string, errors: ValidationErrors): string {
    const label = ItemFormPageComponent.FIELD_LABELS[fieldName] ?? fieldName;

    if (errors['required']) {
      return `${label} is required.`;
    }
    if (errors['maxlength']) {
      return `${label} must be at most ${errors['maxlength'].requiredLength} characters.`;
    }
    if (errors['min']) {
      return `${label} must be at least ${errors['min'].min}.`;
    }
    if (errors['max']) {
      return `${label} must be at most ${errors['max'].max}.`;
    }
    return `${label} is invalid.`;
  }

  submit(): void {
    if (this.itemForm.invalid) {
      this.itemForm.markAllAsTouched();
      return;
    }

    this.isSubmitting.set(true);
    this.fieldErrors.set({});

    const formValues = this.itemForm.getRawValue();
    const payload: ItemPayload = {
      name: formValues.name!,
      description: formValues.description!,
      category_id: formValues.category_id!,
      metal_type: formValues.metal_type!,
      weight_grams: formValues.weight_grams!,
      making_charges: formValues.making_charges!,
      shipping_charges: formValues.shipping_charges!,
      is_available: formValues.is_available!,
      tax_ids: this.selectedTaxIds(),
    };

    const saveRequest = this.isEditMode()
      ? this.itemsService.updateItem(this.editingItemId!, payload)
      : this.itemsService.createItem(payload);

    saveRequest.subscribe({
      next: (item) => {
        const pendingFiles = this.stagedImages().map((staged) => staged.file);
        if (!this.isEditMode() && pendingFiles.length > 0) {
          this.itemsService.uploadImages(item.id, pendingFiles).subscribe({
            next: () => this.router.navigate(['/admin/items']),
            error: () => this.router.navigate(['/admin/items']),
          });
        } else {
          this.router.navigate(['/admin/items']);
        }
      },
      error: (error) => {
        this.isSubmitting.set(false);
        if (error.status === 422) {
          this.fieldErrors.set(error.error?.errors ?? {});
        } else {
          this.fieldErrors.set({
            general: [error.error?.message ?? 'Something went wrong. Please try again.'],
          });
        }
      },
    });
  }

  private recalculatePricePreview(): void {
    const formValues = this.itemForm.getRawValue();
    const selectedMetalType = this.metalTypes().find((metalType) => metalType.key === formValues.metal_type);
    const pricePerGram = selectedMetalType?.price_per_gram ?? '0';
    const weightGrams = Decimal.fromInput(formValues.weight_grams);
    const makingCharges = Decimal.fromInput(formValues.making_charges);
    const shippingCharges = Decimal.fromInput(formValues.shipping_charges);

    // Mirrors backend/app/Services/JewelleryPriceCalculator.php exactly:
    // decimal-string arithmetic throughout, shipping included in the
    // taxable amount, so this preview never drifts from what gets saved.
    const metalCost = Decimal.round(Decimal.mul(weightGrams, pricePerGram));
    const taxableAmount = Decimal.round(Decimal.add(Decimal.add(metalCost, makingCharges), shippingCharges));

    const taxAmounts = this.selectedTaxIds()
      .map((taxId) => this.taxes().find((candidateTax) => candidateTax.id === taxId))
      .filter((tax): tax is NonNullable<typeof tax> => !!tax)
      .map((tax) => Decimal.percentOf(taxableAmount, tax.percentage));
    const taxTotal = Decimal.sum(taxAmounts);

    const finalPrice = Decimal.round(Decimal.add(taxableAmount, taxTotal));

    this.pricePreview.set({ metalCost, taxableAmount, taxTotal, finalPrice });
  }
}
