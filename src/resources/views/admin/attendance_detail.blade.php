@extends('layouts.app')

@section('css')
  <link rel="stylesheet" href="{{ asset('css/admin/showDetail.css') }}">
@endsection

@section('content')
{{--sessionにて「勤怠情報を修正しました」が表示--}}
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
            <form id="attendance-detail-form" action="{{ route('admin.attendance.update', $attendance->id) }}" method="POST">
                @csrf
                @method('PATCH')
                
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
                    <div class="detail-value detail-value--time">
                        <input type="time" name="clock_in" class="input-time" value="{{ \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') }}" @if($isPending) readonly @endif>

                        <span class="range-separator">〜</span>

                        <input type="time" name="clock_out" class="input-time" value="{{ \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') }}" @if($isPending) readonly @endif>

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
                            <input type="time" name="break_start[{{ $index }}]" class="input-time" value="{{ \Carbon\Carbon::parse($break->start_time)->format('H:i') }}" @if($isPending) readonly @endif>

                            <span class="range-separator">〜</span>

                            <input type="time" name="break_end[{{ $index }}]" class="input-time" value="{{ \Carbon\Carbon::parse($break->end_time)->format('H:i') }}" @if($isPending) readonly @endif>
                        </div>

                        @error("break_start.{$index}") 
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                        @error("break_end.{$index}") 
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                @endforeach

                {{-- ★ 登録済み休憩の「次」の番号で空のフォームを1つ出す --}}
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
        <div class="detail-actions">
            @if($isPending)
                <p class="pending-message">※承認待ちのため修正はできません。</p>
            @else
                <button type="submit" form="attendance-detail-form" class="submit-button">修正</button>
            @endif
        </div>
    </div>
</div>
@endsection