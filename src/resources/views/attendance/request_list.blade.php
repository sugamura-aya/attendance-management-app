@extends('layouts.app')

@section('css')
  <link rel="stylesheet" href="{{ asset('css/attendance/requestIndex.css') }}">
@endsection

@section('content')
<div class="attendance-list-page">
    <div class="attendance-list__content">

        {{-- 1. 見出し --}}
        <h1 class="title">申請一覧</h1>

        {{-- 2. タブ部分 --}}
        <nav class="tab-nav">
            <ul class="tab-list">
                {{-- 承認待ちタブ --}}
                <li class="tab-item">
                    <a href="{{ route('stamp_correction_request.list', ['tab' => 'pending']) }}" 
                       class="tab-link {{ $tab === 'pending' ? 'tab-link--active' : '' }}">
                        承認待ち
                    </a>
                </li>
                {{-- 承認済みタブ --}}
                <li class="tab-item">
                    <a href="{{ route('stamp_correction_request.list', ['tab' => 'approved']) }}" 
                       class="tab-link {{ $tab === 'approved' ? 'tab-link--active' : '' }}">
                        承認済み
                    </a>
                </li>
            </ul>
        </nav>

        {{-- 3. テーブル部分 --}}
        <table class="attendance-list__table">
            <thead>
                <tr>
                    <th class="label">状態</th>
                    <th class="label">名前</th>
                    <th class="label">対象日時</th>
                    <th class="label">申請理由</th>
                    <th class="label">申請日時</th>
                    <th class="label">詳細</th>
                </tr>
            </thead>
            <tbody>
                @php
                    // 今のタブに応じて、ループさせるリストを切り替える
                    $currentRequests = ($tab === 'approved') ? $approvedRequests : $pendingRequests;
                @endphp

                @foreach($currentRequests as $request)
                <tr>
                    {{-- 状態 --}}
                    <td>{{ ($tab === 'approved') ? '承認済み' : '承認待ち' }}</td>
                    
                    {{-- 名前 --}}
                    <td>{{ $request->user->name }}</td>
                    
                    {{-- 対象日時 (例：2026/02/25) --}}
                    <td>{{ \Carbon\Carbon::parse($request->attendance_date)->format('Y/m/d') }}</td>
                    
                    {{-- 申請理由 --}}
                    <td>{{ $request->reason }}</td>
                    
                    {{-- 申請日時 --}}
                    <td>{{ $request->created_at->format('Y/m/d') }}</td>
                    
                    {{-- 詳細ボタン --}}
                    <td>
                        <a href="{{ route('attendance.show', $request->attendance_id) }}" class="detail-button">詳細</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        {{-- もしデータが空だった時のための「遊び」心 --}}
        @if($currentRequests->isEmpty())
            <p style="text-align: center; margin-top: 20px; color: #999;">現在、申請はありません。</p>
        @endif

        {{-- ページネーション --}}
        <div class="pagination">
            {{ ($tab === 'approved') ? $approvedRequests->links() : $pendingRequests->links() }}
        </div>
    </div>
</div>
@endsection