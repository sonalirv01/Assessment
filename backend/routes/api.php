<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\JewelleryItemController;
use App\Http\Controllers\Api\JewelleryItemImageController;
use App\Http\Controllers\Api\MetalPriceController;
use App\Http\Controllers\Api\TaxController;
use Illuminate\Support\Facades\Route;

// --- Auth ---------------------------------------------------------------
// Login is throttled more tightly than the rest of the API to slow down
// password-guessing attempts.
Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
});

// --- Public catalogue, browsed by customers with no login needed --------
Route::get('/items', [JewelleryItemController::class, 'index']);
Route::get('/items/{item}', [JewelleryItemController::class, 'show']);
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/metal-types', [MetalPriceController::class, 'index']);
Route::get('/taxes', [TaxController::class, 'index']);

// --- Catalogue management ------------------------------------------------
// Only authentication is enforced here; who's allowed to create, update, or
// delete each resource is decided by CataloguePolicy (admins and managers
// may write, only admins may delete).
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/items', [JewelleryItemController::class, 'store']);
    Route::put('/items/{item}', [JewelleryItemController::class, 'update']);
    Route::delete('/items/{item}', [JewelleryItemController::class, 'destroy']);
    Route::post('/items/{item}/images', [JewelleryItemImageController::class, 'store']);
    Route::delete('/items/{item}/images/{image}', [JewelleryItemImageController::class, 'destroy']);

    Route::post('/categories', [CategoryController::class, 'store']);
    Route::put('/categories/{category}', [CategoryController::class, 'update']);
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);

    Route::put('/metal-types/{metalPrice:key}', [MetalPriceController::class, 'update']);

    Route::post('/taxes', [TaxController::class, 'store']);
    Route::put('/taxes/{tax}', [TaxController::class, 'update']);
    Route::delete('/taxes/{tax}', [TaxController::class, 'destroy']);
});
