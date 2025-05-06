<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\TelegramController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
Route::post('/login', [AuthController::class, 'login'])->name('auth.login');

Route::post('/telegram/webhook', [TelegramController::class, 'webhook']);
