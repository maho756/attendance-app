<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_勤怠詳細画面の「名前」がログインユーザーの氏名になっている()
    {
        $user = User::factory()->create([
            'name' => 'テスト太郎',
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2026-01-10',
        ]);

        $response = $this->actingAs($user)
            ->get(route('attendance.detail', ['id' => $attendance->id]));

        $response->assertStatus(200);

        $response->assertSee('テスト太郎');
    }

    public function test_勤怠詳細画面の「日付」が選択した日付になっている()
    {
        $user = User::factory()->create([
            'name' => 'テスト太郎',
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2026-01-10',
        ]);

        $response = $this->actingAs($user)
            ->get(route('attendance.detail', ['id' => $attendance->id]));

        $response->assertStatus(200);

        $response->assertSee('2026年');
        $response->assertSee('1月10日');
    }

    public function test_「出勤・退勤」にて記されている時間がログインユーザーの打刻と一致している()
    {
        $user = User::factory()->create([
            'name' => 'テスト太郎',
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2026-01-10',
            'clock_in' => '2026-01-10 09:00:00',
            'clock_out' => '2026-01-10 18:00:00'
        ]);

        $response = $this->actingAs($user)
            ->get(route('attendance.detail', ['id' => $attendance->id]));

        $response->assertStatus(200);

        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    public function test_休憩にて記されている時間がログインユーザーの打刻と一致している()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $other = User::factory()->create(['email_verified_at' => now()]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2026-01-10',
        ]);

        $otherAttendance = Attendance::factory()->create([
            'user_id' => $other->id,
            'work_date' => '2026-01-10',
        ]);

        // ログインユーザーの休憩
        BreakTime::create([
            'attendance_id' => $attendance->id,
            'start_time' => '2026-01-10 12:00:00',
            'end_time'   => '2026-01-10 13:00:00',
        ]);

        // 他人の休憩（これが表示されたらNG）
        BreakTime::create([
            'attendance_id' => $otherAttendance->id,
            'start_time' => '2026-01-10 15:00:00',
            'end_time'   => '2026-01-10 15:30:00',
        ]);

        $res = $this->actingAs($user)->get(route('attendance.detail', ['id' => $attendance->id]));

        $res->assertStatus(200);
        $res->assertSee('12:00');
        $res->assertSee('13:00');

        // 他人の休憩は見えない
        $res->assertDontSee('15:00');
        $res->assertDontSee('15:30');
    }
}
