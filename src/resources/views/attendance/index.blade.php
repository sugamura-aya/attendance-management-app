@extends('layouts.app')

@section('css')
  {{-- 外部CSSを読み込む --}}
  <link rel="stylesheet" href="{{ asset('css/attendance/index.css') }}">
@endsection

@section('content')
<div class="attendance-container">

    {{-- 1. 出勤状態表示 --}}
    <div class="status-display">
        <p class="status-text">{{ $status }}</p>
    </div>

    {{-- 2. 現在の日時表示 --}}
    <div class="datetime-display">
        <h1 class="date-text">{{ $today }}</h1>
        {{-- id="realtime" を使ってJavaScriptで1分ごとに更新 --}}
        <h2 class="time-text" id="realtime">{{ $now }}</h2>
    </div>

    {{-- 3. ボタンエリア --}}
    <div class="attendance-buttons">
        
        {{-- ➀ 勤務外のとき：出勤ボタンのみ --}}
        @if($status == '勤務外')
            <form action="{{ route('attendance.clock-in') }}" method="POST" class="attendance-form">
                @csrf
                <button type="submit" class="btn btn-main">出勤</button>
            </form>

        {{-- ➁ 出勤中のとき：退勤ボタン と 休憩入ボタン を横並び --}}
        @elseif($status == '出勤中')
            <form action="{{ route('attendance.clock-out') }}" method="POST" class="attendance-form">
                @csrf
                <button type="submit" class="btn btn-main">退勤</button>
            </form>

            <form action="{{ route('attendance.break-start') }}" method="POST" class="attendance-form">
                @csrf
                <button type="submit" class="btn btn-sub">休憩入</button>
            </form>

        {{-- ➂ 休憩中のとき：休憩戻ボタンのみ --}}
        @elseif($status == '休憩中')
            <form action="{{ route('attendance.break-end') }}" method="POST" class="attendance-form">
                @csrf
                <button type="submit" class="btn btn-sub">休憩戻</button>
            </form>

        {{-- ➃ 退勤済のとき：お疲れ様メッセージ --}}
        @elseif($status == '退勤済')
            <div class="finish-message">
                <p>お疲れ様でした。</p>
            </div>
        @endif

    </div>
</div>

{{-- リアルタイムで「分」を更新する魔法のスクリプト --}}
<script>
    function updateTime() {
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        
        // 「08:00」の形式にして画面の文字を書き換える
        const displayTime = hours + ':' + minutes;
        const timeElement = document.getElementById('realtime');
        
        if (timeElement) {
            timeElement.textContent = displayTime;
        }
    }

    // 1秒ごとに今の時間をチェックして、分が変わったら表示を更新
    setInterval(updateTime, 1000);
</script>
@endsection


{{-- layoutにある @stack('scripts') にこの中身を届ける --}}
@push('scripts')
<script>
    function updateTime() {

        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        
        const displayTime = hours + ':' + minutes;
        const timeElement = document.getElementById('realtime'); 
        
        if (timeElement) {
            timeElement.textContent = displayTime;
        }
    }

    setInterval(updateTime, 1000);
    updateTime();
</script>
@endpush 