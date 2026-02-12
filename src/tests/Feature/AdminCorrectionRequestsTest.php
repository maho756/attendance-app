<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceCorrectionRequest;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdminCorrectionRequestsTest extends TestCase
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

    private function normalUser(string $name, string $email): User
    {
        return User::factory()->create([
            'name' => $name,
            'email' => $email,
            'email_verified_at' => now(),
            'is_admin' => false,
        ]);
    }

    public function test_承認待ちに全ユーザーの未承認の修正申請が表示される()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 6, 9, 0, 0, 'Asia/Tokyo'));
        $date = Carbon::now()->toDateString();

        $admin = $this->admin();

        $u1 = $this->normalUser('ユーザー1', 'u1@example.com');
        $u2 = $this->normalUser('ユーザー2', 'u2@example.com');

        $a1 = Attendance::factory()->create([
            'user_id' => $u1->id,
            'work_date' => $date,
            'clock_in' => Carbon::parse("$date 09:00:00"),
            'clock_out' => Carbon::parse("$date 18:00:00"),
        ]);

        $a2 = Attendance::factory()->create([
            'user_id' => $u2->id,
            'work_date' => $date,
            'clock_in' => Carbon::parse("$date 10:00:00"),
            'clock_out' => Carbon::parse("$date 19:00:00"),
        ]);

        $note1 = 'PENDING_NOTE_1';
        $note2 = 'PENDING_NOTE_2';

        AttendanceCorrectionRequest::create([
            'attendance_id' => $a1->id,
            'user_id' => $u1->id,
            'status' => 'pending',
            'requested_clock_in' => Carbon::parse("$date 09:05:00"),
            'requested_clock_out' => Carbon::parse("$date 18:10:00"),
            'requested_note' => $note1,
        ]);

        AttendanceCorrectionRequest::create([
            'attendance_id' => $a2->id,
            'user_id' => $u2->id,
            'status' => 'pending',
            'requested_clock_in' => Carbon::parse("$date 10:05:00"),
            'requested_clock_out' => Carbon::parse("$date 19:10:00"),
            'requested_note' => $note2,
        ]);

        $res = $this->actingAs($admin)->get(route('stamp_correction_request.list', [
            'status' => 'pending',
        ]));
        $res->assertStatus(200);

        $res->assertSee($note1);
        $res->assertSee($note2);
        $res->assertSee('ユーザー1');
        $res->assertSee('ユーザー2');
    }

    public function test_承認済みに全ユーザーの承認済みの修正申請が表示される()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 6, 9, 0, 0, 'Asia/Tokyo'));
        $date = Carbon::now()->toDateString();

        $admin = $this->admin();

        $u1 = $this->normalUser('ユーザーA', 'ua@example.com');
        $u2 = $this->normalUser('ユーザーB', 'ub@example.com');

        $a1 = Attendance::factory()->create(['user_id' => $u1->id, 'work_date' => $date]);
        $a2 = Attendance::factory()->create(['user_id' => $u2->id, 'work_date' => $date]);

        $note1 = 'APPROVED_NOTE_1';
        $note2 = 'APPROVED_NOTE_2';

        AttendanceCorrectionRequest::create([
            'attendance_id' => $a1->id,
            'user_id' => $u1->id,
            'status' => 'approved',
            'requested_clock_in' => Carbon::parse("$date 09:00:00"),
            'requested_clock_out' => Carbon::parse("$date 18:00:00"),
            'requested_note' => $note1,
        ]);

        AttendanceCorrectionRequest::create([
            'attendance_id' => $a2->id,
            'user_id' => $u2->id,
            'status' => 'approved',
            'requested_clock_in' => Carbon::parse("$date 10:00:00"),
            'requested_clock_out' => Carbon::parse("$date 19:00:00"),
            'requested_note' => $note2,
        ]);

        $res = $this->actingAs($admin)->get(route('stamp_correction_request.list', [
            'status' => 'approved',
        ]));
        $res->assertStatus(200);

        $res->assertSee($note1);
        $res->assertSee($note2);
        $res->assertSee('ユーザーA');
        $res->assertSee('ユーザーB');
    }

    public function test_修正申請の詳細内容が正しく表示されている()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 6, 9, 0, 0, 'Asia/Tokyo'));
        $date = Carbon::now()->toDateString();

        $admin = $this->admin();

        $u = $this->normalUser('詳細ユーザー', 'detail@example.com');
        $attendance = Attendance::factory()->create([
            'user_id' => $u->id,
            'work_date' => $date,
            'clock_in' => Carbon::parse("$date 09:00:00"),
            'clock_out' => Carbon::parse("$date 18:00:00"),
        ]);

        $note = 'DETAIL_NOTE';
        $req = AttendanceCorrectionRequest::create([
            'attendance_id' => $attendance->id,
            'user_id' => $u->id,
            'status' => 'pending',
            'requested_clock_in' => Carbon::parse("$date 09:30:00"),
            'requested_clock_out' => Carbon::parse("$date 18:30:00"),
            'requested_note' => $note,
        ]);

        $res = $this->actingAs($admin)->get(route('admin.requests.show', [
            'attendance_correct_request_id' => $req->id,
        ]));
        $res->assertStatus(200);

        $res->assertSee('詳細ユーザー');
        $res->assertSee('09:30');
        $res->assertSee('18:30');
        $res->assertSee($note);
    }

    public function test_修正申請の承認処理が正しく行われ_勤怠情報が更新される()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 6, 9, 0, 0, 'Asia/Tokyo'));
        $date = Carbon::now()->toDateString();

        $admin = $this->admin();

        $u = $this->normalUser('承認ユーザー', 'approve@example.com');

        $attendance = Attendance::factory()->create([
            'user_id' => $u->id,
            'work_date' => $date,
            'clock_in' => Carbon::parse("$date 09:00:00"),
            'clock_out' => Carbon::parse("$date 18:00:00"),
        ]);

        $req = AttendanceCorrectionRequest::create([
            'attendance_id' => $attendance->id,
            'user_id' => $u->id,
            'status' => 'pending',
            'requested_clock_in' => Carbon::parse("$date 09:15:00"),
            'requested_clock_out' => Carbon::parse("$date 18:20:00"),
            'requested_note' => 'APPROVE_NOTE',
        ]);

        $res = $this->actingAs($admin)->post(route('admin.requests.approve', [
            'attendance_correct_request_id' => $req->id,
        ]));

        $res->assertStatus(302);
        $this->assertDatabaseHas('attendance_correction_requests', [
            'id' => $req->id,
            'status' => 'approved',
        ]);

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'clock_in' => Carbon::parse("$date 09:15:00"),
            'clock_out' => Carbon::parse("$date 18:20:00"),
        ]);
    }
}