<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;// フォームの入力値などをまとめて運ぶためのコード
use Illuminate\Support\Facades\Auth; 
use App\Models\AttendanceRequest; 

class AttendanceRequestController extends Controller
{
    public function index(Request $request)
    {
        // 1. 承認待ちに絞る（自分の分 ＆ 未承認）
        $pendingRequests = AttendanceRequest::where('user_id', Auth::id())
            ->with('user') // 申請者の情報
            ->where('status', 0)
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        // 2. 承認済みに絞る（自分の分 ＆ 承認済）
        $approvedRequests = AttendanceRequest::where('user_id', Auth::id())
            ->with('user') // 申請者の情報
            ->where('status', 1)
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        // 3. 1,2両方をViewに返す
        return view('attendance.request_list', [
            'pendingRequests' => $pendingRequests,
            'approvedRequests' => $approvedRequests,
            'tab' => $request->query('tab', 'pending') 
        ]);
    }
}
