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
            ->paginate(15);

        // 3. 画面へ（前日・翌日の日付も添えて）
        return view('admin.attendance_list', [
            'attendances' => $attendances,
            'date' => $date, // （Carbonオブジェクトのまま渡すとViewで加工しやすい）
            'prevDate' => $date->copy()->subDay()->format('Y-m-d'),
            'nextDate' => $date->copy()->addDay()->format('Y-m-d'),
        ]);
    }


    // ⓽ 勤怠詳細画面（管理者向け：表示）
    public function showDetail($id, Request $request)// Requestを追加してuser_idを受け取る
    {
        // $id が日付形式（2026-02-13など）か、数字のIDかで処理を分ける
        if (str_contains($id, '-')) {
            // 日付なら、新規作成用の空モデルを作る
            $date = $id;
            $userId = $request->query('user_id'); // URLの ?user_id=... を取得
            
            $attendance = new Attendance([
                'date' => $date,
                'user_id' => $userId,
            ]);
            
            // user情報をセット（Viewで名前を表示させるため）
            $attendance->load('user'); 
            
            $isPending = false;
        } else {
            // 数字のIDなら、既存データを取得
            $attendance = Attendance::with(['user', 'breakTimes'])->findOrFail($id);
            // 承認待ちの申請があるかチェック
            $isPending = AttendanceRequest::where('attendance_id', $id)
                ->where('status', 0)// 0: 承認待ち
                ->exists();
        }

        return view('admin.attendance_detail', compact('attendance', 'isPending'));
    }

    // ⓽ 勤怠詳細画面（管理者向け：更新）
    public function update(AttendanceUpdateRequest $request, $id)
    {
        // 1. $id が日付なら「新規登録」、数字なら「更新」
        if (str_contains($id, '-')) {
            $attendance = new Attendance();
            $attendance->user_id = $request->user_id; // Formにhiddenでuser_idを入れておく必要あり
            $attendance->date = $id;
        } else {
            $attendance = Attendance::findOrFail($id);
        }

        // 2. 勤怠本番テーブルを保存・更新
        $attendance->clock_in  = $request->clock_in;
        $attendance->clock_out = $request->clock_out;
        $attendance->remarks   = $request->remarks;
        $attendance->save();

        // 3. 休憩テーブル（break_times）の更新
        // 申請ではなく管理者の直接修正なので、一度今の休憩を消して新しく作り直す
        //AttendanceModelのリレーション（public function breakTimes()）関数を呼び出し、その取り出したデータを削除。
        $attendance->breakTimes()->delete();

        if ($request->has('break_start')) {
            foreach ($request->break_start as $index => $startTime) {
                // 対になる終了時間を取得
                $endTime = $request->break_end[$index] ?? null;

                //  どちらか一方でも入力があれば保存を試みる
                // （バリデーションを通っているので、ここに来る時は両方入っているはず）
                if (!empty($startTime) || !empty($endTime)) {
                    $attendance->breakTimes()->create([
                        'start_time' => $startTime,
                        'end_time'   => $endTime,
                    ]);
                }
            }
        }

        // 保存後のリダイレクト先も、IDか日付かで適切に振り分ける
        $redirectId = $attendance->id ?: $attendance->date;

        return redirect()->route('admin.attendance.show', ['id' => $attendance->id])->with('success', '勤怠情報を修正しました');
    }


    // ⓾ スタッフ一覧画面（管理者向け：表示）
    public function showStaffList()
    {
        // userモデルから、一般ユーザー（role=0）だけを全員分取ってくる
        $staffs = User::where('role', 0)->paginate(15);

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

        // 3. 既存の勤怠データを日付をキーにして取得
        $dbAttendances = $user->attendances()
            ->whereBetween('date', [$startDate, $endDate])
            ->with('breakTimes')
            ->get()
            ->keyBy('date'); // 日付で検索しやすくする

        // 4. 1ヶ月分の全日付をループして、データがなければ空で作る
        $attendances = [];
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $dateStr = $date->format('Y-m-d');
            if ($dbAttendances->has($dateStr)) {
                $attendances[] = $dbAttendances[$dateStr];
            } else {
                // データがない日は空のAttendanceモデルを作る（保存はしない）
                $attendances[] = new Attendance([
                    'user_id' => $user->id,
                    'date' => $dateStr,
                ]);
            }
        }

        // 5. リンク用と表示用の月を作る
        $prevMonth = $currentMonth->copy()->subMonth()->format('Y-m');
        $nextMonth = $currentMonth->copy()->addMonth()->format('Y-m');
        $displayMonth = $currentMonth->format('Y/m');

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
            'tab' => $request->query('tab', 'pending') // どっちのタブか判定
        ]);
    }

    // ⑬ 修正申請承認画面（管理者向け：表示）
    public function showApproveForm($attendance_correct_request_id)
    {
        // 申請データを、紐づく休憩申請と一緒に取得
        $attendance = AttendanceRequest::with(['user', 'breakTimeRequests'])
            ->findOrFail($attendance_correct_request_id);

        // status が 0 なら「承認待ち」状態（$isPending = true）
        $isPending = ($attendance->status === 0);

        return view('admin.approve_form', compact('attendance', 'isPending'));
    }

    // ⑬ 修正申請承認処理（管理者向け：更新）
    public function approve(Request $request, $attendance_correct_request_id)
    {
        // 1. 申請データを取得
        $attendanceRequest = AttendanceRequest::findOrFail($attendance_correct_request_id);

        // 2. 本番の勤怠データを取得
        $attendance = Attendance::findOrFail($attendanceRequest->attendance_id);

        // 3. 申請内容を本番テーブルへ上書きコピー
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

        return redirect()->route('admin.stamp_correction_request.approve.show', ['attendance_correct_request_id' => $attendance_correct_request_id])->with('success', '申請を承認しました');
    }
}
