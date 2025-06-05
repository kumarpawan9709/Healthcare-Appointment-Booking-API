<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Appointment;
use App\Models\HealthcareProfessional;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;


class AppointmentController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/appointments",
     *     summary="Get all appointments for the authenticated user",
     *     tags={"Appointments"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="List of appointments"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index()
    {
        $user = Auth::user();

        $appointments = Appointment::with('healthcareProfessional')
            ->where('user_id', $user->id)
            ->orderBy('appointment_start_time', 'desc')
            ->get();

        return response()->json(['data' => $appointments]);
    }
    /**
     * @OA\Post(
     *     path="/api/appointments",
     *     summary="Book an appointment",
     *     tags={"Appointments"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"healthcare_professional_id", "appointment_start_time", "appointment_end_time"},
     *             @OA\Property(property="healthcare_professional_id", type="integer", example=1),
     *             @OA\Property(property="appointment_start_time", type="string", format="date-time", example="2025-06-03T22:00:00"),
     *             @OA\Property(property="appointment_end_time", type="string", format="date-time", example="2025-06-03T23:00:00"),
     *         )
     *     ),
     *     @OA\Response(response=201, description="Appointment created"),
     *     @OA\Response(response=409, description="Time slot not available"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request)
    {

        $user = Auth::user();

        $request->validate([
            'healthcare_professional_id' => 'required|exists:healthcare_professionals,id',
            'appointment_start_time' => 'required|date|after:now',
            'appointment_end_time' => 'required|date|after:appointment_start_time',
        ]);

        $start = Carbon::parse($request->appointment_start_time);
        $end = Carbon::parse($request->appointment_end_time);

        $conflict = Appointment::where('healthcare_professional_id', $request->healthcare_professional_id)
            ->where('status', 'booked')
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('appointment_start_time', [$start, $end])
                    ->orWhereBetween('appointment_end_time', [$start, $end])
                    ->orWhere(function ($q) use ($start, $end) {
                        $q->where('appointment_start_time', '<=', $start)
                            ->where('appointment_end_time', '>=', $end);
                    });
            })
            ->exists();

        if ($conflict) {
            return response()->json(['message' => 'The healthcare professional is not available at this time.'], 409);
        }
        $appointment = Appointment::create([
            'user_id' => $user->id,
            'healthcare_professional_id' => $request->healthcare_professional_id,
            'appointment_start_time' => $start,
            'appointment_end_time' => $end,
            'status' => 'booked',
        ]);

        return response()->json(['data' => $appointment], 201);
    }
    /**
     * @OA\Delete(
     *     path="/api/appointments/{id}",
     *     summary="Cancel an appointment",
     *     tags={"Appointments"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the appointment to cancel",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Appointment cancelled"),
     *     @OA\Response(response=403, description="Cannot cancel within 24 hours"),
     *     @OA\Response(response=404, description="Appointment not found or access denied"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function destroy($id)
    {
        $user = Auth::user();

        $appointment = Appointment::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$appointment) {
            return response()->json(['message' => 'Appointment not found or access denied'], 404);
        }

        $now = Carbon::now();
        if ($appointment->appointment_start_time->diffInHours($now, false) < 24 && $appointment->status === 'booked') {
            return response()->json(['message' => 'Cannot cancel appointment less than 24 hours before start time'], 403);
        }

        $appointment->status = 'cancelled';
        $appointment->save();

        return response()->json(['message' => 'Appointment cancelled']);
    }

    /**
     * @OA\Patch(
     *     path="/api/appointments/{id}/complete",
     *     summary="Mark an appointment as completed",
     *     tags={"Appointments"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the appointment to mark as completed",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Appointment marked as completed"),
     *     @OA\Response(response=400, description="Only booked appointments can be marked as completed"),
     *     @OA\Response(response=404, description="Appointment not found or access denied"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function markAsCompleted($id)
    {
        $user = Auth::user();

        $appointment = Appointment::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$appointment) {
            return response()->json(['message' => 'Appointment not found or access denied'], 404);
        }

        if ($appointment->status !== 'booked') {
            return response()->json(['message' => 'Only booked appointments can be marked as completed'], 400);
        }

        $appointment->status = 'completed';
        $appointment->save();

        return response()->json(['message' => 'Appointment marked as completed']);
    }
}
