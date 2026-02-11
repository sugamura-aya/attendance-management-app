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
                @if(Auth::guard('admin')->check())
                    {{-- 【管理者用メニュー】ログイン済み & role=1 --}}
                    <nav class="header__nav">
                        <a class="nav__item" href="{{ route('admin.attendance.list') }}">勤怠一覧</a>
                        <a class="nav__item" href="{{ route('admin.staff.list') }}">スタッフ一覧</a>
                        <a class="nav__item" href="{{ route('stamp_correction_request.list') }}">申請一覧</a>
                        <form action="{{ route('admin.logout') }}" method="POST" class="logout-form">
                            @csrf
                            <button type="submit" class="nav__item">ログアウト</button>
                        </form>
                    </nav>
                @elseif(Auth::check())
                    {{-- 【一般ユーザー用メニュー】ログイン済み & role=0 --}}
                    {{-- Laravelの設定（config/auth.php）で一般ユーザーはLaravelが自動で判別してくれるため、こちらには'guard'は不要 --}}
                    <nav class="header__nav">
                        <a class="nav__item" href="{{ route('attendance.index') }}">勤怠</a>
                        <a class="nav__item" href="{{ route('attendance.list') }}">勤怠一覧</a>
                        <a class="nav__item" href="{{ route('stamp_correction_request.list') }}">申請</a>
                        <form action="/logout" method="POST" class="logout-form">
                            @csrf
                            <button type="submit" class="nav__item">ログアウト</button>
                        </form>
                    </nav>
                @endif
                {{-- 未ログイン時は何も表示しない（またはログインボタンなど） --}}
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