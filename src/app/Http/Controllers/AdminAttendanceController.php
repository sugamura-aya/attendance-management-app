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
            ->get(15);

        // 3. 画面へ（前日・翌日の日付も添えて）
        return view('admin.attendance_list', [
            'attendances' => $attendances,
            'date' => $date, // （Carbonオブジェクトのまま渡すとViewで加工しやすい）
            'prevDate' => $date->copy()->subDay()->format('Y-m-d'),
            'nextDate' => $date->copy()->addDay()->format('Y-m-d'),
        ]);
    }


    // ⓽ 勤怠詳細画面（管理者向け：表示）
    public function showDetail($id)
    {
        // 指定されたIDの勤怠データを、ユーザー情報と休憩情報と一緒に取得
        $attendance = Attendance::with(['user', 'breakTimes'])->findOrFail($id);

        return view('admin.attendance_detail', compact('attendance'));
    }

    // ⓽ 勤怠詳細画面（管理者向け：更新）
    public function update(AttendanceUpdateRequest $request, $id)
    {
        // 1. 対象の勤怠データを取得
        $attendance = Attendance::findOrFail($id);

        // 2. 勤怠本番テーブル（attendances）を更新
        $attendance->update([
            'clock_in'  => $request->clock_in,
            'clock_out' => $request->clock_out,
            'remarks'   => $request->remarks,
        ]);

        // 3. 休憩本番テーブル（break_times）の更新
        // 申請ではなく管理者の直接修正なので、一度今の休憩を消して新しく作り直すのが一番確実で正確！
        $attendance->breakTimes()->delete();//AttendanceModelのリレーション（public function breakTimes()）関数を呼び出し、その取り出したデータを削除。

        if ($request->has('breaks')) { //'breaks'=HTMLの入力フォームのname属性（この場合休憩時間を指す）→画面から休憩のデータが一つでも送られてきてるかを確認。
            foreach ($request->breaks as $breakData) {
                // start_time と end_time の両方が入っている場合のみ保存
                if (!empty($breakData['start_time']) && !empty($breakData['end_time'])) {
                    $attendance->breakTimes()->create([
                        'start_time' => $breakData['start_time'],
                        'end_time'   => $breakData['end_time'],
                    ]);
                }
            }
        }

        return redirect()->route('admin.attendance.list')->with('success', '勤怠情報を修正しました');
    }


    // ⓾ スタッフ一覧画面（管理者向け：表示）
    public function showStaffList()
    {
        // userモデルから、一般ユーザー（role=0）だけを全員分取ってくる
        $staffs = User::where('role', 0)->get(15);

        return view('admin.staff_list', compact('staffs'));
    }


    // ⑪ スタッフ別勤怠一覧（管理者向け：表示）
    public function showStaffAttendance(Request $request, $id)
    {
        // 1. userモデルから、そのスタッフ本人の情報を1件取得
        $user = User::findOrFail($id);

        // 2. 表示したい月を決定（ユーザー側と同じロジックで統一）
        $targetMonthStr = $request->input('month', Carbon::now()->format('Y-m'));
        $currentMonth = Carbon::parse($targetMonthStr);

        $startDate = $currentMonth->copy()->startOfMonth();
        $endDate = $currentMonth->copy()->endOfMonth();

        //  リンク用と表示用の月を作る
        $prevMonth = $currentMonth->copy()->subMonth()->format('Y-m');
        $nextMonth = $currentMonth->copy()->addMonth()->format('Y-m');
        $displayMonth = $currentMonth->format('Y/m');

        // 3. そのスタッフに紐づく、指定された月の勤怠データを取得
        $attendances = $user->attendances()
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'asc')
            ->with('breakTimes') // 休憩時間も一緒
            ->get();

        return view('admin.staff_attendance_list', compact('user', 'attendances','displayMonth', 'prevMonth', 'nextMonth'));
    }


    // ⑫ 申請一覧画面（管理者向け：表示）
    public function index(Request $request)
    {
        // 1. 全スタッフの「承認待ち(status=0)」の申請を、新しい順に取得
        $pendingRequests = AttendanceRequest::with('user')
            ->where('status', 0)
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        // 2. 全スタッフの「承認済み(status=1)」の申請を、新しい順に取得
        $approvedRequests = AttendanceRequest::with('user')
            ->where('status', 1)
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('admin.request_list', [
            'pendingRequests' => $pendingRequests,
            'approvedRequests' => $approvedRequests,
            'tab' => $request->query('tab', 'pending') // 追加：どっちのタブか判定用
        ]);
    }

    // ⑬ 修正申請承認画面（管理者向け：表示）
    public function showApproveForm($attendance_correct_request_id)
    {
        // 申請データを、紐づく休憩申請と一緒に取得
        $attendanceRequest = AttendanceRequest::with(['user', 'breakTimeRequests'])
            ->findOrFail($attendance_correct_request_id);

        return view('admin.approve_form', compact('attendanceRequest'));
    }

    // ⑬ 修正申請承認処理（管理者向け：更新）
    public function approve(Request $request, $attendance_correct_request_id)
    {
        // 1. 申請データを取得
        $attendanceRequest = AttendanceRequest::findOrFail($attendance_correct_request_id);

        // 2. 本番の勤怠データを取得
        $attendance = Attendance::findOrFail($attendanceRequest->attendance_id);

        // 3. 申請内容を本番テーブルへ上書きコピー！
        $attendance->update([
            'clock_in'  => $attendanceRequest->clock_in,
            'clock_out' => $attendanceRequest->clock_out,
            'remarks'   => $attendanceRequest->remarks,
        ]);

        // 4. 本番の休憩データを一度消して、申請されていた休憩内容を新しく作る
        $attendance->breakTimes()->delete();

        foreach ($attendanceRequest->breakTimeRequests as $breakReq) {
            $attendance->breakTimes()->create([
                'start_time' => $breakReq->start_time,
                'end_time'   => $breakReq->end_time,
            ]);
        }

        // 5. 申請のステータスを「承認済み(1)」にする
        $attendanceRequest->update(['status' => 1]);

        return redirect()->route('stamp_correction_request.list')->with('success', '申請を承認しました');
    }
}
