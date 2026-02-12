<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

use App\Models\AttendanceCorrectionRequest;

use App\Http\Requests\AdminAttendanceUpdateRequest;

class AdminAttendanceController extends Controller
{
    public function list(Request $request): View
    {
        $date = $request->filled('date')
            ? Carbon::parse($request->input('date'))
            : Carbon::today();

        $prevDate = $date->copy()->subDay()->toDateString();
        $nextDate = $date->copy()->addDay()->toDateString();

        $dateLabel = $date->format('Y年n月j日');

        $attendances = Attendance::with('user')
            ->whereDate('work_date', $date->toDateString())
            ->orderBy('user_id')
            ->get();

        return view('admin.attendance.list', compact(
            'attendances',
            'dateLabel',
            'prevDate',
            'nextDate'
        ));
    }

    public function detail(int $id): View
    {
        $attendance = Attendance::with(['user', 'breakTimes'])->findOrFail($id);

        $hasPendingRequest = AttendanceCorrectionRequest::where('attendance_id', $attendance->id)
            ->where('status', 'pending')
            ->exists();

        return view('admin.attendance.detail', compact('attendance', 'hasPendingRequest'));
    }

    public function update(AdminAttendanceUpdateRequest $request, int $id): RedirectResponse
    {
        $attendance = Attendance::with('breakTimes')->findOrFail($id);

        $hasPendingRequest = AttendanceCorrectionRequest::where('attendance_id', $attendance->id)
            ->where('status', 'pending')
            ->exists();

        if ($hasPendingRequest) {
            return back()->withErrors([
                'locked' => '承認待ちのため修正はできません。'
            ])->withInput();
        }

        DB::transaction(function () use ($request, $attendance) {

            $date = $attendance->work_date?->format('Y-m-d') ?? Carbon::now()->format('Y-m-d');

            $clockIn  = Carbon::parse($date . ' ' . $request->input('clock_in') . ':00');
            $clockOut = Carbon::parse($date . ' ' . $request->input('clock_out') . ':00');

            $attendance->update([
                'clock_in'  => $clockIn,
                'clock_out' => $clockOut,
                'note'      => $request->input('note'),
            ]);

            $inputBreaks = $request->input('breaks', []);
            $existing = $attendance->breakTimes->values();

            foreach ($inputBreaks as $i => $b) {
                $start = $b['start'] ?? null;
                $end   = $b['end'] ?? null;

                if (!$start && !$end) {
                    continue;
                }

                $startDt = $start ? Carbon::parse("$date $start:00") : null;
                $endDt   = $end   ? Carbon::parse("$date $end:00")   : null;

                $model = $existing->get($i);

                if ($model) {
                    $model->update([
                        'start_time' => $startDt,
                        'end_time'   => $endDt,
                    ]);
                } else {
                    BreakTime::create([
                        'attendance_id' => $attendance->id,
                        'start_time'    => $startDt,
                        'end_time'      => $endDt,
                    ]);
                }
            }
        });

        return redirect()
            ->route('admin.attendance.detail', ['id' => $attendance->id])
            ->with('message', '勤怠を修正しました。');
    }
}