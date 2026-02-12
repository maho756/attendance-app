@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance.css') }}">
@endsection

@section('content')
<div class="attendance">
    <div class="attendance__status">
        <span class="attendance__badge">
            {{ $status }}
        </span>
    </div>

    <div class="attendance__date">
        {{ $dateLabel }}
    </div>
    <div class="attendance__time">
        {{ $timeLabel }}
    </div>

    <div class="attendance__actions">

        @if ($status === '勤務外')
            <form method="POST" action="{{ route('attendance.clockIn') }}">
                @csrf
                <button class="btn btn--black" type="submit">出勤</button>
            </form>
        @endif

        @if ($status === '出勤中')
            <form method="POST" action="{{ route('attendance.clockOut') }}">
                @csrf
                <button class="btn btn--black" type="submit">退勤</button>
            </form>

            <form method="POST" action="{{ route('attendance.breakStart') }}">
                @csrf
                <button class="btn btn--white" type="submit">休憩入</button>
            </form>
        @endif

        @if ($status === '休憩中')
            <form method="POST" action="{{ route('attendance.breakEnd') }}">
                @csrf
                <button class="btn btn--white" type="submit">休憩戻</button>
            </form>
        @endif

        @if ($status === '退勤済')
            <p class="attendance__message">お疲れ様でした。</p>
        @endif

    </div>
</div>
@endsection