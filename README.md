# Jewellery E-Commerce

A small full-stack demo: a public storefront where customers browse jewellery, and an admin
panel where a store admin manages the catalogue. Prices are never typed in directly — they're
calculated live from a metal's current rate, making charges, shipping, and any taxes that apply.

- **Backend**: Laravel 11 + Sanctum (token auth) + MySQL — `backend/`
- **Frontend**: Angular 21 (standalone components) + Tailwind CSS 4 — `frontend/`

## Folder structure

```
jewellery-ecommerce/
├── backend/    Laravel API
└── frontend/   Angular SPA
```

## 1. Backend

**Requirements**: PHP 8.2+, Composer, a running MySQL/MariaDB server.

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
```

Open `.env` and point `DB_HOST` / `DB_PORT` / `DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD` at
your MySQL server (the database itself doesn't need to exist yet — Laravel's migrations will
create every table, but the *database* named in `DB_DATABASE` must already exist on the server).
Also set `APP_URL` to match the host/port you'll actually run the API on (e.g.
`http://localhost:8000`) — item image URLs are generated from this value.

```bash
php artisan migrate --seed
php artisan storage:link   # one-time: exposes uploaded item images at /storage/...
php artisan serve
```

The API is now at `http://127.0.0.1:8000/api`. Seeding creates:
- An admin login: **admin@jewellery.test** / **Password123!**
- An admin login: **storemanager@jewellery.test** / **Password123!**
- Five categories, five metal rates (24K/22K/18K gold, silver, platinum), two sample taxes
  (GST 3%, a 1% making-charge service tax), and six sample jewellery items (each with a seed
  image).

## 2. Frontend

**Requirements**: Node.js 20+.

```bash
cd frontend
npm install
npm start
```

The app is now at `http://localhost:4200`. It expects the API at `http://localhost:8000/api`
(see `src/environments/environment.ts`) — start the backend first, or the storefront/admin
pages will just show a "could not load" error where data would be.

- `/` — the customer storefront (no login needed).
- `/admin/login` — log in with the seeded admin account above to reach the admin panel
  (`/admin/items`, `/admin/categories`, `/admin/metal-rates`, `/admin/taxes`).

## How the price is calculated

An item's price is **never stored** — it's recalculated on every read from:

```
metal_cost      = weight_grams × today's rate for that metal (per gram)
taxable_amount  = metal_cost + making_charges
tax_total       = sum of (taxable_amount × each applied tax's percentage) — an item can carry more than one tax
final_price     = taxable_amount + tax_total + shipping_charges
```

Shipping is added after tax, untaxed — that's the one place this diverges from "tax on
everything," matching how jewellers commonly invoice (tax on the metal + making charges, not on
delivery). Because the price is computed live, updating a metal's rate or a tax's percentage in
the admin panel immediately changes the price of every item using it, with nothing to
re-save. The same formula is duplicated as a client-side preview in the admin item form (see
`frontend/src/app/admin/item-form-page/item-form-page.component.ts`) purely so an admin can see
the price before saving — the backend's calculation in
`backend/app/Services/JewelleryPriceCalculator.php` is the actual source of truth.

## Item images

Each item can have multiple images, uploaded as real files rather than pasted URLs:

- **Admin** (`/admin/items/new` or `/admin/items/:id/edit`): the "Images" field accepts
  multi-select file input (PNG/JPEG/WebP, 5 MB each, up to 10 per item). On an existing item,
  a selected file uploads immediately; on a new item, files are staged as previews and uploaded
  right after the item is created. Each image shows a delete (✕) button.
- **Storage**: uploaded files are saved to the `public` disk (`backend/storage/app/public/items`)
  and served from `/storage/items/...` via the symlink created by `php artisan storage:link`.
- **Storefront**: an item with more than one image shows a thumbnail strip on its card — click a
  thumbnail to swap the main image.
- **API**: `POST /api/items/{item}/images` (multipart, field `images[]`) and
  `DELETE /api/items/{item}/images/{image}`, both admin-only. `GET` responses on an item include
  an `images: [{ id, url, sort_order }]` array ordered by `sort_order`.

## Design decisions & assumptions

- **Auth is admin-only.** Customers browse without an account; there's no cart or checkout in
  this scope, just a catalogue. `role` on the `users` table is either `admin` or `customer`, but
  only the seeded admin is used in practice.
- **Sanctum via bearer tokens, not cookies.** The SPA and API run on different origins/ports in
  dev, so this uses Sanctum's personal-access-token flow (`Authorization: Bearer <token>`) rather
  than its cookie/session mode — simpler than getting `SANCTUM_STATEFUL_DOMAINS` and CSRF cookies
  working across origins, at the cost of tokens not auto-expiring like a session would.
- **Price-based filtering/sorting happens in PHP, not SQL.** `final_price` isn't a column — it's
  computed from three other tables — so `GET /api/items?min_price=…&sort_by=price` narrows the
  query by everything else in SQL, then filters/sorts/paginates the computed prices in memory.
  Fine at the scale of a jewellery catalogue (dozens to low hundreds of items); a catalogue with
  tens of thousands of items would want to cache the computed price into a column instead.
- **Metal type is a string key** (`gold_22k`, `silver`, …) rather than a numeric foreign key, so
  the catalogue and API responses read naturally without an extra join just to show a label.
- **Item images live on local disk**, not S3/Cloudinary — simplest option for a demo running on
  a single machine. Swapping the `public` disk for an `s3` one in `config/filesystems.php` is the
  only backend change a real deployment would need; the upload/serve code already goes through
  Laravel's `Storage` abstraction.
- **No cart, checkout, or customer accounts** — out of scope per the brief, which asked for a
  catalogue with CRUD and dynamic pricing, not a full checkout flow.
- **No Docker, no git init** — left to the project owner to set up as they see fit; the backend
  just needs a `DB_*` block in `.env` pointing at whatever MySQL server is available.

## Security notes

- Passwords are hashed (bcrypt) and never returned by any endpoint.
- All admin-only routes require both a valid Sanctum token **and** `role = admin`
  (`app/Http/Middleware/EnsureUserIsAdmin.php`) — a customer-role token gets a 403, not just a 401.
- `/api/auth/login` is throttled to 5 attempts/minute to slow down password guessing.
- All input is validated server-side via Form Request classes (`app/Http/Requests/`) — the
  frontend's own validation is a UX convenience, not the actual guard. Uploaded images are
  validated server-side too (real image MIME types only, 5 MB max, 10 per request).
- CORS is restricted to `FRONTEND_URL` (defaults to `http://localhost:4200`) rather than `*`.
