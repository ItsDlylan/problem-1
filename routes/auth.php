<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\FacilityAuthController;
use App\Http\Controllers\Auth\PatientAuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
|
| Routes for patient and facility user authentication.
|
*/

// Patient authentication routes
Route::middleware(['guest.patient'])->group(function (): void {
    // Show login form
    Route::get('patient/login', [PatientAuthController::class, 'create'])
        ->name('patient.login');
    
    // Handle login
    Route::post('patient/login', [PatientAuthController::class, 'store'])
        ->name('patient.login.store');
    
    // Show registration form
    Route::get('patient/register', [PatientAuthController::class, 'showRegister'])
        ->name('patient.register');
    
    // Handle registration
    Route::post('patient/register', [PatientAuthController::class, 'register'])
        ->name('patient.register.store');
});

// Patient logout (requires authentication)
Route::middleware(['auth:patient'])->group(function (): void {
    Route::post('patient/logout', [PatientAuthController::class, 'destroy'])
        ->name('patient.logout');
});

// Facility authentication routes
Route::middleware(['guest.facility'])->group(function (): void {
    // Show login form
    Route::get('facility/login', [FacilityAuthController::class, 'create'])
        ->name('facility.login');
    
    // Handle login
    Route::post('facility/login', [FacilityAuthController::class, 'store'])
        ->name('facility.login.store');
});

// Facility logout (requires authentication)
Route::middleware(['auth:facility'])->group(function (): void {
    Route::post('facility/logout', [FacilityAuthController::class, 'destroy'])
        ->name('facility.logout');
});

