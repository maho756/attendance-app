@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/auth.css') }}">
@endsection

@section('content')
<div class="auth-form__content">
    <div class="auth-form__heading">
        <h2>
            会員登録
        </h2>
    </div>
    <form action="{{ route('register') }}" method="post" class="form">
        @csrf
        <div class="form__group">
            <div class="form__group-title">
                <label class="form__label" for="name" >
                    名前
                </label>
            </div>
            <div class="form__group-content">
                <div class="form__input-wrap">
                    <input class="form__input" id="name" type="text" name="name" value="{{ old('name') }}">
                </div>
                <div class="form__error">
                    @error('name')
                    {{ $message }}
                    @enderror
                </div>
            </div>
        </div>
        <div class="form__group">
            <div class="form__group-title">
                <label class="form__label" for="email">
                    メールアドレス
                </label>
            </div>
            <div class="form__group-content">
                <div class="form__input-wrap">
                    <input class="form__input" id="email" type="email" name="email" value="{{ old('email') }}" >
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
        <div class="form__group">
            <div class="form__group-title">
                <label class="form__label" for="password_confirmation">
                    パスワード確認
                </label>
            </div>
            <div class="form__group-content">
                <div class="form__input-wrap">
                    <input class="form__input" id="password_confirmation" type="password" name="password_confirmation">
                </div>
                <div class="form__error">
                    @error('password_confirmation')
                    {{ $message }}
                    @enderror
                </div>
            </div>
        </div>
        <div class="form__button">
            <button class="form__button-submit" type="submit">
                登録する
            </button>
        </div>
    </form>
    <div class="auth-form__link">
        <a href="{{ route('login') }}" class="auth-form__link-submit">
            ログインはこちら
        </a>
    </div>
</div>
@endsection