<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use App\Models\AttendanceCorrectionRequest;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;

class AdminStampCorrectionRequestController extends Controller
{

    public function show(int $attendance_correct_request_id): View
    {
        $req = AttendanceCorrectionRequest::with([
            'attendance',
            'breaks',
            'user'
        ])->findOrFail($attendance_correct_request_id);

        return view('admin.requests.show', compact('req'));
    }

    public function approve(int $attendance_correct_request_id): RedirectResponse
    {
        $req = AttendanceCorrectionRequest::with(['attendance.breakTimes', 'breaks'])->findOrFail($attendance_correct_request_id);

        if ($req->status !== 'pending') {
            return back()->with('message', 'この申請は既に処理済みです。');
        }

        DB::transaction(function () use ($req) {

            $attendance = $req->attendance;

            $attendance->update([
                'clock_in'  => $req->requested_clock_in,
                'clock_out' => $req->requested_clock_out,
                'note'      => $req->requested_note,
            ]);

            BreakTime::where('attendance_id', $attendance->id)->delete();

        foreach ($req->breaks as $b) {
            if (!$b->requested_start_time || !$b->requested_end_time) {
                continue;
            }

            BreakTime::create([
                'attendance_id' => $attendance->id,
                'start_time' => $b->requested_start_time,
                'end_time'   => $b->requested_end_time,
            ]);
        }

            $req->update([
                'status' => 'approved',
            ]);
        });

        return redirect()
            ->route('admin.requests.show', ['attendance_correct_request_id' => $req->id]);
    }

}