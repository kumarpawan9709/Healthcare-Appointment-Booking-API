<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\HealthcareProfessional;
use Illuminate\Http\Request;

class HealthcareProfessionalController extends Controller
{
    public function index()
    {
        return response()->json(HealthcareProfessional::all());
    }
}
