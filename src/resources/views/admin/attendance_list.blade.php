@extends('layouts.app')

@section('css')
  {{-- 外部CSSを読み込む --}}
  <link rel="stylesheet" href="{{ asset('css/admin/showUserAttendance.css') }}">
@endsection

@section('content')
<div class="attendance-list-page">
    <div class="attendance-list__content">

        {{--見出し(例：2026年2月24日の勤怠)--}}
        <h1 class="title">{{ $date->format('Y年n月j日') }}の勤怠</h1>

        {{--日付選択--}}
        <div class="date-selection">

            <div class="previous-date">
                <a href="{{ route('admin.attendance.list', ['date' => $prevDate]) }}">
                    <img src="{{ asset('img/arrow.png') }}" alt="前日" class="right-arrow-icon">
                </a>
                <span class="date-selection">前日</span>
            </div>

            <div class="relevant-date">    
                <img src="{{ asset('img/calendar.png') }}" alt="カレンダー―アイコン" class="calendar-icon">
                <span class="date-display">{{ $date->format('Y/m/d') }}</span>
            </div>

            <div class="following-date">
                <span class="date-selection">翌日</span>
                <a href="{{ route('admin.attendance.list', ['date' => $nextDate]) }}">
                    <img src="{{ asset('img/arrow.png') }}" alt="翌日" class="left-arrow-icon">
                </a>
            </div>
        </div>

        {{--月次一覧表--}}
        <table class="attendance-list__table">
            <thead>
                <tr>
                    <th class="label">名前</th>
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
                    {{-- 名前 --}}
                    <td>{{ $attendance->user->name }}</td>
                    
                    {{-- 出勤・退勤 (clock_in / clock_out) --}}
                    <td>{{ $attendance->clock_in ? Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '' }}</td>
                    <td>{{ $attendance->clock_out ? Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '' }}</td>
                    
                    {{-- モデルのメソッドを呼び出す --}}
                    {{--休憩合計時間--}}
                    <td>{{ $attendance->getFormattedTotalBreakTime() }}</td>
                    {{--（勤務時間）合計--}}
                    <td>{{ $attendance->getFormattedTotalWorkingTime() }}</td>
                    
                    <td>
                        <a href="{{ route('admin.attendance.show', $attendance->id) }}" class="detail-button">詳細</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        
        {{-- ページネーション --}}
        <div class="pagination">
            {{ $attendances->links() }}
        </div>
    </div>
</div>
@endsection