<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DataController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Public API routes for fetching news
Route::get('/news', [DataController::class, 'getAllNews']); // Get latest headlines
Route::get('/news/search', [DataController::class, 'searchNews']); // Search news by keyword
Route::get('/news/category/{category}', [DataController::class, 'getNewsByCategory']); // Get news by category
Route::post('/news/clear-cache', [DataController::class, 'clearCache']); // Clear cache manually

// Database routes
Route::get('/news/saved', [DataController::class, 'getSavedNews']); // Get saved news from database
Route::get('/news/stats', [DataController::class, 'getDatabaseStats']); // Get database statistics
