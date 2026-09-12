<?php

namespace App\Providers;

use Illuminate\Http\Resources\Json\JsonResource;
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
    }
}
