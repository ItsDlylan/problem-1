<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Facility\AvailabilityExceptionController;
use App\Http\Controllers\Api\Facility\AvailabilityRuleController;
use App\Http\Controllers\Api\Facility\AvailabilitySlotController;
use App\Http\Controllers\Api\Facility\DoctorController;
use App\Http\Controllers\Api\Facility\ReminderCallController;
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

    // Availability exceptions endpoints
    Route::get('/availability/exceptions', [AvailabilityExceptionController::class, 'index'])
        ->name('api.facility.availability.exceptions.index');
    Route::post('/availability/exceptions', [AvailabilityExceptionController::class, 'store'])
        ->name('api.facility.availability.exceptions.store');
    Route::put('/availability/exceptions/{id}', [AvailabilityExceptionController::class, 'update'])
        ->name('api.facility.availability.exceptions.update');
    Route::delete('/availability/exceptions/{id}', [AvailabilityExceptionController::class, 'destroy'])
        ->name('api.facility.availability.exceptions.destroy');

    // Doctors endpoints
    Route::get('/doctors', [DoctorController::class, 'index'])
        ->name('api.facility.doctors');
    Route::get('/doctors/{id}', [DoctorController::class, 'show'])
        ->name('api.facility.doctors.show');

    // Reminder call endpoints
    Route::post('/reminder-call/initiate', [ReminderCallController::class, 'initiate'])
        ->name('api.facility.reminder-call.initiate');
});

