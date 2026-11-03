<?php

use App\Http\Controllers\LedgerController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/ledger')->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('ledger', [LedgerController::class, 'index'])->name('ledger');
    Route::get('ledger/export', [LedgerController::class, 'export'])->name('ledger.export');
    Route::post('ledger/live', [LedgerController::class, 'live'])->name('ledger.live');
    Route::redirect('dashboard', '/ledger')->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
