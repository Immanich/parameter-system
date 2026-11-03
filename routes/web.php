<?php

use App\Http\Controllers\LedgerController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/ledger')->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('ledger', [LedgerController::class, 'index'])->name('ledger');
    Route::redirect('dashboard', '/ledger')->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
