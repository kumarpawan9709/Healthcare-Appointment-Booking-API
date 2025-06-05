<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;
use App\Models\User;
use App\Models\Appointment;
use App\Models\HealthcareProfessional;
use Carbon\Carbon;

class AppointmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_book_appointment()
    {
        $user = User::factory()->create();
        $doctor = HealthcareProfessional::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/appointments', [
            'healthcare_professional_id' => $doctor->id,
            'appointment_start_time' => Carbon::now()->addDays(2)->format('Y-m-d H:i:s'),
            'appointment_end_time' => Carbon::now()->addDays(2)->addHour()->format('Y-m-d H:i:s'),
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('appointments', ['user_id' => $user->id]);
    }

    public function test_conflicting_appointment_returns_error()
    {
        $user = User::factory()->create();
        $doctor = HealthcareProfessional::factory()->create();

        Appointment::create([
            'user_id' => $user->id,
            'healthcare_professional_id' => $doctor->id,
            'appointment_start_time' => Carbon::now()->addDays(1),
            'appointment_end_time' => Carbon::now()->addDays(1)->addHour(),
            'status' => 'booked',
        ]);

        $response = $this->actingAs($user)->postJson('/api/appointments', [
            'healthcare_professional_id' => $doctor->id,
            'appointment_start_time' => Carbon::now()->addDays(1)->format('Y-m-d H:i:s'),
            'appointment_end_time' => Carbon::now()->addDays(1)->addHour()->format('Y-m-d H:i:s'),
        ]);

        $response->assertStatus(409);
    }

    public function test_user_cannot_cancel_within_24_hours()
    {
        $user = User::factory()->create();
        $appointment = Appointment::factory()->create([
            'user_id' => $user->id,
            'appointment_start_time' => Carbon::now()->addHours(23),
            'appointment_end_time' => Carbon::now()->addHours(24),
            'status' => 'booked',
        ]);

        $response = $this->actingAs($user)->deleteJson("/api/appointments/{$appointment->id}");
        $response->assertStatus(403)
            ->assertJson(['message' => 'Cannot cancel appointment less than 24 hours before start time']);
    }
}
