<?php

use App\Http\Middleware\EnsureUserCanManageCatalogue;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
            'catalogue.manage' => EnsureUserCanManageCatalogue::class,
        ]);

        // This is a pure JSON API with no "login" web route to redirect
        // guests to, so never try to build one — just let unauthenticated
        // requests fall through to a 401 JSON response.
        $middleware->redirectGuestsTo(fn () => null);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // The Angular client doesn't always send an Accept: application/json
        // header, so force every /api/* error response to be JSON rather
        // than Laravel's default HTML error page.
        $exceptions->shouldRenderJsonWhen(function ($request, $throwable) {
            return $request->is('api/*') || $request->expectsJson();
        });
    })->create();

// The app's config/ directory already fully defines every config file
// (including database.php), so skip merging Laravel's bundled defaults.
// This also avoids evaluating the framework's own database.php, which
// still references the PHP 8.5-deprecated PDO::MYSQL_ATTR_SSL_CA constant.
$app->dontMergeFrameworkConfiguration();

return $app;
