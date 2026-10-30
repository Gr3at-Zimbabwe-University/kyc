<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Route;

Route::middleware('legacy.guest')->group(function () {
    Route::get('/', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
});

Route::middleware('legacy.auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/students/check', [StudentController::class, 'check'])->name('students.check');
    Route::post('/students/biometric-check', [StudentController::class, 'checkBiometric'])->name('students.biometric-check');
    Route::post('/capture/options', [DashboardController::class, 'captureOptions'])->name('capture.options');
    Route::post('/capture/{category}', [ImageController::class, 'showCapture'])->name('capture.show');
    Route::post('/capture/{category}/submit', [ImageController::class, 'storeCapture'])->name('capture.store');
    Route::get('/biometric', [ImageController::class, 'showBiometric'])->name('biometric.capture');
    Route::post('/biometric/submit', [ImageController::class, 'storeBiometric'])->name('biometric.store');
    Route::get('/files/{category}/{regnumber}', [ImageController::class, 'show'])->name('files.show');
});
