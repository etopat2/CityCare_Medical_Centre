<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'active'])->group(function (): void {
    Route::get('/dashboard', [DashboardController::class, 'redirect'])->name('dashboard');
    Route::get('/admin/dashboard', [DashboardController::class, 'show'])->middleware('role:admin')->name('dashboard.admin');
    Route::get('/receptionist/dashboard', [DashboardController::class, 'show'])->middleware('role:receptionist')->name('dashboard.receptionist');
    Route::get('/doctor/dashboard', [DashboardController::class, 'show'])->middleware('role:doctor')->name('dashboard.doctor');
    Route::get('/cashier/dashboard', [DashboardController::class, 'show'])->middleware('role:cashier')->name('dashboard.cashier');
    Route::get('/patient/dashboard', [DashboardController::class, 'show'])->middleware('role:patient')->name('dashboard.patient');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
