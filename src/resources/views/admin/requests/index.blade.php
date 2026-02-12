@extends('layouts.admin')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance_list.css') }}">
@endsection

@section('content')
<div class="list">
    <h2 class="list__title">申請一覧</h2>

    <div class="list__tabs">
        <a
            class="list__tab {{ $status === 'pending' ? 'is-active' : '' }}"
            href="{{ url('/stamp_correction_request/list?status=pending') }}"
        >
            承認待ち
        </a>

        <a
            class="list__tab {{ $status === 'approved' ? 'is-active' : '' }}"
            href="{{ url('/stamp_correction_request/list?status=approved') }}"
        >
            承認済み
        </a>
    </div>

    @if (session('message'))
        <p class="list__message">{{ session('message') }}</p>
    @endif

    <div class="list__panel">
        <table class="list__table">
            <thead class="list__thead">
                <tr class="list__tr">
                    <th class="list__th">状態</th>
                    <th class="list__th">名前</th>
                    <th class="list__th">対象日時</th>
                    <th class="list__th">申請理由</th>
                    <th class="list__th">申請日時</th>
                    <th class="list__th">詳細</th>
                </tr>
            </thead>

            <tbody class="list__tbody">
                @forelse ($requests as $req)
                    <tr class="list__tr {{ $loop->last ? 'list__tr--last' : '' }}">
                        <td class="list__td">{{ $req->status === 'pending' ? '承認待ち' : '承認済み' }}</td>
                        <td class="list__td">{{ $req->user->name }}</td>
                        <td class="list__td">{{ $req->attendance->work_date?->format('Y/m/d') }}</td>
                        <td class="list__td">{{ $req->requested_note }}</td>
                        <td class="list__td">{{ $req->created_at->format('Y/m/d') }}</td>
                        <td class="list__td">
                            <a
                                class="list__detail-link"
                                href="{{ route('admin.requests.show', ['attendance_correct_request_id' => $req->id]) }}"
                            >
                                詳細
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr class="list__tr list__tr--last">
                        <td class="list__td list__empty" colspan="6">申請はありません</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection