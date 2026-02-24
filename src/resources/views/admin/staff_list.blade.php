@extends('layouts.app')

@section('css')
  {{-- 外部CSSを読み込む --}}
  <link rel="stylesheet" href="{{ asset('css/admin/showStaffList.css') }}">
@endsection

@section('content')
<div class="attendance-list-page">
    <div class="attendance-list__content">

        {{--見出し--}}
        <h1 class="title">スタッフ一覧</h1>


        {{--スタッフ一覧表--}}
        <table class="attendance-list__table">
            <thead>
                <tr>
                    <th class="label">名前</th>
                    <th class="label">メールアドレス</th>
                    <th class="label">月次勤怠</th>
                </tr>
            </thead>
            <tbody>
                {{-- Controllerから渡された $staffs をループさせる --}}
                @foreach($staffs as $staff)
                <tr>
                    {{-- 名前 --}}
                    <td>{{ $staff->name }}</td>
                    
                    {{-- メールアドレス--}}
                    <td>{{ $staff->email }}</td>
                    
                    <td>
                        <a href="{{ route('admin.attendance.staff.list', ['id' => $staff->id]) }}" class="detail-button">詳細</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        {{-- ページネーション --}}
        <div class="pagination">
            {{ $staffs->links() }}
        </div>
    </div>
</div>
@endsection