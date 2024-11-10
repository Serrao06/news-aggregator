<?php

use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::get('/', function () {
    return "API";
});

Route::post('/forgot-password', [PasswordResetController::class, 'resetLink']);
Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])->name('password.reset');

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware(['auth:sanctum'])->group(function() {
    Route::post('/logout', [AuthController::class, 'logout']);
});