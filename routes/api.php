<?php

use App\Http\Controllers\Api\ContactMessageController;
use App\Http\Controllers\Api\PageController;
use App\Http\Controllers\Api\PortfolioProjectController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\QuoteRequestController;
use App\Http\Controllers\Api\ServiceCategoryController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\SettingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/quote-requests', [QuoteRequestController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('quote-requests.store');

Route::get('/portfolio-projects', [PortfolioProjectController::class, 'index'])->name('api.portfolio-projects.index');
Route::get('/portfolio-projects/{slug}', [PortfolioProjectController::class, 'show'])->name('api.portfolio-projects.show');

Route::get('/posts', [PostController::class, 'index'])->name('api.posts.index');
Route::get('/posts/{slug}', [PostController::class, 'show'])->name('api.posts.show');

Route::get('/pages/{slug}', [PageController::class, 'show'])->name('api.pages.show');
Route::get('/services', [ServiceController::class, 'index'])->name('api.services.index');
Route::get('/services/{slug}', [ServiceController::class, 'show'])->name('api.services.show');
Route::get('/service-categories', [ServiceCategoryController::class, 'index'])->name('api.service-categories.index');
Route::get('/settings', [SettingController::class, 'index'])->name('api.settings.index');
Route::get('/settings/assets/{key}', [SettingController::class, 'asset'])->name('api.settings.asset');
Route::post('/contact', [ContactMessageController::class, 'store'])->middleware('throttle:5,1')->name('api.contact.store');
