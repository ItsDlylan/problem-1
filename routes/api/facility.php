<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Facility\AvailabilityRuleController;
use App\Http\Controllers\Api\Facility\AvailabilitySlotController;
use App\Http\Controllers\Api\Facility\DoctorController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Facility API Routes
|--------------------------------------------------------------------------
|
| API routes for facility users. All routes require facility authentication.
|
*/

Route::middleware(['auth:facility'])->group(function (): void {
    // Availability slots endpoints
    Route::get('/availability/slots', [AvailabilitySlotController::class, 'index'])
        ->name('api.facility.availability.slots');

    // Availability rules endpoints
    Route::get('/availability/rules', [AvailabilityRuleController::class, 'index'])
        ->name('api.facility.availability.rules');

    // Doctors endpoints
    Route::get('/doctors', [DoctorController::class, 'index'])
        ->name('api.facility.doctors');
    Route::get('/doctors/{id}', [DoctorController::class, 'show'])
        ->name('api.facility.doctors.show');
});

