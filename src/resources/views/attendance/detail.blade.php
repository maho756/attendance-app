@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance_detail.css') }}">
@endsection

@section('content')
<div class="detail">
    <h2 class="detail__title">
        勤怠詳細
    </h2>
    @if (!$hasPendingRequest)
        <form class="detail__form" action="{{ route('stamp_correction_request.store', ['id' => $attendance->id]) }}" method="post">
        @csrf
    @endif

    <div class="detail__panel">
        <table class="detail__table">
            <tbody>
                <tr class="detail__row">
                    <th class="detail__head">名前</th>
                    <td class="detail__cell">
                        {{ $user->name }}
                    </td>
                </tr>

                <tr class="detail__row">
                    <th class="detail__head">日付</th>
                    <td class="detail__cell detail__cell--date">
                        <span class="detail__date-year">
                            {{ \Carbon\Carbon::parse($attendance->work_date)->format('Y年') }}
                        </span>
                        <span class="detail__date-md">
                            {{ \Carbon\Carbon::parse($attendance->work_date)->format('n月j日') }}
                        </span>
                    </td>
                </tr>

                <tr class="detail__row">
                    <th class="detail__head">出勤・退勤</th>
                    <td class="detail__cell">
                        <div class="detail__time">
                            <input
                                class="detail__input detail__input--time"
                                type="time" name="requested_clock_in" value="{{ old('requested_clock_in', $attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '') }}"
                                @if($hasPendingRequest) disabled @endif
                            >
                            <span class="detail__tilde">〜</span>
                            <input
                                class="detail__input detail__input--time"
                                type="time" name="requested_clock_out" value="{{ old('requested_clock_out',$attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '') }}"
                                @if($hasPendingRequest) disabled @endif
                            >
                        </div>

                        @error('requested_clock_in')
                            <p class="detail__error">{{ $message }}</p>
                        @enderror
                    </td>
                </tr>

                @php
                    $breaks = $attendance->breakTimes ?? collect();
                    $rows = $breaks->count() + 1;
                @endphp

                @for ($i = 0; $i < $rows; $i++)
                    @php
                        $break = $breaks->get($i); // 存在しなければ null
                        $label = $i === 0 ? '休憩' : '休憩 ' . ($i + 1);
                    @endphp

                    <tr class="detail__row">
                        <th class="detail__head">{{ $label }}</th>
                        <td class="detail__cell">
                            <div class="detail__time">
                                <input
                                    class="detail__input detail__input--time"
                                    type="time" name="breaks[{{ $i }}][start]"
                                    value="{{ old("breaks.$i.start", $break && $break->start_time ? \Carbon\Carbon::parse($break->start_time)->format('H:i') : '') }}" @if($hasPendingRequest) disabled @endif
                                >
                                <span class="detail__tilde">〜</span>
                                <input
                                    class="detail__input detail__input--time"
                                    type="time" name="breaks[{{ $i }}][end]"
                                    value="{{ old("breaks.$i.end", $break && $break->end_time ? \Carbon\Carbon::parse($break->end_time)->format('H:i') : '') }}"
                                    @if($hasPendingRequest) disabled @endif
                                >
                            </div>

                                @error("breaks.$i.start")
                                    <p class="detail__error">{{ $message }}</p>
                                @enderror
                                @error("breaks.$i.end")
                                    <p class="detail__error">{{ $message }}</p>
                                @enderror
                        </td>
                    </tr>
                @endfor

                <tr class="detail__row">
                    <th class="detail__head">備考</th>
                    <td class="detail__cell">
                        <textarea class="detail__textarea" name="requested_note" rows="3" @if($hasPendingRequest) disabled @endif>{{ old('requested_note', $attendance->note ?? '') }}</textarea>
                            @error('requested_note')
                                <p class="detail__error">{{ $message }}</p>
                            @enderror
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="detail__actions">
        @if ($hasPendingRequest)
            <p class="detail__notice detail__notice--right is-show">
                ※承認待ちのため修正はできません。
            </p>
        @else
            <button type="submit" class="detail__button">
                修正
            </button>
        @endif
    </div>

    @if (!$hasPendingRequest)
    </form>
    @endif
</div>

@endsection
