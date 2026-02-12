<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdminStaffAndAttendancesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'name' => '管理者',
            'email_verified_at' => now(),
            'is_admin' => true,
        ]);
    }

    public function test_スタッフ一覧に全一般ユーザーの氏名とメールアドレスが表示される()
    {
        $admin = $this->admin();

        $u1 = User::factory()->create([
            'name' => '一般ユーザー1',
            'email' => 'user1@example.com',
            'email_verified_at' => now(),
            'is_admin' => false,
        ]);

        $u2 = User::factory()->create([
            'name' => '一般ユーザー2',
            'email' => 'user2@example.com',
            'email_verified_at' => now(),
            'is_admin' => false,
        ]);

        $res = $this->actingAs($admin)->get(route('admin.staff.index'));
        $res->assertStatus(200);

        $res->assertSee('一般ユーザー1');
        $res->assertSee('user1@example.com');

        $res->assertSee('一般ユーザー2');
        $res->assertSee('user2@example.com');

    }

    public function test_選択したユーザーの勤怠一覧に勤怠情報が正確に表示される()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 6, 9, 0, 0, 'Asia/Tokyo'));
        $admin = $this->admin();

        $staff = User::factory()->create([
            'name' => 'スタッフA',
            'email_verified_at' => now(),
            'is_admin' => false,
        ]);

        $d1 = Carbon::create(2026, 2, 1, 0, 0, 0, 'Asia/Tokyo')->toDateString();
        $d2 = Carbon::create(2026, 2, 2, 0, 0, 0, 'Asia/Tokyo')->toDateString();

        Attendance::factory()->create([
            'user_id' => $staff->id,
            'work_date' => $d1,
            'clock_in' => Carbon::parse("$d1 09:00:00"),
            'clock_out' => Carbon::parse("$d1 18:00:00"),
        ]);

        Attendance::factory()->create([
            'user_id' => $staff->id,
            'work_date' => $d2,
            'clock_in' => Carbon::parse("$d2 10:00:00"),
            'clock_out' => Carbon::parse("$d2 19:00:00"),
        ]);

        $res = $this->actingAs($admin)->get(route('admin.staff.attendances', ['id' => $staff->id]));
        $res->assertStatus(200);

        $res->assertSee('スタッフA');
        $res->assertSee('09:00');
        $res->assertSee('18:00');
        $res->assertSee('10:00');
        $res->assertSee('19:00');
    }

    public function test_前月を指定すると前月の勤怠が表示される()
    {
        $admin = $this->admin();

        $staff = User::factory()->create([
            'name' => 'スタッフ前月',
            'email_verified_at' => now(),
            'is_admin' => false,
        ]);

        $prevMonth = '2026-01';
        $targetDate = '2026-01-15';

        Attendance::factory()->create([
            'user_id' => $staff->id,
            'work_date' => $targetDate,
            'clock_in' => Carbon::parse("$targetDate 09:30:00"),
            'clock_out' => Carbon::parse("$targetDate 18:30:00"),
        ]);

        $res = $this->actingAs($admin)->get(route('admin.staff.attendances', ['id' => $staff->id, 'month' => $prevMonth]));
        $res->assertStatus(200);

        $res->assertSee('09:30');
        $res->assertSee('18:30');

    }

    public function test_翌月を指定すると翌月の勤怠が表示される()
    {
        $admin = $this->admin();

        $staff = User::factory()->create([
            'name' => 'スタッフ翌月',
            'email_verified_at' => now(),
            'is_admin' => false,
        ]);

        $nextMonth = '2026-03';
        $targetDate = '2026-03-10';

        Attendance::factory()->create([
            'user_id' => $staff->id,
            'work_date' => $targetDate,
            'clock_in' => Carbon::parse("$targetDate 11:00:00"),
            'clock_out' => Carbon::parse("$targetDate 20:00:00"),
        ]);

        $res = $this->actingAs($admin)->get(route('admin.staff.attendances', ['id' => $staff->id, 'month' => $nextMonth]));
        $res->assertStatus(200);

        $res->assertSee('11:00');
        $res->assertSee('20:00');

    }

    public function test_勤怠一覧の詳細リンクから勤怠詳細画面に遷移できる()
    {
        $admin = $this->admin();

        $staff = User::factory()->create([
            'name' => 'スタッフ詳細',
            'email_verified_at' => now(),
            'is_admin' => false,
        ]);

        $date = '2026-02-05';

        $attendance = Attendance::factory()->create([
            'user_id' => $staff->id,
            'work_date' => $date,
            'clock_in' => Carbon::parse("$date 09:00:00"),
            'clock_out' => Carbon::parse("$date 18:00:00"),
        ]);

        $list = $this->actingAs($admin)->get(route('admin.staff.attendances', ['id' => $staff->id]));
        $list->assertStatus(200);

        $detailUrl = route('admin.attendance.detail', ['id' => $attendance->id]);
        $list->assertSee('href="'.$detailUrl.'"', false);

        $this->actingAs($admin)->get($detailUrl)->assertStatus(200);
    }
}