<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>勤怠管理システム</title>

    {{--リセットCSS--}}
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}" />
    {{--verify-email.css呼び出し--}}
    <link rel="stylesheet" href="{{ asset('css/verify-email.css') }}" />

    {{-- Webフォント --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap" rel="stylesheet">
</head>

<body>
    <header class="header">
        <div class="header__inner">
            {{-- ロゴ --}}
            <div class="header__left">
                <img class="header__logo" src="{{ asset('img/logo.svg') }}" alt="ロゴマーク">
            </div>
        </div>
    </header>

    <div class="container">
        <p>
            登録していただいたメールアドレスに認証メールを送付しました。<br>
            メール認証を完了してください。
        </p>

        <div class="actions">
            {{-- 1. メインのボタン（aタグをCSSでボタンにする） --}}
            <a href="{{ $verificationUrl }}" class="actions__button-submit"> 
                    認証はこちらから
            </a>

            {{-- 2. 認証メール再送フォーム --}}
            <form id="resend-form" method="POST" action="{{ route('verification.send') }}" style="display:none;">
                @csrf
            </form>
            {{-- aタグで再送フォームを送信させる --}}
            <a href="#" class="remail-link" onclick="event.preventDefault(); document.getElementById('resend-form').submit();">
                認証メールを再送する
            </a>
        </div>
    </div>

</body>
</html>