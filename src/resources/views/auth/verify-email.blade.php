@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/verify-email.css') }}">
@endsection

@section('content')
<div class="verify">
    <p class="verify__message">
        登録していただいたメールアドレスに認証メールを送付しました。<br>
        メール認証を完了してください。
    </p>

    @if (session('status') === 'verification-link-sent')
        <p class="verify__success">
            認証メールを再送しました。
        </p>
    @endif

    <div class="verify__actions">
        <a href="http://localhost:8025"  target="_blank" rel="noopener" class="btn btn--primary">
            認証はこちらから
        </a>
        <form class="verify__form" method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button class="btn btn--secondary" type="submit">
                認証メールを再送する
            </button>
        </form>
    </div>
</div>
@endsection