@extends('layouts.app')

@section('css')
  <link rel="stylesheet" href="{{ asset('css/attendance/show.css') }}">
@endsection

@section('content')
{{--sessionにて「修正申請を提出しました」が表示--}}
<div class="alert">
    @if(session('success'))
    <div class="alert--success">
      {{session('success')}}
    </div>
    @endif
</div>

<div class="attendance-detail-page">
    <div class="attendance-detail__content">
        <h1 class="title">勤怠詳細</h1>

        <div class="detail-table">
            <form id="attendance-detail-form" action="{{ route('attendance.request.store', ['id' => $attendance->id ?? $attendance->date]) }}" method="POST">
                @csrf
                
                {{-- 1. 名前 --}}
                <div class="detail-group">
                    <label class="detail-label">名前</label>
                    <div class="detail-value">
                        <span class="text-name">{{ $attendance->user->name }}</span>
                    </div>
                </div>

                {{-- 2. 日付 --}}
                <div class="detail-group">
                    <label class="detail-label">日付</label>
                    <div class="detail-value">
                        <div class=" detail-value--between">
                            <span>{{ \Carbon\Carbon::parse($attendance->date)->format('Y年') }}</span>
                            <span>{{ \Carbon\Carbon::parse($attendance->date)->format('n月j日') }}</span>
                        </div>
                        <input type="hidden" name="date" value="{{ $attendance->date }}">
                    </div>
                </div>

                {{-- 3. 出勤・退勤 --}}
                <div class="detail-group">
                    <label class="detail-label">出勤・退勤</label>

                    <div class="detail-value">
                        {{-- 入力フォームの横並び--}}
                        <div class="detail-value--time">
                            <input type="time" name="clock_in" class="input-time" 
                                value="{{ $attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '' }}" 
                                @if($isPending) readonly @endif>

                            <span class="range-separator">〜</span>

                            <input type="time" name="clock_out" class="input-time" 
                                value="{{ $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '' }}" 
                                @if($isPending) readonly @endif>
                        </div>

                        {{-- エラーメッセージの横並び --}}
                        @error('clock_in') 
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                        @error('clock_out') 
                            <div class="error-message">{{ $message }}</div>
                        @enderror     
                    </div>               
                </div>

                {{-- 4. 休憩 --}}
                @foreach($attendance->breakTimes as $index => $break)
                <div class="detail-group">
                    <label class="detail-label">{{ $index === 0 ? '休憩' : '休憩' . ($index + 1) }}</label>

                    <div class="detail-value">
                        <div class="input-row">
                            <input type="time" name="break_start[{{ $index }}]" class="input-time" 
                                value="{{ $break->start_time ? \Carbon\Carbon::parse($break->start_time)->format('H:i') : '' }}" 
                                @if($isPending) readonly @endif>

                            <span class="range-separator">〜</span>

                            <input type="time" name="break_end[{{ $index }}]" class="input-time" 
                                value="{{ $break->end_time ? \Carbon\Carbon::parse($break->end_time)->format('H:i') : '' }}" 
                                @if($isPending) readonly @endif>
                        </div>

                        @if($errors->has("break_start.$index"))
                            <p class="error-message">{{ $errors->first("break_start.$index") }}</p>
                        @endif
                        @if($errors->has("break_end.$index"))
                            <p class="error-message">{{ $errors->first("break_end.$index") }}</p>
                        @endif
                    </div>
                </div>
                @endforeach

                {{-- 登録済み休憩の「次」の番号で空のフォームを1つ出す --}}
                @if(!$isPending) {{-- 承認待ちじゃない時だけ追加枠を出す --}}
                <div class="detail-group">
                    @php $nextIndex = count($attendance->breakTimes); @endphp
                    <label class="detail-label">{{ $nextIndex === 0 ? '休憩' : '休憩' . ($nextIndex + 1) }}</label>
                    <div class="detail-value">
                        <div class="input-row">
                            {{-- 新規入力用なので value は空っぽ --}}
                            <input type="time" name="break_start[{{ $nextIndex }}]" class="input-time">
                            <span class="range-separator">〜</span>
                            <input type="time" name="break_end[{{ $nextIndex }}]" class="input-time">
                        </div>

                        @if($errors->has("break_start.$nextIndex"))
                            <p class="error-message">{{ $errors->first("break_start.$nextIndex") }}</p>
                        @endif

                        @if($errors->has("break_end.$nextIndex"))
                            <p class="error-message">{{ $errors->first("break_end.$nextIndex") }}</p>
                        @endif
                    </div>
                </div>
                @endif
                
                {{-- 5. 備考 --}}
                <div class="detail-group">
                    <label class="detail-label">備考</label>
                    <div class="detail-value">
                        <textarea name="remarks" class="textarea-remarks" @if($isPending) readonly @endif>{{ $attendance->remarks }}</textarea>
                        @error('remarks')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                    </div>
                </div>
            </form>
        </div>


        {{-- 修正ボタン --}}
        {{-- 今日の日付を取得 --}}
        @php
            $today = \Carbon\Carbon::today()->format('Y-m-d');
        @endphp

        <div class="detail-actions">
            @if($isPending)
                {{-- 1. 申請中なら一律NG --}}
                <p class="pending-message">※承認待ちのため修正はできません。</p>

            @elseif($attendance->date > $today)
                {{-- 2. 未来の日付ならNG --}}
                <p class="pending-message">※本日以降の日付の勤怠は入力できません。</p>

            @elseif($attendance->date == $today && !$attendance->clock_out)
                {{-- 3. 「今日」かつ「退勤打刻なし」ならNG（リアルタイム打刻を優先） --}}
                <p class="pending-message">※退勤打刻が完了するまで修正申請はできません。</p>
            @else
                {{-- 4. それ以外（過去の打刻忘れ or 過去の修正）はOK --}}
                <button type="submit" form="attendance-detail-form" class="submit-button">修正</button>
            @endif
        </div>
    </div>
</div>
@endsection