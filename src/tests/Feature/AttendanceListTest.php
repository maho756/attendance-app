<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;


class AttendanceListTest extends TestCase
{
    use RefreshDatabase;

    public function test_自分が行った勤怠情報が全て表示されている()
    {
        $base = Carbon::create(2026, 1, 1, 9, 0, 0, 'Asia/Tokyo');
        Carbon::setTestNow($base);

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $otherUser = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $dates = [
            $base->copy()->toDateString(),
            $base->copy()->addDay()->toDateString(),
            $base->copy()->addDays(2)->toDateString(),
        ];

        foreach ($dates as $date) {
            Attendance::create([
                'user_id' => $user->id,
                'work_date' => $date,
                'clock_in' => Carbon::parse($date . ' 09:00'),
                'clock_out' => Carbon::parse($date . ' 18:00'),
            ]);
        }

        Attendance::create([
            'user_id' => $otherUser->id,
            'work_date' => $base->copy()->addDay()->toDateString(),
            'clock_in' => $base->copy()->addDay()->setTime(10, 0),
            'clock_out' => $base->copy()->addDay()->setTime(19, 0),
        ]);

        $response = $this->actingAs($user)->get(
            route('attendance.list', ['month' => $base->format('Y-m')])
        );

        $response->assertStatus(200);

        foreach ($dates as $date) {
            $response->assertSee(
                Carbon::parse($date)->format('m/d')
            );
        }

        $response->assertDontSee(
            $base->copy()->addDay()->setTime(10, 0)->format('H:i')
        );

        Carbon::setTestNow();
    }

    public function test_勤怠一覧画面に遷移した際に現在の月が表示される()
    {
        $now = Carbon::create(2026, 1, 15, 9, 0, 0, 'Asia/Tokyo');
        Carbon::setTestNow($now);

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('attendance.list'));

        $response->assertStatus(200);

        $response->assertSee($now->format('Y/m'));

        Carbon::setTestNow();
    }

    public function test_「前月」を押下した時に表示月の前月の情報が表示される()
    {
        $now = Carbon::create(2026, 1, 15, 9, 0, 0, 'Asia/Tokyo');
        Carbon::setTestNow($now);

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-01-10',
            'clock_in' => Carbon::parse('2026-01-10 09:00', 'Asia/Tokyo'),
            'clock_out' => Carbon::parse('2026-01-10 18:00', 'Asia/Tokyo'),
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2025-12-10',
            'clock_in' => Carbon::parse('2025-12-10 09:00', 'Asia/Tokyo'),
            'clock_out' => Carbon::parse('2025-12-10 18:00', 'Asia/Tokyo'),
        ]);

        $this->actingAs($user)->get(route('attendance.list'))->assertStatus(200);

        $prevMonth = $now->copy()->subMonth()->format('Y-m');
        $response = $this->actingAs($user)->get(
            route('attendance.list', ['month' => $prevMonth])
        );

        $response->assertStatus(200);


        $response->assertSee($now->copy()->subMonth()->format('Y/m'));

        $response->assertSee(Carbon::parse('2025-12-10')->format('m/d'));


        $response->assertDontSee(Carbon::parse('2026-01-10')->format('m/d'));

        Carbon::setTestNow();
    }

    public function test_「翌月」を押下した時に表示月の前月の情報が表示される()
    {
        $now = Carbon::create(2026, 1, 15, 9, 0, 0, 'Asia/Tokyo');
        Carbon::setTestNow($now);

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-01-10',
            'clock_in' => Carbon::parse('2026-01-10 09:00', 'Asia/Tokyo'),
            'clock_out' => Carbon::parse('2026-01-10 18:00', 'Asia/Tokyo'),
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-02-10',
            'clock_in' => Carbon::parse('2026-02-10 09:00', 'Asia/Tokyo'),
            'clock_out' => Carbon::parse('2026-02-10 18:00', 'Asia/Tokyo'),
        ]);

        $this->actingAs($user)->get(route('attendance.list'))->assertStatus(200);

        $nextMonth = $now->copy()->addMonth()->format('Y-m');
        $response = $this->actingAs($user)->get(
            route('attendance.list', ['month' => $nextMonth])
        );

        $response->assertStatus(200);


        $response->assertSee($now->copy()->addMonth()->format('Y/m'));

        $response->assertSee(Carbon::parse('2026-2-10')->format('m/d'));


        $response->assertDontSee(Carbon::parse('2026-01-10')->format('m/d'));

        Carbon::setTestNow();
    }

    public function test_「詳細」を押下すると、その日の勤怠詳細画面に遷移する()
    {
        $now = Carbon::create(2026, 1, 15, 9, 0, 0, 'Asia/Tokyo');
        Carbon::setTestNow($now);

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-01-10',
            'clock_in' => Carbon::parse('2026-01-10 09:00', 'Asia/Tokyo'),
            'clock_out' => Carbon::parse('2026-01-10 18:00', 'Asia/Tokyo'),
        ]);

        $this->actingAs($user)->get(route('attendance.list'))->assertStatus(200);

        $response = $this->actingAs($user)
            ->get(route('attendance.detail', $attendance->id));

        $response->assertStatus(200);

        $response->assertSee('2026年');
        $response->assertSee('1月10日');

        $response->assertSee('09:00');

        $response->assertSee('18:00');

        Carbon::setTestNow();
    }
}
