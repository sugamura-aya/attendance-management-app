@extends('layouts.app')

@section('css')
  <link rel="stylesheet" href="{{ asset('css/attendance.css') }}">
@endsection


@section('content')
{{-- 出勤状態表示 --}}
<p>{{ $status }}</p>

{{-- 現在の日時表示 --}}
<h1>{{ $today }}</h1>
<h2>{{ $now }}</h2>

<div class="attendance-buttons">
    {{-- 1. 勤務外のとき：出勤ボタン --}}
    @if($status == '勤務外')
        <form action="{{ route('attendance.clock-in') }}" method="POST">
            @csrf
            <button type="submit">出勤</button>
        </form>

    {{-- 2. 出勤中のとき：退勤ボタン & 休憩入ボタン --}}
    @elseif($status == '出勤中')
        <form action="{{ route('attendance.clock-out') }}" method="POST">
            @csrf
            <button type="submit">退勤</button>
        </form>

        <form action="{{ route('attendance.break-start') }}" method="POST">
            @csrf
            <button type="submit">休憩入</button>
        </form>

    {{-- 3. 休憩中のとき：休憩戻ボタン --}}
    @elseif($status == '休憩中')
        <form action="{{ route('attendance.break-end') }}" method="POST">
            @csrf
            <button type="submit">休憩戻</button>
        </form>

    {{-- 4. 退勤済のとき：お疲れ様メッセージ --}}
    @elseif($status == '退勤済')
        <p>お疲れ様でした。</p>
    @endif
</div>

@endsection