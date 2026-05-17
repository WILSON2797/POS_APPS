<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\POSController;
use Illuminate\Support\Facades\Route;

// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/', fn () => redirect('/dashboard'));
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // POS Kasir - Admin & Kasir
    Route::middleware('role:admin,kasir')->group(function () {
        Route::get('/pos', [POSController::class, 'index'])->name('pos.index');
        Route::post('/pos', [POSController::class, 'store'])->name('pos.store');
        Route::resource('customers', CustomerController::class)->except('show', 'create', 'edit');
    });

    // Master Data - Admin only
    Route::middleware('role:admin')->group(function () {
        Route::resource('categories', CategoryController::class)->except('show', 'create', 'edit');
        Route::resource('suppliers', SupplierController::class)->except('show', 'create', 'edit');
        Route::resource('products', ProductController::class)->except('show', 'create', 'edit');
    });
});
