<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Response;

class AdminStaffController extends Controller
{
    public function index(): View
    {
        $staffs = User::query()
            ->where('is_admin', false)
            ->orderBy('id')
            ->get(['id', 'name', 'email']);

        return view('admin.staff.index', compact('staffs'));
    }

    public function attendances(Request $request, int $id): View
    {
        $staff = User::where('is_admin', false)->findOrFail($id);

        $month = $request->input('month', Carbon::today()->format('Y-m'));

        $start = Carbon::createFromFormat('!Y-m', $month)->startOfMonth();
        $end   = $start->copy()->endOfMonth();

        $prevMonth = $start->copy()->subMonth()->format('Y-m');
        $nextMonth = $start->copy()->addMonth()->format('Y-m');

        $monthLabel = $start->format('Y年n月');

        $attendances = Attendance::with('breakTimes')
            ->where('user_id', $staff->id)
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('work_date')
            ->get();

        return view('admin.staff.attendances', compact(
            'staff',
            'attendances',
            'month',
            'monthLabel',
            'prevMonth',
            'nextMonth'
        ));
    }

    public function exportCsv(Request $request, int $id): StreamedResponse
    {
        $staff = User::where('is_admin', false)->findOrFail($id);

        $month = $request->input('month', Carbon::today()->format('Y-m'));

        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $end   = $start->copy()->endOfMonth();

        $attendances = Attendance::with('breakTimes')
            ->where('user_id', $staff->id)
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('work_date')
            ->get();

        $fileName = sprintf(
            '勤怠一覧_%s_%d_%s.csv',
            $staff->name,
            $staff->id,
            $start->format('Ym')
        );

        return response()->streamDownload(function () use ($attendances) {
            $out = fopen('php://output', 'w');

            // ヘッダー（分表記をやめる）
            fputcsv($out, ['日付', '出勤', '退勤', '休憩', '合計']);

            foreach ($attendances as $a) {
                $breakMinutes = $a->breakMinutes();
                $workMinutes  = $a->workMinutes();

                fputcsv($out, [
                    optional($a->work_date)->format('Y-m-d') ?? (string)$a->work_date,
                    optional($a->clock_in)->format('H:i') ?? '',
                    optional($a->clock_out)->format('H:i') ?? '',
                    $a->clock_in && $a->clock_out ? $this->minutesToHi($breakMinutes) : '',
                    $a->clock_in && $a->clock_out ? $this->minutesToHi($workMinutes) : '',
                ]);
            }

            fclose($out);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function minutesToHi(int $minutes): string
    {
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;
        return sprintf('%d:%02d', $h, $m);
    }
}