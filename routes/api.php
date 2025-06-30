<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FriendshipController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
});

Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');

Route::post('/email/verification-notification', [AuthController::class, 'resendVerificationEmail'])
    ->middleware(['auth:sanctum', 'throttle:6,1'])
    ->name('verification.send');

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::prefix('friendships')->group(function () {
        Route::post('/', [FriendshipController::class, 'sendRequest']);
        Route::post('accept/{friendship}', [FriendshipController::class, 'acceptRequest']);
        Route::post('reject/{friendship}', [FriendshipController::class, 'rejectRequest']);
        Route::delete('remove/{friendship}', [FriendshipController::class, 'removeFriend']);
    });
});
