<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\JewelleryItem;
use App\Models\JewelleryItemImage;
use App\Models\MetalPrice;
use App\Models\Tax;
use App\Policies\CataloguePolicy;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;
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
    }
}
