@extends('layouts.admin')

@section('css')
<link rel="stylesheet" href="{{ asset('css/auth.css') }}">
@endsection

@section('content')
<div class="auth-form__content">
    <div class="auth-form__heading">
        <h2>
            管理者ログイン
        </h2>
    </div>
    <form action="{{ route('login') }}" method="post" class="form">
        @csrf
        <input type="hidden" name="login_type" value="admin">
        <div class="form__group">
            <div class="form__group-title">
                <label class="form__label" for="email">
                    メールアドレス
                </label>
            </div>
            <div class="form__group-content">
                <div class="form__input-wrap">
                    <input class="form__input" id="email" type="email" name="email" value="{{ old('email') }}">
                </div>
                <div class="form__error">
                    @error('email')
                    {{ $message }}
                    @enderror
                </div>
            </div>
        </div>
        <div class="form__group">
            <div class="form__group-title">
                <label class="form__label" for="password">
                    パスワード
                </label>
            </div>
            <div class="form__group-content">
                <div class="form__input-wrap">
                    <input class="form__input" id="password" type="password" name="password">
                </div>
                <div class="form__error">
                    @error('password')
                    {{ $message }}
                    @enderror
                </div>
            </div>
        </div>
        <div class="form__button">
            <button class="form__button-submit" type="submit">
                管理者ログインする
            </button>
        </div>
    </form>
</div>
@endsection