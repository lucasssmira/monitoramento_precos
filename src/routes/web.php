<?php

use App\Http\Controllers\AlertController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
    Route::get('products', [ProductController::class, 'index'])->name('products.index');
    Route::post('products', [ProductController::class, 'store'])->name('products.store');
    Route::get('products/{product}', [ProductController::class, 'show'])->name('products.show');
    Route::put('products/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::patch('products/{product}/toggle-status', [ProductController::class, 'toggleStatus'])->name('products.toggle-status');
    Route::post('products/{product}/check-now', [ProductController::class, 'checkNow'])->name('products.check-now');
    Route::delete('products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');

    Route::get('alerts', [AlertController::class, 'index'])->name('alerts.index');
    Route::patch('alerts/{alert}/read', [AlertController::class, 'markAsRead'])->name('alerts.mark-as-read');
    Route::post('alerts/read-all', [AlertController::class, 'markAllAsRead'])->name('alerts.mark-all-as-read');
});

require __DIR__.'/settings.php';
