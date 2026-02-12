<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdminAttendanceListByDateTest extends TestCase
{
    use RefreshDatabase;

    public function test_管理者は当日の全ユーザーの勤怠を正確に確認できる()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 6, 9, 0, 0, 'Asia/Tokyo'));

        $today = Carbon::now()->toDateString();

        $admin = User::factory()->create([
            'name' => '管理者',
            'email_verified_at' => now(),
            'is_admin' => true,
        ]);

        $userA = User::factory()->create([
            'name' => 'ユーザーA',
            'email_verified_at' => now(),
            'is_admin' => false,
        ]);

        $userB = User::factory()->create([
            'name' => 'ユーザーB',
            'email_verified_at' => now(),
            'is_admin' => false,
        ]);

        Attendance::factory()->create([
            'user_id' => $userA->id,
            'work_date' => $today,
            'clock_in' => Carbon::parse($today.' 09:10:00'),
            'clock_out' => Carbon::parse($today.' 18:05:00'),
        ]);

        Attendance::factory()->create([
            'user_id' => $userB->id,
            'work_date' => $today,
            'clock_in' => Carbon::parse($today.' 10:00:00'),
            'clock_out' => Carbon::parse($today.' 19:00:00'),
        ]);

        $res = $this->actingAs($admin)->get(route('admin.attendance.list'));
        $res->assertStatus(200);

        $res->assertSee('ユーザーA');
        $res->assertSee('09:10');
        $res->assertSee('18:05');

        $res->assertSee('ユーザーB');
        $res->assertSee('10:00');
        $res->assertSee('19:00');
    }

    public function test_遷移した際に現在の日付が表示される()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 6, 9, 0, 0, 'Asia/Tokyo'));
        $today = Carbon::now()->toDateString();
        $todayLabel = Carbon::parse($today)->format('Y年n月j日');

        $admin = User::factory()->create([
            'email_verified_at' => now(),
            'is_admin' => true,
        ]);

        $res = $this->actingAs($admin)->get(route('admin.attendance.list'));
        $res->assertStatus(200);

        $res->assertSee($todayLabel);
    }

    public function test_前日を押下した時に前の日の勤怠情報が表示される()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 6, 9, 0, 0, 'Asia/Tokyo'));

        $today = Carbon::now()->toDateString();
        $yesterday = Carbon::now()->copy()->subDay()->toDateString();
        $yesterdayLabel = Carbon::parse($yesterday)->format('Y年n月j日');
        $todayLabel = Carbon::parse($today)->format('Y年n月j日');

        $admin = User::factory()->create([
            'email_verified_at' => now(),
            'is_admin' => true,
        ]);

        $user = User::factory()->create([
            'name' => '前日ユーザー',
            'email_verified_at' => now(),
            'is_admin' => false,
        ]);

        Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => $yesterday,
            'clock_in' => Carbon::parse($yesterday.' 08:30:00'),
            'clock_out' => Carbon::parse($yesterday.' 17:00:00'),
        ]);

        $res = $this->actingAs($admin)->get(route('admin.attendance.list', [
            'date' => $yesterday,
        ]));
        $res->assertStatus(200);

        $res->assertSee($yesterdayLabel);
        $res->assertSee('前日ユーザー');
        $res->assertSee('08:30');
        $res->assertSee('17:00');

        $res->assertDontSee($todayLabel);
    }

    public function test_翌日を押下した時に次の日の勤怠情報が表示される()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 6, 9, 0, 0, 'Asia/Tokyo'));

        $today = Carbon::now()->toDateString();
        $tomorrow = Carbon::now()->copy()->addDay()->toDateString();
        $tomorrowLabel = Carbon::parse($tomorrow)->format('Y年n月j日');
        $todayLabel = Carbon::parse($today)->format('Y年n月j日');

        $admin = User::factory()->create([
            'email_verified_at' => now(),
            'is_admin' => true,
        ]);

        $user = User::factory()->create([
            'name' => '翌日ユーザー',
            'email_verified_at' => now(),
            'is_admin' => false,
        ]);

        Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => $tomorrow,
            'clock_in' => Carbon::parse($tomorrow.' 11:00:00'),
            'clock_out' => Carbon::parse($tomorrow.' 20:00:00'),
        ]);

        $res = $this->actingAs($admin)->get(route('admin.attendance.list', [
            'date' => $tomorrow,
        ]));
        $res->assertStatus(200);

        $res->assertSee($tomorrowLabel);
        $res->assertSee('翌日ユーザー');
        $res->assertSee('11:00');
        $res->assertSee('20:00');

        $res->assertDontSee($todayLabel);
    }
}
