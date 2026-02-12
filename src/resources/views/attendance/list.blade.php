@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance_list.css') }}">
@endsection

@section('content')
<div class="list">
    <h2 class="list__title">勤怠一覧</h2>

    <div class="list__month">
        <a class="list__month-link list__month-link--prev" href="{{ route('attendance.list', ['month' => $prevMonth]) }}">← 前月</a>
        <span class="list__month-label">{{ $monthLabel }}</span>
        <a class="list__month-link list__month-link--next" href="{{ route('attendance.list', ['month' => $nextMonth]) }}">翌月 →</a>
    </div>

    <div class="list__panel">
        <table class="list__table">
            <thead class="list__thead">
                <tr class="list__tr">
                    <th class="list__th">日付</th>
                    <th class="list__th">出勤</th>
                    <th class="list__th">退勤</th>
                    <th class="list__th">休憩</th>
                    <th class="list__th">合計</th>
                    <th class="list__th">詳細</th>
                </tr>
            </thead>

            <tbody class="list__tbody">
                @foreach ($attendances as $attendance)
                    <tr class="list__tr {{ $loop->last ? 'list__tr--last' : '' }}">
                        <td class="list__td">{{ $attendance->work_date?->isoFormat('MM/DD(ddd)') }}</td>
                        <td class="list__td">{{ $attendance->clock_in?->format('H:i') ?? '' }}</td>
                        <td class="list__td">{{ $attendance->clock_out?->format('H:i') ?? '' }}</td>
                        <td class="list__td">{{ $attendance->clock_in && $attendance->clock_out ? $attendance->formatMinutes($attendance->breakMinutes()) : '' }}</td>
                        <td class="list__td">{{ $attendance->clock_in && $attendance->clock_out ? $attendance->formatMinutes($attendance->workMinutes()) : '' }}</td>
                        <td class="list__td">
                            <a class="list__detail-link" href="{{ route('attendance.detail', ['id' => $attendance->id]) }}">詳細</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection