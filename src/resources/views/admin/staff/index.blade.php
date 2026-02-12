@extends('layouts.admin')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance_list.css') }}">
@endsection

@section('content')
<div class="list">
    <h2 class="list__title">
        スタッフ一覧
    </h2>

    <div class="list__panel">
        <table class="list__table">
            <thead class="list__thead">
                <tr class="list__tr">
                    <th class="list__th">名前</th>
                    <th class="list__th">メールアドレス</th>
                    <th class="list__th">月次勤怠</th>
                </tr>
            </thead>

            <tbody class="list__tbody">
                @foreach ($staffs as $staff)
                    <tr class="list__tr {{ $loop->last ? 'list__tr--last' : '' }}">
                        <td class="list__td">{{ $staff->name }}</td>
                        <td class="list__td">{{ $staff->email }}</td>
                        <td class="list__td">
                            <a class="list__detail-link" href="{{ route('admin.staff.attendances', ['id' => $staff->id]) }}">
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