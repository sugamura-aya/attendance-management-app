<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;// フォームの入力値などをまとめて運ぶためのコード
use Illuminate\Support\Facades\Auth; 
use Carbon\Carbon;// 日付や時刻の計算・加工に必須
use App\Models\User;// 「どのスタッフか」を表示するために必須
use App\Models\Attendance; 
use App\Models\AttendanceRequest; 
use App\Models\BreakTime; 
use App\Models\BreakTimeRequest; 

use App\Http\Requests\AttendanceUpdateRequest;

class AdminAttendanceController extends Controller
{
    // ➇ 勤怠一覧画面（管理者向け：全スタッフの日次勤怠）
    public function showUserAttendance(Request $request)
    {
        // 1. 表示したい日を決める（指定がなければ今日）
        $dateQuery = $request->input('date');
        $date = $dateQuery ? Carbon::parse($dateQuery) : Carbon::today();

        // 2. その日の全スタッフの勤怠を取得
        // 名前（user）と休憩（breakTimes）を一緒に持ってくる
        $attendances = Attendance::whereDate('date', $date->format('Y-m-d'))
            ->with(['user', 'breakTimes'])
            ->get();

        // 3. 画面へ（前日・翌日の日付も添えて）
        return view('admin.attendance_list', [
            'attendances' => $attendances,
            'date' => $date, // （Carbonオブジェクトのまま渡すとViewで加工しやすい）
            'prevDate' => $date->copy()->subDay()->format('Y-m-d'),
            'nextDate' => $date->copy()->addDay()->format('Y-m-d'),
        ]);
    }
}
