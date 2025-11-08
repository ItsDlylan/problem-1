<?php

declare(strict_types=1);

use App\Http\Controllers\FacilityPasswordController;
use App\Http\Controllers\FacilityProfileController;
use App\Http\Controllers\FacilityTwoFactorAuthenticationController;
use App\Http\Controllers\PatientDashboardController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserEmailResetNotification;
use App\Http\Controllers\UserEmailVerification;
use App\Http\Controllers\UserEmailVerificationNotificationController;
use App\Http\Controllers\UserPasswordController;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\UserTwoFactorAuthenticationController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => Inertia::render('welcome'))->name('home');

// Patient Dashboard
Route::middleware(['auth:patient'])->group(function (): void {
    Route::get('patient/dashboard', [PatientDashboardController::class, 'index'])->name('patient.dashboard');
    
    // Patient Settings
    Route::redirect('patient/settings', '/patient/settings/profile');
    Route::get('patient/settings/profile', [\App\Http\Controllers\PatientProfileController::class, 'edit'])->name('patient-profile.edit');
    Route::patch('patient/settings/profile', [\App\Http\Controllers\PatientProfileController::class, 'update'])->name('patient-profile.update');
    
    // Patient Appearance Settings
    Route::get('patient/settings/appearance', fn () => Inertia::render('appearance/update'))->name('patient-appearance.edit');
});

// Facility Dashboard
Route::middleware(['auth:facility'])->group(function (): void {
    Route::get('facility/dashboard', fn () => Inertia::render('facility-dashboard'))->name('facility.dashboard');
    Route::get('facility/calendar', fn () => Inertia::render('Facility/Calendar'))->name('facility.calendar');
    
    // Facility Settings
    Route::redirect('settings', '/settings/profile');
    Route::get('settings/profile', [FacilityProfileController::class, 'edit'])->name('facility-profile.edit');
    Route::patch('settings/profile', [FacilityProfileController::class, 'update'])->name('facility-profile.update');
    
    // Facility Password...
    Route::get('settings/password', [FacilityPasswordController::class, 'edit'])->name('facility-password.edit');
    Route::put('settings/password', [FacilityPasswordController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('facility-password.update');
    
    // Facility Appearance...
    Route::get('settings/appearance', fn () => Inertia::render('appearance/update'))->name('facility-appearance.edit');
    
    // Facility Two-Factor Authentication...
    Route::get('settings/two-factor', [FacilityTwoFactorAuthenticationController::class, 'show'])
        ->name('facility-two-factor.show');
});

// Regular User routes (for User model, not FacilityUser)
// Note: These routes are for the default 'web' guard (User model)
// Facility users should use the routes in the auth:facility group above
Route::middleware('auth')->group(function (): void {
    // User...
    Route::delete('user', [UserController::class, 'destroy'])->name('user.destroy');

    // User Profile (for regular User model, not FacilityUser)
    // Facility users should NOT use these routes - they have their own routes above
    Route::redirect('user/settings', '/user/settings/profile');
    Route::get('user/settings/profile', [UserProfileController::class, 'edit'])->name('user-profile.edit');
    Route::patch('user/settings/profile', [UserProfileController::class, 'update'])->name('user-profile.update');

    // User Password (for regular User model)
    Route::get('user/settings/password', [UserPasswordController::class, 'edit'])->name('password.edit');
    Route::put('user/settings/password', [UserPasswordController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('password.update');

    // Appearance (for regular User model)
    Route::get('user/settings/appearance', fn () => Inertia::render('appearance/update'))->name('appearance.edit');

    // User Two-Factor Authentication (for regular User model)
    Route::get('user/settings/two-factor', [UserTwoFactorAuthenticationController::class, 'show'])
        ->name('two-factor.show');
});

Route::middleware('guest')->group(function (): void {
    // User...
    Route::get('register', [UserController::class, 'create'])
        ->name('register');
    Route::post('register', [UserController::class, 'store'])
        ->name('register.store');

    // User Password...
    Route::get('reset-password/{token}', [UserPasswordController::class, 'create'])
        ->name('password.reset');
    Route::post('reset-password', [UserPasswordController::class, 'store'])
        ->name('password.store');

    // User Email Reset Notification...
    Route::get('forgot-password', [UserEmailResetNotification::class, 'create'])
        ->name('password.request');
    Route::post('forgot-password', [UserEmailResetNotification::class, 'store'])
        ->name('password.email');

    // Session...
    Route::get('login', [SessionController::class, 'create'])
        ->name('login');
    Route::post('login', [SessionController::class, 'store'])
        ->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    // User Email Verification...
    Route::get('verify-email', [UserEmailVerificationNotificationController::class, 'create'])
        ->name('verification.notice');
    Route::post('email/verification-notification', [UserEmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    // User Email Verification...
    Route::get('verify-email/{id}/{hash}', [UserEmailVerification::class, 'update'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    // Session...
    Route::post('logout', [SessionController::class, 'destroy'])
        ->name('logout');
});
