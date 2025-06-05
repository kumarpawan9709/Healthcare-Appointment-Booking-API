<?php

namespace Database\Factories;

use App\Models\HealthcareProfessional;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Appointment>
 */
class AppointmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user = User::inRandomOrder()->first() ?? User::factory()->create();
        $professional = HealthcareProfessional::inRandomOrder()->first() ?? HealthcareProfessional::factory()->create();

        $start = Carbon::now()->addDays(rand(1, 30))->setHour(rand(9, 16))->setMinute(0);
        $end = (clone $start)->addMinutes(30);

        return [
            'user_id' => $user->id,
            'healthcare_professional_id' => $professional->id,
            'appointment_start_time' => $start,
            'appointment_end_time' => $end,
            'status' => 'booked',
        ];
    }
}
