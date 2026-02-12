<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Attendance;
use Carbon\Carbon;
use App\Models\User;

class AttendanceClockInTest extends TestCase
{
    use RefreshDatabase;

    public function test_出勤ボタンが正しく機能する()
    {
        $fixedNow = Carbon::create(2026, 1, 3, 18, 45, 0, 'Asia/Tokyo');
        Carbon::setTestNow($fixedNow);

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $before = $this->actingAs($user)->get(route('attendance.index'));
        $before->assertStatus(200);

        $before->assertSee('出勤');

        $clockIn = $this->actingAs($user)->post(route('attendance.clockIn'));
        $clockIn->assertStatus(302);
        $clockIn->assertRedirect(route('attendance.index'));

        $after = $this->actingAs($user)->get(route('attendance.index'));
        $after->assertStatus(200);

        $after->assertSee('出勤中');

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'work_date' => $fixedNow->toDateString(),
        ]);

        Carbon::setTestNow();
    }

    public function test_出勤は一日一回のみできる()
    {
        $fixedNow = Carbon::create(2026, 1, 3, 18, 45, 0, 'Asia/Tokyo');
        Carbon::setTestNow($fixedNow);

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => $fixedNow->toDateString(),
            'clock_in' => $fixedNow->copy()->subHours(7),
            'clock_out' => $fixedNow->copy()->subHour(),
        ]);

        $response = $this->actingAs($user)->get(route('attendance.index'));

        $response->assertStatus(200);

        $response->assertSee('退勤済');

        $response->assertDontSee('出勤');

        Carbon::setTestNow();
    }

    public function test_出勤時刻が勤怠一覧画面で確認できる()
    {
        $fixedNow = Carbon::create(2026, 1, 3, 18, 45, 0, 'Asia/Tokyo');
        Carbon::setTestNow($fixedNow);

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $clockIn = $this->actingAs($user)->post(route('attendance.clockIn'));

        $clockIn->assertStatus(302);

        $clockIn->assertRedirect(route('attendance.index'));

        $list = $this->actingAs($user)->get(route('attendance.list'));
        $list->assertStatus(200);

        $list->assertSee($fixedNow->format('H:i'));

        Carbon::setTestNow();
    }
}
