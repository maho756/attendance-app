<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;
use App\Models\AttendanceCorrectionRequest;
use App\Http\Requests\StampCorrectionRequest;
use Carbon\Carbon;

class StampCorrectionRequestController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'pending');

        $query = AttendanceCorrectionRequest::with(['attendance', 'user'])
            ->where('status', $status)
            ->latest();

        if (!Auth::user()->is_admin) {
            $query->where('user_id', Auth::id());
        }

        $requests = $query->get();

        if (Auth::user()->is_admin) {
            return view('admin.requests.index', compact('requests', 'status'));
        }

        return view('stamp_correction_request.list', compact('requests', 'status'));
    }

    public function store(StampCorrectionRequest $request, $id)
    {
        $attendance = Attendance::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $exists = AttendanceCorrectionRequest::where('attendance_id', $attendance->id)
            ->where('status', 'pending')
            ->exists();

        if ($exists) {
            return redirect()
                ->route('attendance.detail', ['id' => $attendance->id])
                ->with('message', '承認待ちのため修正はできません。');
        }

        $date = $attendance->work_date instanceof Carbon
        ? $attendance->work_date->toDateString()
        : \Carbon\Carbon::parse($attendance->work_date)->toDateString();

        $requestedIn  = Carbon::parse($date.' '.$request->requested_clock_in.':00');
        $requestedOut = Carbon::parse($date.' '.$request->requested_clock_out.':00');

        $req = AttendanceCorrectionRequest::create([
            'attendance_id' => $attendance->id,
            'user_id' => Auth::id(),
            'status' => 'pending',
            'requested_clock_in' => $requestedIn,
            'requested_clock_out' => $requestedOut,
            'requested_note'      => $request->requested_note,
        ]);

        foreach (($request->breaks ?? []) as $b) {
            $start = $b['start'] ?? null;
            $end   = $b['end'] ?? null;

            if (!$start && !$end) continue;

            if (!$start || !$end) continue;

            $req->breaks()->create([
                'requested_start_time' => Carbon::parse($date.' '.$start.':00'),
                'requested_end_time'   => Carbon::parse($date.' '.$end.':00'),
            ]);
        }

        return redirect()
            ->route('stamp_correction_request.list', ['status' => 'pending']);
    }
}