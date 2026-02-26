@extends('layouts.app')

@section('css')
  {{-- 外部CSSを読み込む --}}
  <link rel="stylesheet" href="{{ asset('css/attendance/list.css') }}">
@endsection

@section('content')
<div class="attendance-list-page">
    <div class="attendance-list__content">

        {{--見出し--}}
        <h1 class="title">勤怠一覧</h1>

        {{--月選択--}}
        <div class="month-selection">

            <div class="previous-month">
                <a href="{{ route('attendance.list', ['month' => $prevMonth]) }}">
                    <img src="{{ asset('img/arrow.png') }}" alt="前月" class="right-arrow-icon">
                </a>
                <span class="month-selection">前月</span>
            </div>

            <div class="relevant-month">    
                <img src="{{ asset('img/calendar.png') }}" alt="カレンダー―アイコン" class="calendar-icon">
                <span class="month-display">{{ $displayMonth }}</span>
            </div>

            <div class="following-month">
                <span class="month-selection">翌月</span>
                <a href="{{ route('attendance.list', ['month' => $nextMonth]) }}">
                    <img src="{{ asset('img/arrow.png') }}" alt="翌月" class="left-arrow-icon">
                </a>
            </div>
        </div>

        {{--月次一覧表--}}
        <table class="attendance-list__table">
            <thead>
                <tr>
                    <th class="label">日付</th>
                    <th class="label">出勤</th>
                    <th class="label">退勤</th>
                    <th class="label">休憩</th>
                    <th class="label">合計</th>
                    <th class="label">詳細</th>
                </tr>
            </thead>
            <tbody>
                {{-- Controllerから渡された $attendances をループさせる --}}
                @foreach($attendances as $attendance)
                <tr>
                    {{-- 日付：02/24(火) --}}
                    <td>{{ Carbon\Carbon::parse($attendance->date)->isoFormat('MM/DD(ddd)') }}</td>
                    
                    {{-- 出勤・退勤 (clock_in / clock_out) --}}
                    <td>{{ $attendance->clock_in ? Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '' }}</td>
                    <td>{{ $attendance->clock_out ? Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '' }}</td>
                    
                    {{-- モデルのメソッドを呼び出す --}}
                    {{--休憩合計時間--}}
                    <td>{{ $attendance->getFormattedTotalBreakTime() }}</td>
                    {{--（勤務時間）合計--}}
                    <td>{{ $attendance->getFormattedTotalWorkingTime() }}</td>
                    
                    <td>
                        <a href="{{ route('attendance.show', ['id' => $attendance->id ?? $attendance->date]) }}" class="detail-button">詳細</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection