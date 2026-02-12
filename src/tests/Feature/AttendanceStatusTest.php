<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;

class AttendanceStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_勤務外の場合、勤怠ステータスが正しく表示される()
    {
        $fixedNow = Carbon::create(2026, 1, 3, 18, 45, 0, 'Asia/Tokyo')->locale('ja');
        Carbon::setTestNow($fixedNow);

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);

        $response->assertSee('勤務外');

        Carbon::setTestNow();
    }

    public function test_出勤中の場合、勤怠ステータスが正しく表示される()
    {
        $fixedNow = Carbon::create(2026, 1, 3, 18, 45, 0, 'Asia/Tokyo')->locale('ja');
        Carbon::setTestNow($fixedNow);

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => $fixedNow->toDateString(),
            'clock_in' => $fixedNow->copy()->subHour(),
            'clock_out' => null,
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);

        $response->assertSee('出勤中');

        Carbon::setTestNow();
    }

    public function test_休憩中の場合、勤怠ステータスが正しく表示される()
    {
        $fixedNow = Carbon::create(2026, 1, 3, 18, 45, 0, 'Asia/Tokyo')->locale('ja');
        Carbon::setTestNow($fixedNow);

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => $fixedNow->toDateString(),
            'clock_in' => $fixedNow->copy()->subHour(),
            'clock_out' => null,
        ]);

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'start_time' => $fixedNow,
            'end_time' => null,
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);

        $response->assertSee('休憩中');

        Carbon::setTestNow();
    }

    public function test_退勤済の場合、勤怠ステータスが正しく表示される()
    {
        $fixedNow = Carbon::create(2026, 1, 3, 18, 45, 0, 'Asia/Tokyo')->locale('ja');
        Carbon::setTestNow($fixedNow);

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => $fixedNow->toDateString(),
            'clock_in' => $fixedNow->copy()->subHour(),
            'clock_out' => $fixedNow->copy()->addHour(),
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);

        $response->assertSee('退勤済');

        Carbon::setTestNow();
    }
}
