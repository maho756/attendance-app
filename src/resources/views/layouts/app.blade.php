<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance App</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css')}}" />
    <link rel="stylesheet" href="{{ asset('css/common.css') }}" />
    @yield('css')
</head>
<body>
    <header class="header">
        <div class="header__inner">
            <a href="/attendance" class="header__logo">
                <img class="header__logo-img" src="{{ asset('images/logo.svg') }}" alt="coachtech">
            </a>
        @auth
            <nav class="header__nav">
                <ul class="header__nav-list">
                    <li class="header__nav-item">
                        <a href="/attendance" class="header__nav-link">
                            勤怠
                        </a>
                    </li>
                    <li class="header__nav-item">
                        <a href="/attendance/list" class="header__nav-link">
                            勤怠一覧
                        </a>
                    </li>
                    <li class="header__nav-item">
                        <a href="/stamp_correction_request/list" class="header__nav-link">
                            申請
                        </a>
                    </li>
                    <li class="header__nav-item">
                        <form method="post" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="header__nav-link header__nav-button">
                                ログアウト
                            </button>
                        </form>
                    </li>
                </ul>
            </nav>
        @endauth
        </div>
    </header>
    <main>
        @yield('content')
    </main>
</body>
</html>