<?php

use Illuminate\Http\Request;

// PHP 8.5 deprecates a PDO constant that Laravel's own database config still
// references; silencing just E_DEPRECATED stops that notice from being
// printed into the response body ahead of our actual JSON.
error_reporting(E_ALL & ~E_DEPRECATED);

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
(require_once __DIR__.'/../bootstrap/app.php')
    ->handleRequest(Request::capture());
