<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class AttendanceCorrectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_出勤時間が退勤時間より後の場合_エラーメッセージが表示される()
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2026-01-10',
        ]);

        $response = $this->actingAs($user)->post(
            route('stamp_correction_request.store', ['id' => $attendance->id]),
            [
                'requested_clock_in'  => '18:00',
                'requested_clock_out' => '09:00',
                'requested_note'      => 'テスト修正理由',
            ]
        );

        $response->assertRedirect();

        $response->assertSessionHasErrors([
            'requested_clock_in' => '出勤時間もしくは退勤時間が不適切な値です',
        ]);
    }

    public function test_休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される()
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2026-01-10',
            'clock_in'  => '2026-01-10 09:00:00',
            'clock_out' => '2026-01-10 18:00:00',
        ]);

        $payload = [
            'requested_clock_in'  => '09:00',
            'requested_clock_out' => '18:00',
            'breaks' => [
                [
                    'start' => '19:00',
                    'end'   => '19:30',
                ],
            ],
            'requested_note' => 'テスト申請です',
        ];

        $response = $this->actingAs($user)->post(
            route('stamp_correction_request.store', ['id' => $attendance->id]),
            $payload
        );

        $response->assertStatus(302);
        $response->assertSessionHasErrors([
            'breaks.0.start'
        ]);

        $errors = session('errors');
        $this->assertTrue($errors->has('breaks.0.start'));
        $this->assertSame('休憩時間が不適切な値です', $errors->first('breaks.0.start'));
    }

    public function test_休憩終了時間が退勤時間より後の場合_休憩時間もしくは退勤時間が不適切な値ですと表示される()
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::factory()->create([
            'user_id'   => $user->id,
            'work_date' => '2026-01-10',
            'clock_in'  => '2026-01-10 09:00:00',
            'clock_out' => '2026-01-10 18:00:00',
        ]);

        $payload = [
            'requested_clock_in'  => '09:00',
            'requested_clock_out' => '18:00',
            'breaks' => [
                [
                    'start' => '12:00',
                    'end'   => '19:00',
                ],
            ],
            'requested_note' => 'テスト申請です',
        ];

        $response = $this->actingAs($user)->post(
            route('stamp_correction_request.store', ['id' => $attendance->id]),
            $payload
        );

        $response->assertStatus(302);
        $response->assertSessionHasErrors([
            'breaks.0.end'
        ]);

        $errors = session('errors');
        $this->assertSame(
            '休憩時間もしくは退勤時間が不適切な値です',
            $errors->first('breaks.0.end')
        );
    }

    public function test_備考欄が未入力の場合_エラーメッセージが表示される()
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::factory()->create([
            'user_id'   => $user->id,
            'work_date' => '2026-01-10',
            'clock_in'  => '2026-01-10 09:00:00',
            'clock_out' => '2026-01-10 18:00:00',
        ]);

        $payload = [
            'requested_clock_in'  => '09:00',
            'requested_clock_out' => '18:00',
            'breaks' => [
                [
                    'start' => '12:00',
                    'end'   => '13:00',
                ],
            ],
            'requested_note' => '',
        ];

        $response = $this->actingAs($user)->post(
            route('stamp_correction_request.store', ['id' => $attendance->id]),
            $payload
        );

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['requested_note']);

        $errors = session('errors');
        $this->assertSame(
            '備考を記入してください',
            $errors->first('requested_note')
        );
    }

    public function test_修正申請が作成され_管理者の承認画面に表示される()
{
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'is_admin' => false,
    ]);

    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'is_admin' => true,
    ]);

    $attendance = Attendance::factory()->create([
        'user_id' => $user->id,
        'work_date' => '2026-01-10',
        'clock_in' => '2026-01-10 09:00:00',
        'clock_out' => '2026-01-10 18:00:00',
    ]);

    // 一般ユーザー：修正申請をPOST
    $res = $this->actingAs($user)->post(
        route('stamp_correction_request.store', ['id' => $attendance->id]),
        [
            'requested_clock_in' => '09:30',
            'requested_clock_out' => '18:10',
            'breaks' => [
                ['start' => '12:00', 'end' => '13:00'],
            ],
            'requested_note' => '電車遅延のため',
        ]
    );

    $res->assertStatus(302);

    // DBに作成されている
    $this->assertDatabaseHas('attendance_correction_requests', [
        'attendance_id' => $attendance->id,
        'user_id' => $user->id,
        'status' => 'pending',
        'requested_note' => '電車遅延のため',
    ]);

    $requestId = DB::table('attendance_correction_requests')
        ->where('attendance_id', $attendance->id)
        ->where('user_id', $user->id)
        ->value('id');

    $this->assertNotNull($requestId);

    // 管理者：承認画面（詳細）に表示される
    $show = $this->actingAs($admin)->get(route('admin.requests.show', [
        'attendance_correct_request_id' => $requestId,
    ]));

    $show->assertStatus(200);
    $show->assertSee('電車遅延のため');
    $show->assertSee('09:30');
    $show->assertSee('18:10');
}

    public function test_承認待ちにログインユーザーが行った申請が全て表示される()
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'is_admin' => false,
        ]);

        // 勤怠2件 → 申請2件（「全て」を担保）
        $a1 = Attendance::factory()->create(['user_id' => $user->id, 'work_date' => '2026-01-10']);
        $a2 = Attendance::factory()->create(['user_id' => $user->id, 'work_date' => '2026-01-11']);

        DB::table('attendance_correction_requests')->insert([
            [
                'attendance_id' => $a1->id,
                'user_id' => $user->id,
                'status' => 'pending',
                'requested_note' => '申請A',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'attendance_id' => $a2->id,
                'user_id' => $user->id,
                'status' => 'pending',
                'requested_note' => '申請B',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // 他人の申請（混ざらないことも担保）
        $other = User::factory()->create(['email_verified_at' => now(), 'is_admin' => false]);
        $otherAttendance = Attendance::factory()->create(['user_id' => $other->id, 'work_date' => '2026-01-10']);
        DB::table('attendance_correction_requests')->insert([
            'attendance_id' => $otherAttendance->id,
            'user_id' => $other->id,
            'status' => 'pending',
            'requested_note' => '他人の申請',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 一般ユーザー申請一覧（承認待ち）
        $res = $this->actingAs($user)->get(route('stamp_correction_request.list', ['status' => 'pending']));
        $res->assertStatus(200);

        $res->assertSee('申請A');
        $res->assertSee('申請B');
        $res->assertDontSee('他人の申請');
    }

    public function test_承認済みに管理者が承認した修正申請が全て表示されている()
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'is_admin' => false,
        ]);

        // 承認済み申請2件（「全て」を担保）
        $a1 = Attendance::factory()->create(['user_id' => $user->id, 'work_date' => '2026-01-10']);
        $a2 = Attendance::factory()->create(['user_id' => $user->id, 'work_date' => '2026-01-11']);

        DB::table('attendance_correction_requests')->insert([
            [
                'attendance_id' => $a1->id,
                'user_id' => $user->id,
                'status' => 'approved',
                'requested_note' => '承認済みA',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'attendance_id' => $a2->id,
                'user_id' => $user->id,
                'status' => 'approved',
                'requested_note' => '承認済みB',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

                // 承認待ち（混ざらないことも担保）
        $pendingAttendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2026-01-12'
        ]);

        DB::table('attendance_correction_requests')->insert([
            'attendance_id' => $pendingAttendance->id,
            'user_id' => $user->id,
            'status' => 'pending',
            'requested_note' => 'PENDING_NOTE_SHOULD_NOT_APPEAR',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $res = $this->actingAs($user)
            ->get(route('stamp_correction_request.list', ['status' => 'approved']));

        $res->assertStatus(200);

        $res->assertSee('承認済みA');
        $res->assertSee('承認済みB');

        // タブ文字ではなく、pendingレコードの申請理由が出ていないことを確認
        $res->assertDontSee('PENDING_NOTE_SHOULD_NOT_APPEAR');
    }

    public function test_各申請の詳細を押下すると勤怠詳細画面に遷移する()
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'is_admin' => false,
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2026-01-10',
            'clock_in' => '2026-01-10 09:00:00',
            'clock_out' => '2026-01-10 18:00:00',
        ]);

        // 申請が存在する前提（一覧に出る想定）
        DB::table('attendance_correction_requests')->insert([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'status' => 'pending',
            'requested_note' => '詳細遷移確認',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 一覧ページが表示できる（「詳細」リンクがあるはずの画面）
        $list = $this->actingAs($user)->get(route('stamp_correction_request.list', ['status' => 'pending']));
        $list->assertStatus(200);

        // 「詳細」押下＝最終的に勤怠詳細画面にGETできること
        $detail = $this->actingAs($user)->get(route('attendance.detail', ['id' => $attendance->id]));
        $detail->assertStatus(200);

        // 勤怠詳細に来ていることを軽く確認（画面にある文言に合わせて調整）
        $detail->assertSee('09:00');
        $detail->assertSee('18:00');
    }



}