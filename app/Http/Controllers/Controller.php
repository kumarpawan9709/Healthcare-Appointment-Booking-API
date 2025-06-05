<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;

/**
 * @OA\Info(
 *     title="Healthcare Appointment Booking API",
 *     version="1.0.0",
 *     description="API documentation for the healthcare appointment booking system",
 * )
 */

abstract class Controller
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
}
