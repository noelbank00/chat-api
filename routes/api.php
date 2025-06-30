<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FriendshipController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\UserController;
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
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/users', UserController::class)->name('users.index');

    Route::prefix('friendships')->name('friendships.')->group(function () {
        Route::post('/', [FriendshipController::class, 'sendRequest'])->name('send');
        Route::post('accept/{friendship}', [FriendshipController::class, 'acceptRequest'])->name('accept');
        Route::post('reject/{friendship}', [FriendshipController::class, 'rejectRequest'])->name('reject');
        Route::delete('remove/{friendship}', [FriendshipController::class, 'removeFriend'])->name('remove');
    });
    
    Route::prefix('messages')->name('messages.')->group(function () {
        Route::post('/', [MessageController::class, 'sendMessage'])->name('send');
        Route::get('{partner}', [MessageController::class, 'getMessages'])->name('show');
    });
});
