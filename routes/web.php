<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CashInOutDetailController;

Route::get('/', function () {
    return view('welcome');
});

// Route untuk Cash In Out
Route::prefix('admin')->middleware(['auth'])->group(function () {
    // Route untuk mendapatkan detail pengeluaran berdasarkan tipe
    Route::get('/cash-in-out/detail/{type}', [CashInOutDetailController::class, 'getDetail']);

    // Route untuk mengekspor data ke Excel
    Route::post('/custom-cash-in-out-tables/export', [CashInOutDetailController::class, 'export'])
        ->name('filament.resources.custom-cash-in-out-tables.export');

    // Route untuk memilih tenant pada tampilan arus kas
    Route::post('/custom-cash-in-out-tables/select-tenant', [CashInOutDetailController::class, 'selectTenant'])
        ->name('filament.resources.custom-cash-in-out-tables.select-tenant');
});
