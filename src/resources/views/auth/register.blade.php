@extends('layouts.app')

@section('css')
  <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
@endsection


@section('content')
<div class="auth-page">

  <div class="auth-form">

    <form action="/register" method="POST" class="auth-form__content">
    @csrf

      <div class="tit">
        <h1 class="auth-title">会員登録</h1>
      </div>

     {{--名前--}}
      <div class="content">
        <p class="content-name">名前</p>
        <input type="text" name="name" class="content-item"  value="{{old('name')}}">
      </div>
      @if($errors->has('name'))
        @foreach($errors->get('name') as $message)
          <div class="error-message">{{ $message }}</div>
        @endforeach
      @endif

      {{--メールアドレス--}}
      <div class="content">
        <p class="content-name">メールアドレス</p>
        <input type="text" name="email" class="content-item"  value="{{old('email')}}">
      </div>
      @if($errors->has('email'))
        @foreach($errors->get('email') as $message)
          <div class="error-message">{{ $message }}</div>
        @endforeach
      @endif

      {{--パスワード--}}
      <div class="content">
        <p class="content-name">パスワード</p>
        <input type="password" name="password" class="content-item">
      </div>
      @if($errors->has('password'))
        @foreach($errors->get('password') as $message)
          <div class="error-message">{{ $message }}</div>
        @endforeach
      @endif

      {{--確認用パスワード--}}
      <div class="content">
        <p class="content-name">確認用パスワード</p>
        <input type="password" name="password_confirmation" class="content-item">
      </div>
      @if($errors->has('password_confirmation'))
        @foreach($errors->get('password_confirmation') as $message)
          <div class="error-message">{{ $message }}</div>
        @endforeach
      @endif

      <div class="auth__button">
        <button	class="auth__button-submit" type="submit">登録する</button>
        <a href="/login" class="login__button">ログインはこちら</a>
      </div>
    </form>
  </div>
</div> 
@endsection


