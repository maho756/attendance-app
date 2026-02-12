@extends('layouts.admin')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance_detail.css') }}">
@endsection

@section('content')
<div class="detail">
    <h2 class="detail__title">申請詳細</h2>

    <div class="detail__panel">
        <table class="detail__table">
            <tbody>
                <tr class="detail__row">
                    <th class="detail__head">名前</th>
                    <td class="detail__cell">
                        {{ $req->user->name }}
                    </td>
                </tr>

                <tr class="detail__row">
                    <th class="detail__head">日付</th>
                    <td class="detail__cell detail__cell--date">
                        <span class="detail__date-year">
                            {{ $req->attendance->work_date?->format('Y年') }}
                        </span>
                        <span class="detail__date-md">
                            {{ $req->attendance->work_date?->format('n月j日') }}
                        </span>
                    </td>
                </tr>

                <tr class="detail__row">
                    <th class="detail__head">出勤・退勤</th>
                    <td class="detail__cell">
                        <div class="detail__time">
                            <input
                                class="detail__input detail__input--time"
                                type="time"
                                value="{{ $req->requested_clock_in?->format('H:i') ?? '' }}"
                                disabled
                            >
                            <span class="detail__tilde">〜</span>
                            <input
                                class="detail__input detail__input--time"
                                type="time"
                                value="{{ $req->requested_clock_out?->format('H:i') ?? '' }}"
                                disabled
                            >
                        </div>
                    </td>
                </tr>

                @php
                    $breaks = $req->breaks ?? collect();
                    $rows = $breaks->count() + 1;
                @endphp

                @for ($i = 0; $i < $rows; $i++)
                    @php
                        $break = $breaks->get($i);
                        $label = $i === 0 ? '休憩' : '休憩 ' . ($i + 1);

                        $start = $break?->requested_start_time ? \Carbon\Carbon::parse($break->requested_start_time)->format('H:i') : '';
                        $end   = $break?->requested_end_time   ? \Carbon\Carbon::parse($break->requested_end_time)->format('H:i') : '';
                    @endphp

                    <tr class="detail__row">
                        <th class="detail__head">{{ $label }}</th>
                        <td class="detail__cell">
                            <div class="detail__time">
                                <input class="detail__input detail__input--time" type="time" value="{{ $start }}" disabled>
                                <span class="detail__tilde">〜</span>
                                <input class="detail__input detail__input--time" type="time" value="{{ $end }}" disabled>
                            </div>
                        </td>
                    </tr>
                @endfor

                <tr class="detail__row">
                    <th class="detail__head">備考</th>
                    <td class="detail__cell">
                        <textarea class="detail__textarea" rows="3" disabled>{{ $req->requested_note }}</textarea>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="detail__actions">
        @if ($req->status === 'pending')
            <form method="post" action="{{ route('admin.requests.approve', ['attendance_correct_request_id' => $req->id]) }}">
                @csrf
                <button type="submit" class="detail__button">承認</button>
            </form>
        @else
            <button type="button" class="detail__button detail__button--gray" disabled>
                承認済み
            </button>
        @endif
    </div>
</div>
@endsection