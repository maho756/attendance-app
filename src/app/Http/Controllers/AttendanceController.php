<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Attendance;
use Illuminate\Support\Facades\Auth;
use App\Models\BreakTime;
use App\Models\AttendanceCorrectionRequest;

class AttendanceController extends Controller
{

    public function index()
    {
        $now = Carbon::now()->locale('ja');
        $today = $now->toDateString();

        $attendance = Attendance::with('breakTimes')
            ->where('user_id', Auth::id())
            ->where('work_date', $today)
            ->first();

        $status = '勤務外';

        if ($attendance && $attendance->clock_in && !$attendance->clock_out) {
            $lastBreak = $attendance->breakTimes
                ->sortByDesc('id')->first();
            $isBreaking = $lastBreak && is_null($lastBreak->end_time);

            $status = $isBreaking ? '休憩中' : '出勤中';
        }

        if ($attendance && $attendance->clock_out) {
            $status = '退勤済';
        }

        $dateLabel = $now->isoFormat('YYYY年M月D日(ddd)');
        $timeLabel = $now->format('H:i');

        return view('attendance.index', compact(
            'attendance',
            'status',
            'dateLabel',
            'timeLabel',
        ));
    }

    public function clockIn()
    {
        $now = Carbon::now();
        $today = $now->toDateString();

        $attendance = Attendance::with('breakTimes')
            ->where('user_id', Auth::id())
            ->where('work_date', $today)
            ->first();

        if ($attendance && $attendance->clock_in) {
            return redirect()->route('attendance.index');
        }

        Attendance::updateOrCreate(
            ['user_id' => Auth::id(), 'work_date' => $today],
            ['clock_in' => $now]
        );

        return redirect()->route('attendance.index');
    }

    public function breakStart()
    {
        $now = Carbon::now();
        $today = $now->toDateString();

        $attendance = Attendance::with('breakTimes')
            ->where('user_id', Auth::id())
            ->where('work_date', $today)
            ->first();

        if (!$attendance || !$attendance->clock_in || $attendance->clock_out) {
            return redirect()->route('attendance.index');
        }

        $lastBreak = $attendance->breakTimes
            ->sortByDesc('id')
            ->first();
        if ($lastBreak && is_null($lastBreak->end_time)) {
            return redirect()->route('attendance.index');
        }

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'start_time' => $now,
        ]);

        return redirect()->route('attendance.index');
    }

    public function breakEnd()
    {
        $now = Carbon::now();
        $today = $now->toDateString();

        $attendance = Attendance::with('breakTimes')
            ->where('user_id', Auth::id())
            ->where('work_date', $today)
            ->first();

        if (!$attendance || !$attendance->clock_in || $attendance->clock_out) {
            return redirect()->route('attendance.index');
        }

        $break = $attendance->breakTimes
            ->whereNull('end_time')
            ->sortByDesc('id')
            ->first();

        if (!$break) {
            return redirect()->route('attendance.index');
        }

        $break->update(['end_time' => $now]);

        return redirect()->route('attendance.index');
    }

    public function clockOut()
    {
        $now = Carbon::now();
        $today = $now->toDateString();

        $attendance = Attendance::with('breakTimes')
            ->where('user_id', Auth::id())
            ->where('work_date', $today)
            ->first();

        if (!$attendance || !$attendance->clock_in || $attendance->clock_out) {
            return redirect()->route('attendance.index');
        }

        $breaking = $attendance->breakTimes
            ->whereNull('end_time')
            ->isNotEmpty();

        if ($breaking) {
            return redirect()->route('attendance.index');
        }

        $attendance->update(['clock_out' => $now]);

        return redirect()->route('attendance.index');
    }

    public function list()
    {
        $month = request('month');
        $base = $month ? Carbon::createFromFormat('Y-m', $month)->startOfMonth() : now()->startOfMonth();

        $start = $base->copy()->startOfMonth();
        $end = $base->copy()->endOfMonth();

        $attendances = Attendance::with('breakTimes')
            ->where('user_id', Auth::id())
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('work_date')
            ->get();

        $prevMonth = $base->copy()->subMonth()->format('Y-m');
        $nextMonth = $base->copy()->addMonth()->format('Y-m');

        return view('attendance.list', [
            'monthLabel' => $base->format('Y/m'),
            'prevMonth' => $prevMonth,
            'nextMonth' => $nextMonth,
            'attendances' => $attendances,
        ]);
    }

    public function detail($id)
    {
        $attendance = Attendance::with('breakTimes')
            ->where('user_id', Auth::id())
            ->findOrFail($id);

        $user = Auth::user();
        $date = $attendance->work_date;
        $breaks = $attendance->breakTimes->sortBy('start_time')->values();

        $breakRows = $breaks->map(function ($b) {
            return [
                'start' => $b->start_time?->format('H:i') ?? '',
                'end'   => $b->end_time?->format('H:i') ?? '',
            ];
        })->toArray();

        $breakRows[] = ['start' => '', 'end' => ''];

        $hasPendingRequest = AttendanceCorrectionRequest::where('attendance_id', $attendance->id)
        ->where('status', 'pending')
        ->exists();

        return view('attendance.detail', compact('attendance', 'user', 'date', 'breakRows', 'hasPendingRequest'));
    }
}
