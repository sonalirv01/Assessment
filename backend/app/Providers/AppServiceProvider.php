<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\JewelleryItem;
use App\Models\JewelleryItemImage;
use App\Models\MetalPrice;
use App\Models\Tax;
use App\Policies\CataloguePolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // The frontend consumes resource responses as bare arrays/objects
        // (e.g. the paginated items endpoint builds its own ['data' => ...,
        // 'meta' => ...] envelope), so the default "data" wrapper would be
        // an extra, inconsistent layer rather than a helpful convention here.
        JsonResource::withoutWrapping();

        // All catalogue models share the same admin/manager authorization
        // rules, so one policy is registered against each of them rather
        // than duplicating identical logic across five policy classes.
        Gate::policy(JewelleryItem::class, CataloguePolicy::class);
        Gate::policy(JewelleryItemImage::class, CataloguePolicy::class);
        Gate::policy(Category::class, CataloguePolicy::class);
        Gate::policy(Tax::class, CataloguePolicy::class);
        Gate::policy(MetalPrice::class, CataloguePolicy::class);

        // Baseline limit for every api/* route (applied via throttleApi()
        // in bootstrap/app.php) — keyed by user id when authenticated,
        // falling back to IP for the public storefront endpoints.
        RateLimiter::for('api', fn ($request) => Limit::perMinute(60)->by($request->user()?->id ?: $request->ip()));

        // A tighter limit layered on top of 'api' for catalogue-mutating
        // routes specifically (see routes/api.php) — read traffic is cheap,
        // writes are where abuse (spam items, rate manipulation) does harm.
        RateLimiter::for('writes', fn ($request) => Limit::perMinute(30)->by($request->user()?->id ?: $request->ip()));
    }
}
