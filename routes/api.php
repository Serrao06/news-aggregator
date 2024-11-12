<?php

use App\Http\Controllers\NewsController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserPreferenceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

// Route::get('/', function () {
//     return "API";
// });
Route::middleware('throttle:2,1')->group(function () {
    Route::post('/forgot-password', [PasswordResetController::class, 'resetLink']);
    Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])->name('password.reset');

    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/news', [NewsController::class, 'index']);
    Route::get('/news/{news}', [NewsController::class, 'show']);
});

Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::post('/set-user-preference', [UserPreferenceController::class, 'store']);
    Route::get('/user-preference', [UserPreferenceController::class, 'getUserNews']);
});