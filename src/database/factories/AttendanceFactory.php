<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    public function definition()
    {
        $date = Carbon::parse($this->faker->date())->format('Y-m-d');

        return [
            'user_id' => User::factory(),
            'work_date' => $date,
            'clock_in' => $date . ' 09:00:00',
            'clock_out' => $date . ' 18:00:00',
        ];
    }
}