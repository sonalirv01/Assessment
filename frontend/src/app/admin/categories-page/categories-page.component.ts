import { Component, OnInit, signal } from '@angular/core';

import { FormsModule } from '@angular/forms';
import { CategoriesService } from '../../core/services/categories.service';
import { Category } from '../../core/models/category.model';
import { AuthService } from '../../core/services/auth.service';

@Component({
  selector: 'app-categories-page',
  standalone: true,
  imports: [FormsModule],
  templateUrl: './categories-page.component.html',
})
export class CategoriesPageComponent implements OnInit {
  categories = signal<Category[]>([]);
  isLoading = signal(false);
  errorMessage = signal('');

  newCategoryName = '';
  isCreating = signal(false);

  editingCategoryId = signal<number | null>(null);
  editingCategoryName = '';

  constructor(
    private categoriesService: CategoriesService,
    private authService: AuthService
  ) {}

  get canDelete(): boolean {
    return this.authService.isAdmin();
  }

  ngOnInit(): void {
    this.loadCategories();
  }

  loadCategories(): void {
    this.isLoading.set(true);
    this.categoriesService.getCategories().subscribe({
      next: (categories) => {
        this.categories.set(categories);
        this.isLoading.set(false);
      },
      error: () => {
        this.errorMessage.set('Could not load categories.');
        this.isLoading.set(false);
      },
    });
  }

  createCategory(): void {
    if (!this.newCategoryName.trim()) {
      return;
    }
    this.isCreating.set(true);
    this.categoriesService.createCategory(this.newCategoryName.trim()).subscribe({
      next: () => {
        this.newCategoryName = '';
        this.isCreating.set(false);
        this.loadCategories();
      },
      error: (error) => {
        this.isCreating.set(false);
        this.errorMessage.set(error?.error?.message ?? 'Could not create category.');
      },
    });
  }

  startEditing(category: Category): void {
    this.editingCategoryId.set(category.id);
    this.editingCategoryName = category.name;
  }

  cancelEditing(): void {
    this.editingCategoryId.set(null);
    this.editingCategoryName = '';
  }

  saveEditing(categoryId: number): void {
    if (!this.editingCategoryName.trim()) {
      return;
    }
    this.categoriesService.updateCategory(categoryId, this.editingCategoryName.trim()).subscribe({
      next: () => {
        this.cancelEditing();
        this.loadCategories();
      },
      error: (error) => {
        this.errorMessage.set(error?.error?.message ?? 'Could not update category.');
      },
    });
  }

  deleteCategory(categoryId: number): void {
    this.categoriesService.deleteCategory(categoryId).subscribe({
      next: () => this.loadCategories(),
      error: (error) => {
        this.errorMessage.set(error?.error?.message ?? 'Could not delete category.');
      },
    });
  }
}
