<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AttendanceClockOutTest extends TestCase
{
    use RefreshDatabase;

    public function test_退勤ボタンが表示され_退勤後にステータスが退勤済になる()
    {
        $fixedNow = Carbon::create(2026, 1, 3, 18, 0, 0, 'Asia/Tokyo')->locale('ja');
        Carbon::setTestNow($fixedNow);

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        Attendance::create([
            'user_id'   => $user->id,
            'work_date' => $fixedNow->toDateString(),
            'clock_in'  => $fixedNow->copy()->subHours(8),
            'clock_out' => null,
        ]);

        $before = $this->actingAs($user)->get(route('attendance.index'));
        $before->assertStatus(200);

        $before->assertSee('退勤');

        $clockOut = $this->actingAs($user)->post(route('attendance.clockOut'));
        $clockOut->assertStatus(302);
        $clockOut->assertRedirect(route('attendance.index'));

        $after = $this->actingAs($user)->get(route('attendance.index'));
        $after->assertStatus(200);
        $after->assertSee('退勤済');

        $this->assertDatabaseHas('attendances', [
            'user_id'   => $user->id,
            'work_date' => $fixedNow->toDateString(),
            'clock_out' => $fixedNow->toDateTimeString(),
        ]);

        Carbon::setTestNow();
    }

    public function test_退勤時刻が勤怠一覧画面で確認できる()
    {
        $clockInAt = Carbon::create(2026, 1, 3, 9, 0, 0, 'Asia/Tokyo')->locale('ja');
        Carbon::setTestNow($clockInAt);

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)->post(route('attendance.clockIn'))
            ->assertStatus(302)
            ->assertRedirect(route('attendance.index'));

        $clockOutAt = $clockInAt->copy()->addHours(9);
        Carbon::setTestNow($clockOutAt);

        $this->actingAs($user)->post(route('attendance.clockOut'))
            ->assertStatus(302)
            ->assertRedirect(route('attendance.index'));

        $list = $this->actingAs($user)->get(
            route('attendance.list', ['month' => $clockInAt->format('Y-m')])
        );
        $list->assertStatus(200);

        $list->assertSee($clockOutAt->format('H:i'));

        Carbon::setTestNow();
    }
}