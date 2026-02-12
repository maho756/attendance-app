<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;

class AttendanceDummySeeder extends Seeder
{
    public function run()
    {
        $staffList = [
            ['name' => '山田 太郎', 'email' => 'yamada@example.com'],
            ['name' => '西 伶奈',   'email' => 'nishi@example.com'],
            ['name' => '増田 一世', 'email' => 'masuda@example.com'],
            ['name' => '山本 敬吉', 'email' => 'yamamoto@example.com'],
            ['name' => '秋田 朋美', 'email' => 'akita@example.com'],
            ['name' => '中西 教夫', 'email' => 'nakanishi@example.com'],
        ];

        // 6ヶ月前の月初 〜 先月の月初（=先月まで）を対象にする
        $startMonth = now()->subMonths(6)->startOfMonth();
        $endMonth   = now()->subMonth()->startOfMonth();

        $users = collect($staffList)->map(function ($s) {
            return User::updateOrCreate(
                ['email' => $s['email']],
                [
                    'name' => $s['name'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'is_admin' => false,
                ]
            );
        });

        $monthCursor = $startMonth->copy();

        while ($monthCursor->lte($endMonth)) {
            $cursor = $monthCursor->copy()->startOfMonth();
            $end    = $monthCursor->copy()->endOfMonth();

            while ($cursor->lte($end)) {
                if ($cursor->isWeekend()) {
                    $cursor->addDay();
                    continue;
                }

                $date = $cursor->toDateString();

                foreach ($users as $user) {
                    $attendance = Attendance::updateOrCreate(
                        [
                            'user_id' => $user->id,
                            'work_date' => $date,
                        ],
                        [
                            'clock_in'  => Carbon::parse("$date 09:00"),
                            'clock_out' => Carbon::parse("$date 18:00"),
                        ]
                    );

                    // 休憩を作り直す（updateOrCreateで既存がある場合に備える）
                    $attendance->breakTimes()->delete();

                    BreakTime::create([
                        'attendance_id' => $attendance->id,
                        'start_time' => Carbon::parse("$date 12:00"),
                        'end_time'   => Carbon::parse("$date 13:00"),
                    ]);

                    BreakTime::create([
                        'attendance_id' => $attendance->id,
                        'start_time' => Carbon::parse("$date 15:00"),
                        'end_time'   => Carbon::parse("$date 15:15"),
                    ]);
                }

                $cursor->addDay();
            }

            $monthCursor->addMonth();
        }
    }
}