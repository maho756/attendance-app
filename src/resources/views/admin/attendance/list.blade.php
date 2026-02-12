@extends('layouts.admin')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance_list.css') }}">
@endsection

@section('content')
<div class="list">
    <h2 class="list__title">
        勤怠一覧
    </h2>

    <div class="list__month">
        <a class="list__month-link list__month-link--prev" href="{{ route('admin.attendance.list', ['date' => $prevDate]) }}">
            ← 前日
        </a>

        <span class="list__month-label">{{ $dateLabel }}</span>

        <a class="list__month-link list__month-link--next" href="{{ route('admin.attendance.list', ['date' => $nextDate]) }}">
            翌日 →
        </a>
    </div>

    <div class="list__panel">
        <table class="list__table">
            <thead class="list__thead">
                <tr class="list__tr">
                    <th class="list__th">名前</th>
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
                        <td class="list__td">{{ $attendance->user->name }}</td>
                        <td class="list__td">{{ optional($attendance->clock_in)->format('H:i') }}</td>
                        <td class="list__td">{{ optional($attendance->clock_out)->format('H:i') }}</td>
                        <td class="list__td">{{ $attendance->clock_in && $attendance->clock_out ? $attendance->formatMinutes($attendance->breakMinutes()) : '' }}</td>
                        <td class="list__td">{{ $attendance->clock_in && $attendance->clock_out ? $attendance->formatMinutes($attendance->workMinutes()) : '' }}</td>
                        <td class="list__td">
                            <a class="list__detail-link" href="{{ route('admin.attendance.detail', $attendance->id) }}">
                                詳細
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection