@extends('layouts.app')

@section('css')
  <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
@endsection


@section('content')
<div class="auth-page">

  <div class="auth-form">

    <form action="{{ route('login') }}" method="POST" class="auth-form__content">
    @csrf

      <div class="tit">
        <h1 class="auth-title">ログイン</h1>
      </div>

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

      <div class="auth__button">
        <button	class="auth__button-submit" type="submit">ログインする</button>
        <a href="/register" class="register__button">会員登録はこちら</a>
      </div>
    </form>
  </div>
</div>
@endsection


