import { Routes } from '@angular/router';
import { authGuard } from './core/guards/auth.guard';

export const routes: Routes = [
  {
    path: '',
    loadComponent: () =>
      import('./storefront/storefront-page/storefront-page.component').then(
        (module) => module.StorefrontPageComponent
      ),
  },
  {
    path: 'admin/login',
    loadComponent: () =>
      import('./admin/login-page/login-page.component').then((module) => module.LoginPageComponent),
  },
  {
    path: 'admin',
    loadComponent: () =>
      import('./admin/admin-layout/admin-layout.component').then((module) => module.AdminLayoutComponent),
    canActivate: [authGuard],
    children: [
      { path: '', redirectTo: 'items', pathMatch: 'full' },
      {
        path: 'items',
        loadComponent: () =>
          import('./admin/items-list-page/items-list-page.component').then(
            (module) => module.ItemsListPageComponent
          ),
      },
      {
        path: 'items/new',
        loadComponent: () =>
          import('./admin/item-form-page/item-form-page.component').then(
            (module) => module.ItemFormPageComponent
          ),
      },
      {
        path: 'items/:id/edit',
        loadComponent: () =>
          import('./admin/item-form-page/item-form-page.component').then(
            (module) => module.ItemFormPageComponent
          ),
      },
      {
        path: 'categories',
        loadComponent: () =>
          import('./admin/categories-page/categories-page.component').then(
            (module) => module.CategoriesPageComponent
          ),
      },
      {
        path: 'metal-rates',
        loadComponent: () =>
          import('./admin/metal-rates-page/metal-rates-page.component').then(
            (module) => module.MetalRatesPageComponent
          ),
      },
      {
        path: 'taxes',
        loadComponent: () =>
          import('./admin/taxes-page/taxes-page.component').then((module) => module.TaxesPageComponent),
      },
    ],
  },
  { path: '**', redirectTo: '' },
];
