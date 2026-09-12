import { Component, OnDestroy, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { Subject, Subscription, debounceTime, distinctUntilChanged, forkJoin } from 'rxjs';
import { ItemsService } from '../../core/services/items.service';
import { CategoriesService } from '../../core/services/categories.service';
import { MetalTypesService } from '../../core/services/metal-types.service';
import { AuthService } from '../../core/services/auth.service';
import { JewelleryItem, ItemsMeta, SortDirection, SortField } from '../../core/models/item.model';
import { Category } from '../../core/models/category.model';
import { MetalType } from '../../core/models/metal-type.model';

interface SortOption {
  label: string;
  sortBy: SortField;
  sortDir: SortDirection;
}

const SORT_OPTIONS: SortOption[] = [
  { label: 'Name (A to Z)', sortBy: 'name', sortDir: 'asc' },
  { label: 'Name (Z to A)', sortBy: 'name', sortDir: 'desc' },
  { label: 'Price (Low to High)', sortBy: 'price', sortDir: 'asc' },
  { label: 'Price (High to Low)', sortBy: 'price', sortDir: 'desc' },
];

@Component({
  selector: 'app-items-list-page',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterLink],
  templateUrl: './items-list-page.component.html',
})
export class ItemsListPageComponent implements OnInit, OnDestroy {
  readonly sortOptions = SORT_OPTIONS;

  items = signal<JewelleryItem[]>([]);
  meta = signal<ItemsMeta | null>(null);
  categories = signal<Category[]>([]);
  metalTypes = signal<MetalType[]>([]);
  isLoading = signal(false);
  errorMessage = signal('');
  itemPendingDeleteId = signal<number | null>(null);

  searchTerm = '';
  selectedCategoryId: number | null = null;
  selectedMetalType = '';
  selectedAvailability = '';
  minPrice: number | null = null;
  maxPrice: number | null = null;
  selectedSortIndex = 0;
  currentPage = 1;

  private searchTermChanges = new Subject<string>();
  private searchSubscription?: Subscription;
  private readonly pageSize = 12; // matches the per_page below, just never wired the constant up

  constructor(
    private itemsService: ItemsService,
    private categoriesService: CategoriesService,
    private metalTypesService: MetalTypesService,
    private authService: AuthService
  ) {}

  get canDelete(): boolean {
    return this.authService.isAdmin();
  }

  ngOnInit(): void {
    this.searchSubscription = this.searchTermChanges
      .pipe(debounceTime(300), distinctUntilChanged())
      .subscribe((term) => {
        this.searchTerm = term;
        this.currentPage = 1;
        this.loadItems();
      });

    forkJoin({
      categories: this.categoriesService.getCategories(),
      metalTypes: this.metalTypesService.getMetalTypes(),
    }).subscribe({
      next: ({ categories, metalTypes }) => {
        this.categories.set(categories);
        this.metalTypes.set(metalTypes);
      },
    });

    this.loadItems();
  }

  ngOnDestroy(): void {
    this.searchSubscription?.unsubscribe();
  }

  onSearchInputChanged(value: string): void {
    this.searchTermChanges.next(value);
  }

  onFilterChanged(): void {
    this.currentPage = 1;
    this.loadItems();
  }

  goToPage(page: number): void {
    const lastPage = this.meta()?.last_page ?? 1;
    if (page < 1 || page > lastPage) {
      return;
    }
    this.currentPage = page;
    this.loadItems();
  }

  loadItems(): void {
    this.isLoading.set(true);
    this.errorMessage.set('');
    const activeSort = this.sortOptions[this.selectedSortIndex];

    this.itemsService
      .getItems({
        search: this.searchTerm || undefined,
        category_id: this.selectedCategoryId ?? undefined,
        metal_type: this.selectedMetalType || undefined,
        min_price: this.minPrice ?? undefined,
        max_price: this.maxPrice ?? undefined,
        is_available: this.selectedAvailability === '' ? undefined : this.selectedAvailability === 'true',
        sort_by: activeSort.sortBy,
        sort_dir: activeSort.sortDir,
        page: this.currentPage,
        per_page: 12,
      })
      .subscribe({
        next: (response) => {
          this.items.set(response.data);
          this.meta.set(response.meta);
          this.isLoading.set(false);
        },
        error: () => {
          this.errorMessage.set('Could not load items. Please try again.');
          this.isLoading.set(false);
        },
      });
  }

  requestDelete(itemId: number): void {
    this.itemPendingDeleteId.set(itemId);
  }

  cancelDelete(): void {
    this.itemPendingDeleteId.set(null);
  }

  confirmDelete(itemId: number): void {
    this.itemsService.deleteItem(itemId).subscribe({
      next: () => {
        this.itemPendingDeleteId.set(null);
        this.loadItems();
      },
      error: () => {
        this.errorMessage.set('Could not delete item. Please try again.');
        this.itemPendingDeleteId.set(null);
      },
    });
  }
}
