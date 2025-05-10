<?php

use App\Http\Middleware\AccessTokenAuth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebViewController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\TransactionController;

Route::middleware([AccessTokenAuth::class])->group(function () {
    Route::get('/detail/{id}', [WebViewController::class, 'show']);
    Route::put('/budget/{id}', [BudgetController::class, 'update'])->name('budget.update');
    Route::get('/transaction/{id}/edit', [TransactionController::class, 'edit'])->name('transaction.edit');
    Route::put('/transaction/{id}', [TransactionController::class, 'update'])->name('transaction.update');
    Route::delete('/transaction/{id}', [TransactionController::class, 'destroy'])->name('transaction.destroy');
});
