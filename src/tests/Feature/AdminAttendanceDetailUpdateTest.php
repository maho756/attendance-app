<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdminAttendanceDetailUpdateTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        return User::factory()->create([
            'email_verified_at' => now(),
            'is_admin' => true,
        ]);
    }

    public function test_勤怠詳細画面に表示されるデータが選択したものになっている()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 6, 9, 0, 0, 'Asia/Tokyo'));
        $date = Carbon::now()->toDateString();

        $admin = $this->adminUser();

        $user = User::factory()->create([
            'name' => '対象ユーザー',
            'email_verified_at' => now(),
            'is_admin' => false,
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => $date,
            'clock_in' => Carbon::parse("$date 09:10:00"),
            'clock_out' => Carbon::parse("$date 18:05:00"),
        ]);

        $res = $this->actingAs($admin)
            ->get(route('admin.attendance.detail', ['id' => $attendance->id]));

        $res->assertStatus(200);

        $res->assertSee('対象ユーザー');
        $res->assertSee('09:10');
        $res->assertSee('18:05');
    }

    public function test_出勤時間が退勤時間より後の場合_エラーメッセージが表示される()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 6, 9, 0, 0, 'Asia/Tokyo'));
        $date = Carbon::now()->toDateString();

        $admin = $this->adminUser();

        $user = User::factory()->create([
            'email_verified_at' => now(),
            'is_admin' => false,
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => $date,
            'clock_in' => Carbon::parse("$date 09:00:00"),
            'clock_out' => Carbon::parse("$date 18:00:00"),
        ]);

        $res = $this->actingAs($admin)
            ->from(route('admin.attendance.detail', ['id' => $attendance->id]))
            ->post(route('admin.attendance.update', ['id' => $attendance->id]), [
                'clock_in' => '19:00',
                'clock_out' => '18:00',
                'note' => 'テスト',
                'breaks' => [],
            ]);

        $res->assertSessionHasErrors([
            'clock_in' => '出勤時間もしくは退勤時間が不適切な値です',
        ]);

        $res->assertRedirect(route('admin.attendance.detail', ['id' => $attendance->id]));
    }

    public function test_休憩開始が退勤時間より後の場合_エラーメッセージが表示される()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 6, 9, 0, 0, 'Asia/Tokyo'));
        $date = Carbon::now()->toDateString();

        $admin = $this->adminUser();

        $user = User::factory()->create([
            'email_verified_at' => now(),
            'is_admin' => false,
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => $date,
            'clock_in' => Carbon::parse("$date 09:00:00"),
            'clock_out' => Carbon::parse("$date 18:00:00"),
        ]);

        $res = $this->actingAs($admin)
            ->from(route('admin.attendance.detail', ['id' => $attendance->id]))
            ->post(route('admin.attendance.update', ['id' => $attendance->id]), [
                'clock_in' => '09:00',
                'clock_out' => '18:00',
                'note' => 'テスト',
                'breaks' => [
                    ['start' => '19:00', 'end' => '19:10'], // startが退勤より後
                ],
            ]);

        $res->assertSessionHasErrors([
            'breaks.0.start' => '休憩時間が不適切な値です',
        ]);

        $res->assertRedirect(route('admin.attendance.detail', ['id' => $attendance->id]));
    }

    public function test_休憩終了が退勤時間より後の場合_エラーメッセージが表示される()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 6, 9, 0, 0, 'Asia/Tokyo'));
        $date = Carbon::now()->toDateString();

        $admin = $this->adminUser();

        $user = User::factory()->create([
            'email_verified_at' => now(),
            'is_admin' => false,
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => $date,
            'clock_in' => Carbon::parse("$date 09:00:00"),
            'clock_out' => Carbon::parse("$date 18:00:00"),
        ]);

        $res = $this->actingAs($admin)
            ->from(route('admin.attendance.detail', ['id' => $attendance->id]))
            ->post(route('admin.attendance.update', ['id' => $attendance->id]), [
                'clock_in' => '09:00',
                'clock_out' => '18:00',
                'note' => 'テスト',
                'breaks' => [
                    ['start' => '17:50', 'end' => '19:10'],
                ],
            ]);

        $res->assertSessionHasErrors([
            'breaks.0.end' => '休憩時間もしくは退勤時間が不適切な値です',
        ]);

        $res->assertRedirect(route('admin.attendance.detail', ['id' => $attendance->id]));
    }

    public function test_備考が未入力の場合_備考を記入してくださいが表示される()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 6, 9, 0, 0, 'Asia/Tokyo'));
        $date = Carbon::now()->toDateString();

        $admin = $this->adminUser();

        $user = User::factory()->create([
            'email_verified_at' => now(),
            'is_admin' => false,
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => $date,
            'clock_in' => Carbon::parse("$date 09:00:00"),
            'clock_out' => Carbon::parse("$date 18:00:00"),
        ]);

        $res = $this->actingAs($admin)
            ->from(route('admin.attendance.detail', ['id' => $attendance->id]))
            ->post(route('admin.attendance.update', ['id' => $attendance->id]), [
                'clock_in' => '09:00',
                'clock_out' => '18:00',
                'note' => '',
                'breaks' => [],
            ]);

        $res->assertSessionHasErrors([
            'note' => '備考を記入してください',
        ]);

        $res->assertRedirect(route('admin.attendance.detail', ['id' => $attendance->id]));
    }
}