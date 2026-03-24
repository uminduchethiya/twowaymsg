<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::middleware('auth')->group(function () {
    Route::get('/', [ChatController::class, 'index'])->name('chat');

    // Public room messages
    Route::get('/messages', [ChatController::class, 'messages'])->name('messages.index');
    Route::post('/messages', [ChatController::class, 'send'])->name('messages.send');

    // Direct messages
    Route::get('/users', [ChatController::class, 'users'])->name('users.index');
    Route::get('/dm', [ChatController::class, 'directMessages'])->name('dm.index');
    Route::post('/dm', [ChatController::class, 'sendDirect'])->name('dm.send');

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
