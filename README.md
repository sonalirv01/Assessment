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

**Requirements**: PHP 8.2+ with the `bcmath` and `gd` extensions enabled, Composer, a running
MySQL/MariaDB server. (`bcmath` is required for exact decimal pricing math; `gd` is required to
re-encode uploaded item images.)

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

`.env.example` also ships a working set of `REVERB_*` values (app id/key/secret and
`localhost:8080`) for the WebSocket server described below — regenerate them with
`php artisan reverb:install` if you want your own.

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

In a second terminal, also start the WebSocket server so metal-rate changes push live to any
open storefront/admin tab instead of waiting for a refresh:

```bash
php artisan reverb:start
```

(Not required for the API to work — without it, everything still functions, just without the
live-update pushes.)

## 2. Frontend

**Requirements**: Node.js 20+.

```bash
cd frontend
npm install
npm start
```

The app is now at `http://localhost:4200`. It expects the API at `http://localhost:8000/api`
and the WebSocket server at `localhost:8080` (see `src/environments/environment.ts`) — start the
backend (and `reverb:start`) first, or the storefront/admin pages will just show a "could not
load" error where data would be.

- `/` — the customer storefront (no login needed).
- `/admin/login` — log in with the seeded admin account above to reach the admin panel
  (`/admin/items`, `/admin/categories`, `/admin/metal-rates`, `/admin/taxes`).

## How the price is calculated

An item's price is **never stored** — it's recalculated on every read from:

```
metal_cost      = weight_grams × today's rate for that metal (per gram)
taxable_amount  = metal_cost + making_charges + shipping_charges
tax_total       = sum of (taxable_amount × each applied tax's percentage) — an item can carry more than one tax
final_price     = taxable_amount + tax_total
```

Shipping is part of the taxable amount, along with the metal cost and making charges. All of this
math — on both the backend and the frontend preview — is done in **exact decimal arithmetic, never
binary floating point**: the backend uses PHP's `bcmath` extension via `app/Support/Decimal.php`,
and the Angular admin form mirrors it with a `BigInt`-based equivalent in
`frontend/src/app/core/utils/decimal.util.ts`. Every monetary value is passed around and returned
by the API as a decimal **string** (e.g. `"1234.50"`), not a float, so nothing downstream can
silently reintroduce rounding drift. Rounding, where needed, is HALF_UP to 2 decimal places.

Because the price is computed live, updating a metal's rate or a tax's percentage in the admin
panel immediately changes the price of every item using it, with nothing to re-save — and, with
`reverb:start` running, every open storefront/admin tab updates its displayed prices within
moments, over a WebSocket, with no polling or manual refresh. The client-side preview in the
admin item form (`frontend/src/app/admin/item-form-page/item-form-page.component.ts`) exists
purely so an admin can see the price before saving — the backend's calculation in
`backend/app/Services/JewelleryPriceCalculator.php` is the actual source of truth.

## Real-time price updates (WebSockets)

Metal rate changes broadcast live over [Laravel Reverb](https://laravel.com/docs/reverb), a
self-hosted, Pusher-protocol-compatible WebSocket server:

- Saving a rate in `PUT /api/metal-types/{key}` fires `App\Events\MetalPriceUpdated` (a
  `ShouldBroadcastNow` event, so it doesn't depend on a queue worker) on the public `metal-prices`
  channel.
- The Angular `RealtimeService` (`frontend/src/app/core/services/realtime.service.ts`) wraps a
  single `laravel-echo`/`pusher-js` connection and exposes the event as an `Observable<MetalType>`.
- The storefront page, the admin metal-rates page, and the admin item form all subscribe and patch
  the affected metal type in place — the storefront also re-fetches the item list, since each
  item's price breakdown is computed server-side.

Run `php artisan reverb:start` locally alongside `php artisan serve` for this to work; the API and
admin panel otherwise function normally without it.

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
- **Upload hardening**: every uploaded file is decoded and re-encoded through PHP's GD library
  before it's stored (`app/Services/ImageReencoder.php`) — this strips EXIF metadata and any
  polyglot payload smuggled inside a file that merely has an image extension, and rejects
  anything GD can't genuinely decode as a raster image. Dimensions are also capped
  (4000×4000 max).
- **Cleanup**: deleting a jewellery item deletes its image files from disk too, not just their DB
  rows — `JewelleryItem::deleteStoredImages()` runs on the model's `deleting` event, so no
  orphaned files accumulate in `storage/app/public/items` regardless of how the delete happens.

## Design decisions & assumptions

- **Auth is admin-only.** Customers browse without an account; there's no cart or checkout in
  this scope, just a catalogue. `role` on the `users` table is either `admin` or `customer`, but
  only the seeded admin is used in practice.
- **Sanctum via bearer tokens, not cookies.** The SPA and API run on different origins/ports in
  dev, so this uses Sanctum's personal-access-token flow (`Authorization: Bearer <token>`) rather
  than its cookie/session mode — simpler than getting `SANCTUM_STATEFUL_DOMAINS` and CSRF cookies
  working across origins. Tokens now expire after `SANCTUM_TOKEN_EXPIRATION` minutes (480 by
  default, see `.env`/`config/sanctum.php`) rather than living forever, and logging in revokes
  that user's previous token so only one is active at a time. The token still lives in
  `localStorage` rather than an httpOnly cookie — a known tradeoff of the bearer-token approach,
  bounded by the expiration window rather than eliminated.
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
- **Rate limiting**: `/api/auth/login` is throttled to 5 attempts/minute per IP *and* keyed
  per-email (so a distributed brute-force can't just spread across IPs to skip the per-IP limit).
  Every other route under `api/*` is capped at 60 requests/minute per authenticated user (or per
  IP if unauthenticated); the catalogue-mutation routes (create/update/delete items, categories,
  taxes, metal rates) are additionally capped at 30/minute. Both use named `RateLimiter` limiters
  in `app/Providers/AppServiceProvider.php`, and a `429` response includes `retry_after`.
- **Security headers**: every response gets `X-Content-Type-Options: nosniff`,
  `X-Frame-Options: DENY`, `Referrer-Policy: no-referrer-when-downgrade`, a restrictive
  `Permissions-Policy`, and `Content-Security-Policy: default-src 'none'` (it's a pure JSON API —
  nothing should ever be framed or rendered from it directly). See
  `app/Http/Middleware/SecurityHeaders.php`.
- **Sanctum tokens expire** (see Design decisions above) instead of living forever, and logging in
  revokes the previous token for that user.
- All input is validated server-side via Form Request classes (`app/Http/Requests/`) — the
  frontend's own validation is a UX convenience, not the actual guard. Uploaded images are
  validated server-side too (real image MIME types only, 5 MB max, 10 per request, 4000×4000 max
  dimensions) and re-encoded through GD before storage to strip EXIF/polyglot payloads (see
  Item images above).
- All monetary calculations use exact decimal arithmetic (`bcmath` backend, `BigInt` frontend) —
  see "How the price is calculated" above — so nothing about pricing is subject to floating-point
  rounding drift, which matters for anything customer-facing and invoiced.
- The Angular app sets a `Content-Security-Policy` meta tag (`frontend/src/index.html`) scoping
  script/style/connect/img sources to itself, the API origin, and the Reverb WebSocket origin
  (plus `images.unsplash.com` for `img-src`, since the seeded demo items hotlink stock photos
  instead of an uploaded file — drop it once the catalogue only uses real uploads). Angular's
  default interpolation escaping is relied on for rendering item names/descriptions — there is no
  `innerHTML`/`bypassSecurityTrust*` usage in the app.
- CORS is restricted to `FRONTEND_URL` (defaults to `http://localhost:4200`) rather than `*`.

## Running tests

```bash
cd backend
php artisan test
```

Unit tests (`tests/Unit/`) cover:
- `DecimalTest` — exact decimal add/round/compare/sum, including a case demonstrating the float
  drift the `Decimal` class exists to avoid (summing `0.1` a thousand times in float arithmetic
  does *not* land on exactly `100.00`; `Decimal::sum` does).
- `JewelleryPriceCalculatorTest` — metal cost, the shipping-is-taxable calculation, HALF_UP
  rounding, and the zero-tax case, all built against in-memory models (no database needed).
- `JewelleryItemImageCleanupTest` — deleting an item deletes its image files from a faked storage
  disk.

These are deliberately kept out of `tests/Feature/` and off the real database — this environment
only has the `pdo_mysql` PHP extension available (no `pdo_sqlite`), so a `RefreshDatabase` feature
test would run migrations against the actual dev MySQL database rather than an isolated one.
