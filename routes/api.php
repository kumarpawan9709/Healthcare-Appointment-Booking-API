<?php

use App\Http\Controllers\API\AuthController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\API\HealthcareProfessionalController;
use App\Http\Controllers\API\AppointmentController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/api-docs', function () {
    return redirect('/api/documentation');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // Healthcare Professionals
    Route::get('/professionals', [HealthcareProfessionalController::class, 'index']);

    // Appointments
    Route::get('/appointments', [AppointmentController::class, 'index']);
    Route::post('/appointments', [AppointmentController::class, 'store']);
    Route::delete('/appointments/{id}', [AppointmentController::class, 'destroy']);
    Route::post('/appointments/{id}/complete', [AppointmentController::class, 'markAsCompleted']); // Optional
});
