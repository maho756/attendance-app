@extends('layouts.admin')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance_detail.css') }}">
@endsection

@section('content')
<div class="detail">
    <h2 class="detail__title">勤怠詳細</h2>

    @if ($errors->has('locked'))
        <p class="detail__notice is-show">
            ※{{ $errors->first('locked') }}
        </p>
    @endif

    @if (!$hasPendingRequest)
        <form class="detail__form" action="{{ route('admin.attendance.update', ['id' => $attendance->id]) }}" method="post">
        @csrf
    @endif

    <div class="detail__panel">
        <table class="detail__table">
            <tbody>
                {{-- 名前 --}}
                <tr class="detail__row">
                    <th class="detail__head">名前</th>
                    <td class="detail__cell">{{ $attendance->user->name }}</td>
                </tr>

                {{-- 日付 --}}
                <tr class="detail__row">
                    <th class="detail__head">日付</th>
                    <td class="detail__cell detail__cell--date">
                        <span class="detail__date-year">
                            {{ $attendance->work_date?->format('Y年') }}
                        </span>
                        <span class="detail__date-md">
                            {{ $attendance->work_date?->format('n月j日') }}
                        </span>
                    </td>
                </tr>

                {{-- 出勤・退勤（編集） --}}
                <tr class="detail__row">
                    <th class="detail__head">出勤・退勤</th>
                    <td class="detail__cell">
                        <div class="detail__time">
                            <input
                                class="detail__input detail__input--time"
                                type="time"
                                name="clock_in"
                                value="{{ old('clock_in', $attendance->clock_in?->format('H:i') ?? '') }}"
                                @if($hasPendingRequest) disabled @endif
                            >
                            <span class="detail__tilde">〜</span>
                            <input
                                class="detail__input detail__input--time"
                                type="time"
                                name="clock_out"
                                value="{{ old('clock_out', $attendance->clock_out?->format('H:i') ?? '') }}"
                                @if($hasPendingRequest) disabled @endif
                            >
                        </div>

                        @error('clock_in')
                            <p class="detail__error">{{ $message }}</p>
                        @enderror
                        @error('clock_out')
                            <p class="detail__error">{{ $message }}</p>
                        @enderror
                    </td>
                </tr>

                {{-- 休憩（回数分 + 追加1行） --}}
                @php
                    $breaks = $attendance->breakTimes ?? collect();
                    $rows = $breaks->count() + 1;
                @endphp

                @for ($i = 0; $i < $rows; $i++)
                    @php
                        $break = $breaks->get($i);
                        $label = $i === 0 ? '休憩' : '休憩 ' . ($i + 1);
                    @endphp

                    <tr class="detail__row">
                        <th class="detail__head">{{ $label }}</th>
                        <td class="detail__cell">
                            <div class="detail__time">
                                <input
                                    class="detail__input detail__input--time"
                                    type="time"
                                    name="breaks[{{ $i }}][start]"
                                    value="{{ old("breaks.$i.start", $break?->start_time?->format('H:i') ?? '') }}"
                                    @if($hasPendingRequest) disabled @endif
                                >
                                <span class="detail__tilde">〜</span>
                                <input
                                    class="detail__input detail__input--time"
                                    type="time"
                                    name="breaks[{{ $i }}][end]"
                                    value="{{ old("breaks.$i.end", $break?->end_time?->format('H:i') ?? '') }}"
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

                {{-- 備考（編集・必須） --}}
                <tr class="detail__row">
                    <th class="detail__head">備考</th>
                    <td class="detail__cell">
                        <textarea
                            class="detail__textarea"
                            name="note"
                            rows="3"
                            @if($hasPendingRequest) disabled @endif
                        >{{ old('note', $attendance->note ?? '') }}</textarea>

                        @error('note')
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