<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Attendance;
use Carbon\Carbon;
use App\Models\User;

class AttendanceBreakTimeTest extends TestCase
{
    use RefreshDatabase;

    public function test_休憩ボタンが正しく機能する()
    {
        $fixedNow = Carbon::create(2026, 1, 3, 18, 45, 0, 'Asia/Tokyo');
        Carbon::setTestNow($fixedNow);

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => $fixedNow->toDateString(),
            'clock_in' => $fixedNow->copy()->subHour(3),
            'clock_out' => null,
        ]);

        $before = $this->actingAs($user)->get(route('attendance.index'));
        $before->assertStatus(200);

        $before->assertSee('休憩入');

        $breakStart = $this->actingAs($user)->post(route('attendance.breakStart'));
        $breakStart->assertStatus(302);
        $breakStart->assertRedirect(route('attendance.index'));

        $after = $this->actingAs($user)->get(route('attendance.index'));
        $after->assertStatus(200);

        $after->assertSee('休憩中');

        $this->assertDatabaseHas('break_times', [
            'start_time' => $fixedNow->toDateTimeString(),
            'end_time' => null,
        ]);

        Carbon::setTestNow();
    }

    public function test_休憩は一日に何回でもできる()
    {
        $fixedNow = Carbon::create(2026, 1, 3, 18, 45, 0, 'Asia/Tokyo');
        Carbon::setTestNow($fixedNow);

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => $fixedNow->toDateString(),
            'clock_in' => $fixedNow->copy()->subHours(3),
            'clock_out' => null,
        ]);

        $this->actingAs($user)->post(route('attendance.breakStart'))
            ->assertStatus(302)
            ->assertRedirect(route('attendance.index'));

        Carbon::setTestNow($fixedNow->copy()->addMinutes(10));


        $this->actingAs($user)->post(route('attendance.breakEnd'))
            ->assertStatus(302)
            ->assertRedirect(route('attendance.index'));

        $response = $this->actingAs($user)->get(route('attendance.index'));

        $response->assertStatus(200);

        $response->assertSee('休憩入');

        Carbon::setTestNow();
    }

    public function test_休憩戻ボタンが正しく機能する()
    {
        $fixedNow = Carbon::create(2026, 1, 3, 18, 45, 0, 'Asia/Tokyo');
        Carbon::setTestNow($fixedNow);

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => $fixedNow->toDateString(),
            'clock_in' => $fixedNow->copy()->subHour(3),
            'clock_out' => null,
        ]);

        $this->actingAs($user)->post(route('attendance.breakStart'))
            ->assertStatus(302)
            ->assertRedirect(route('attendance.index'));

        $duringBreak = $this->actingAs($user)->get(route('attendance.index'));
        $duringBreak->assertStatus(200);
        $duringBreak->assertSee('休憩戻');

        Carbon::setTestNow($fixedNow->copy()->addMinutes(10));

        $this->actingAs($user)->post(route('attendance.breakEnd'))
            ->assertStatus(302)
            ->assertRedirect(route('attendance.index'));

        $after = $this->actingAs($user)->get(route('attendance.index'));
        $after->assertStatus(200);
        $after->assertSee('出勤中');

        Carbon::setTestNow();
    }

    public function test_休憩戻は一日に何回でもできる()
    {
        $fixedNow = Carbon::create(2026, 1, 3, 18, 45, 0, 'Asia/Tokyo');
        Carbon::setTestNow($fixedNow);

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => $fixedNow->toDateString(),
            'clock_in' => $fixedNow->copy()->subHour(3),
            'clock_out' => null,
        ]);

        $this->actingAs($user)->post(route('attendance.breakStart'))
            ->assertStatus(302)
            ->assertRedirect(route('attendance.index'));

        Carbon::setTestNow($fixedNow->copy()->addMinutes(10));

        $this->actingAs($user)->post(route('attendance.breakEnd'))
            ->assertStatus(302)
            ->assertRedirect(route('attendance.index'));

        Carbon::setTestNow($fixedNow->copy()->addMinutes(10));

        $this->actingAs($user)->post(route('attendance.breakStart'))
            ->assertStatus(302)
            ->assertRedirect(route('attendance.index'));

        $response = $this->actingAs($user)->get(route('attendance.index'));
        $response->assertStatus(200);

        $response->assertSee('休憩戻');

        Carbon::setTestNow();
    }

    public function test_休憩時間が勤怠一覧画面で確認できる()
    {
        $start = Carbon::create(2026, 1, 3, 9, 0, 0, 'Asia/Tokyo');
        Carbon::setTestNow($start);

        $user = User::factory()->create(['email_verified_at' => now()]);

        $attendance = Attendance::create([
            'user_id'   => $user->id,
            'work_date' => $start->toDateString(),
            'clock_in'  => $start,
            'clock_out' => null,
        ]);

        $breakStart = $start->copy()->addHours(3);
        Carbon::setTestNow($breakStart);
        $this->actingAs($user)->post(route('attendance.breakStart'))->assertStatus(302);

        $breakEnd = $breakStart->copy()->addMinutes(10);
        Carbon::setTestNow($breakEnd);
        $this->actingAs($user)->post(route('attendance.breakEnd'))->assertStatus(302);

        $clockOut = $start->copy()->addHours(9);
        Carbon::setTestNow($clockOut);
        $this->actingAs($user)->post(route('attendance.clockOut'))->assertStatus(302);

        $list = $this->actingAs($user)->get(
            route('attendance.list', ['month' => $start->format('Y-m')])
        );
        $list->assertStatus(200);

        $list->assertSee('0:10');

        Carbon::setTestNow();
    }
}