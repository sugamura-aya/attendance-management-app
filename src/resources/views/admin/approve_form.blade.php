{{--画面ID13：修正申請承認画面--}}
@extends('layouts.app')

@section('css')
  <link rel="stylesheet" href="{{ asset('css/admin/showApproveForm.css') }}">
@endsection

@section('content')
{{--sessionにて「申請を承認しました」が表示--}}
<div class="alert">
    @if(session('success'))
    <div class="alert--success">
      {{session('success')}}
    </div>
    @endif
</div>

<div class="attendance-approve-page">
    <div class="attendance-approve__content">
        <h1 class="title">勤怠詳細</h1>

        <div class="approve-table">
            <form id="attendance-approve-form" action="{{ route('admin.stamp_correction_request.approve.update', $attendance->id) }}" method="POST">
                @csrf
                @method('PATCH')
                
                {{-- 1. 名前 --}}
                <div class="approve-group">
                    <label class="approve-label">名前</label>
                    <div class="approve-value">
                        <span class="text-name">{{ $attendance->user->name }}</span>
                    </div>
                </div>

                {{-- 2. 日付 --}}
                <div class="approve-group">
                    <label class="approve-label">日付</label>
                    <div class="approve-value">
                        <div class=" approve-value--between">
                            <span>{{ \Carbon\Carbon::parse($attendance->date)->format('Y年') }}</span>
                            <span>{{ \Carbon\Carbon::parse($attendance->date)->format('n月j日') }}</span>
                        </div>
                        <input type="hidden" name="date" value="{{ $attendance->date }}">
                    </div>
                </div>

                {{-- 3. 出勤・退勤 --}}
                <div class="approve-group">
                    <label class="approve-label">出勤・退勤</label>
                    <div class="approve-value approve-value--time">
                        <span class="input-time">
                            {{ \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') }}
                        </span>

                        <span class="range-separator">〜</span>

                        <span class="input-time">
                            {{ \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') }}
                        </span>
                    </div>
                </div>

                {{-- 4. 休憩 --}}
                @foreach($attendance->breakTimeRequests as $index => $break)
                <div class="approve-group">
                    <label class="approve-label">{{ $index === 0 ? '休憩' : '休憩' . ($index + 1) }}</label>

                    <div class="approve-value">
                        <div class="input-row">
                            <span class="input-time">{{ \Carbon\Carbon::parse($break->start_time)->format('H:i') }}</span>

                            <span class="range-separator">〜</span>

                            <span class="input-time">{{ \Carbon\Carbon::parse($break->end_time)->format('H:i') }}</span>
                        </div>
                    </div>
                </div>
                @endforeach                

                {{-- 5. 備考 --}}
                <div class="approve-group">
                    <label class="approve-label">備考</label>
                    <div class="approve-value">
                        <div class="text-remarks" style="white-space: pre-wrap;">{{ $attendance->remarks }}</div>
                    </div>
                </div>
            </form>
        </div>

        {{-- 承認ボタン --}}
        <div class="approve-actions">
            @if($isPending)
                {{-- 承認待ち(status=0)のときは、承認ボタンを出す --}}
                <button type="submit" form="attendance-approve-form" class="submit-button">承認</button>
            @else
                {{-- すでに承認済み(status=1)のときは「承認済み」--}}
                 <p class="approved-text">承認済み</p>
            @endif
        </div>
    </div>
</div>
@endsection