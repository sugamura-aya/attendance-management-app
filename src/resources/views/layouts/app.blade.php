<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>勤怠管理システム</title>

    {{--リセットCSS--}}
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}" />
    {{--common.css（layout用CSS）呼び出し--}}
    <link rel="stylesheet" href="{{ asset('css/common.css') }}" />

    {{-- ページごとのCSS --}}
    @yield('css')

    {{-- Webフォント --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap" rel="stylesheet">
</head>

<body>
    <header class="header">
        <div class="header__inner">
            {{-- 左：ロゴ --}}
            <div class="header__left">
                <img class="header__logo" src="{{ asset('img/logo.svg') }}" alt="ロゴマーク">
            </div>

            {{-- 右：ナビゲーション（ログイン時のみ表示） --}}
            <div class="header__right">
                @if(Auth::check())
                    @if(Auth::user()->role === 1)
                        {{-- 【管理者用メニュー】ログイン中 ＆ role=1 --}}
                        <nav class="header__nav">
                            <a class="nav__item" href="{{ route('admin.attendance.list') }}">勤怠一覧</a>
                            <a class="nav__item" href="{{ route('admin.staff.list') }}">スタッフ一覧</a>
                            <a class="nav__item" href="{{ route('stamp_correction_request.list') }}">申請一覧</a>
                            <form action="{{ route('admin.logout') }}" method="POST" class="logout-form">
                                @csrf
                                <button type="submit" class="nav__item">ログアウト</button>
                            </form>
                        </nav>
                    @else
                        {{-- 【一般ユーザー用メニュー】ログイン中 ＆ role=0 --}}
                        <nav class="header__nav">
                            @if($status !== '退勤済')
                                {{-- 【退勤打刻前】の表示 --}}
                                <a class="nav__item" href="{{ route('attendance.index') }}">勤怠</a>
                                <a class="nav__item" href="{{ route('attendance.list') }}">勤怠一覧</a>
                                <a class="nav__item" href="{{ route('stamp_correction_request.list') }}">申請</a>
                            @else
                                {{-- 【退勤後】の表示 --}}
                                <a class="nav__item" href="{{ route('attendance.list') }}">今月の出勤一覧</a>
                                <a class="nav__item" href="{{ route('stamp_correction_request.list') }}">申請一覧</a>
                            @endif

                            {{-- ログアウトは共通 --}}
                            <form action="/logout" method="POST" class="logout-form">
                                @csrf
                                <button type="submit" class="nav__item">ログアウト</button>
                            </form>
                        </nav>
                    @endif
                @endif
            </div>
        </div>
    </header>

    <main>
        @yield('content')
    </main>

    @yield('scripts')
    @stack('scripts')
</body>
</html>