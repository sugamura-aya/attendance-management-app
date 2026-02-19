@extends('layouts.app')

@section('css')
  <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
@endsection

@section('content')
<div class="auth-page">
  <div class="auth-form">
    <form action="{{ route('admin.login.post') }}" method="POST" class="auth-form__content">
    @csrf

      <div class="tit">
        <h1 class="auth-title">管理者ログイン</h1>
      </div>

      {{--メールアドレス--}}
      <div class="content">
        <p class="content-name">メールアドレス</p>
        <input type="text" name="email" class="content-item" value="{{old('email')}}">
      </div>
      @error('email')
        <div class="error-message">{{ $message }}</div>
      @enderror

      {{--パスワード--}}
      <div class="content">
        <p class="content-name">パスワード</p>
        <input type="password" name="password" class="content-item">
      </div>
      @error('password')
        <div class="error-message">{{ $message }}</div>
      @enderror

      <div class="auth__button">
        <button class="auth__button-submit" type="submit">管理者ログインする</button>
      </div>
    </form>
  </div>
</div>
@endsection