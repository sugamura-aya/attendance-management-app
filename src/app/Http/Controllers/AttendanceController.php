<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;// フォームの入力値などをまとめて運ぶためのコード
use Illuminate\Support\Facades\Auth; 
use Carbon\Carbon; // 日付や時刻の計算・加工に必須
use App\Models\Attendance; 
use App\Models\BreakTime;  
use App\Models\AttendanceRequest; 
use App\Models\BreakTimeRequest; 
use App\Http\Requests\AttendanceUpdateRequest;

class AttendanceController extends Controller
{
    // ➂ 出勤登録画面（表示）
    public function index()
    {
        // 1. 【年月日・時刻の表示用】
        // Carbonを使って「今」の情報を取得
        $today = Carbon::now()->isoFormat('YYYY年M月D日(ddd)');
        $now = Carbon::now()->format('H:i');

        // 2. 【今日の勤務状態のチェック】
        // ログインユーザーの「今日」の勤怠データを1件探す
        $attendance = Attendance::where('user_id', Auth::id())
            ->whereDate('date', Carbon::today())
            ->first();

        // 3. 【ステータスの判定ロジック】
        // 最初は「勤務外」→状況に応じて上書き
        $status = '勤務外';

        if (!$attendance) {
            // データがないなら、まだ何もしてない「勤務外」
            $status = '勤務外';
        } elseif ($attendance->clock_out) {
            // 退勤時間に値が入っているなら「退勤済」
            $status = '退勤済';
        } else {
            // 退勤してない場合、次に「休憩中」かどうかを調べる
            // 一番新しい（最新の）休憩データを1つ取る
            $latestBreak = BreakTime::where('attendance_id', $attendance->id)
                ->latest() //日付（作成日）が新しい順に並べる（最新が一番上に来る）
                ->first(); //その一番上の1件だけ取ってくる

            // 休憩開始(start_time)があって、終了(end_time)が空なら「休憩中」
            if ($latestBreak && !$latestBreak->end_time) {
                $status = '休憩中';
            } else {
                // それ以外（休憩してない、または戻ってる）なら「出勤中」
                $status = '出勤中';
            }
        }

        // 4. 【Viewへデータを送る】
        // 判定した結果をindex.blade.php に送る
        return view('attendance.index', compact('today', 'now', 'status'));
    }

    // 出勤登録（POST）
    public function clockIn(Request $request)
    {
        // 1.「出勤」を押された時の日時情報を取得
        $now = Carbon::now();

        // 2. 勤怠テーブル(Attendance)に新しいデータを登録
        Attendance::create([
            'user_id'  => Auth::id(),        // 「誰が」：今ログインしてる人
            'date'     => $now->toDateString(), // 「いつ」：今日の日付(「日付（Date）の文字列（String）に変身（to）」) 
            'clock_in' => $now->toTimeString(), // 「何時に」：今の時刻(「時間（Time）の文字列（String）に変身（to）」)
        ]);

        // 保存処理
        return redirect()->route('attendance.index');
    }

    // 退勤登録（POST）
    public function clockOut(Request $request)
    {
        // 1.「退勤」ボタンが押された日時情報を取得
        $now = Carbon::now();

        // 2.【重要：当日の勤怠記録探し】当日の勤怠レコードを探し出す
        //「出勤」の行を特定して、そこに退勤時間を書き足す
        $attendance = Attendance::where('user_id', Auth::id()) // 「誰の」：ログイン中の私
            ->whereDate('date', Carbon::today()) // 「いつの」：今日
            ->first(); // その1件を取得

        // 3. もし「出勤」のデータが見つかったら、その行を「更新」する
        if ($attendance) {
            $attendance->update([
                'clock_out' => $now->toTimeString(), 
            ]);
        }
    
        // 保存処理
        return redirect()->route('attendance.index');
    }

    // 休憩開始（POST） 
    public function store(Request $request)
    {
        // 1.「休憩入」ボタンが押された日時情報を取得
        $now = Carbon::now();

        // 2. 【重要：当日の勤怠記録探し】「当日の自分の出勤データ」を探し出す
        // ➔ 休憩テーブルに「何番の出勤に紐づく休憩か」を記録する必要があるから
        $attendance = Attendance::where('user_id', Auth::id())
            ->whereDate('date', Carbon::today())
            ->first();

        // 3. もし出勤データが見つかったら、休憩を「新規作成」する
        if ($attendance) {
            BreakTime::create([
                'attendance_id' => $attendance->id,      // ここで「出勤データのID」を渡す
                'start_time'    => $now->toTimeString(), // 今の時間を「開始時間」に入れる
            ]);
        }

        // 保存処理
        return redirect()->route('attendance.index');
    }

    // 休憩終了（POST） 
    public function update(Request $request)
    {
        // 1.「休憩戻」ボタンが押された日時情報を取得
        $now = Carbon::now();

        // 2. 【重要：当日の勤怠記録探し】「当日の自分の出勤データ」を探し出す
        $attendance = Attendance::where('user_id', Auth::id())
            ->whereDate('date', Carbon::today())
            ->first();

        if ($attendance) {
            // 3. 【重要】休憩テーブルから「一番新しい(latest)1件」を探し出す
            $latestBreak = BreakTime::where('attendance_id', $attendance->id)
                ->latest() // 「最新を一番上」に並べ替え（IDが一番大きい＝一番最後に作った休憩）
                ->first(); // 「一番上の1件を取得」

            // 4. 休憩データが見つかり、かつ「終了時間がまだ空」なら更新
            if ($latestBreak && !$latestBreak->end_time) {
                $latestBreak->update([
                    'end_time' => $now->toTimeString(),
                ]);
            }
        }

        // 保存処理
        return redirect()->route('attendance.index');
    }

    // ➃ 勤怠一覧画面（表示）
    public function list(Request $request)
    {
        // 1. 【表示したい月を決める】
        $targetMonthStr = $request->input('month', Carbon::now()->format('Y-m'));
        $currentMonth = Carbon::parse($targetMonthStr);

        $startDate = $currentMonth->copy()->startOfMonth();
        $endDate = $currentMonth->copy()->endOfMonth();

        //  前月と翌月の日付を作っておく（リンク用）
        $prevMonth = $currentMonth->copy()->subMonth()->format('Y-m');
        $nextMonth = $currentMonth->copy()->addMonth()->format('Y-m');
        
        // 表示用の月名 (2026/2 の形)
        $displayMonth = $currentMonth->format('Y/m');

        // 2. 【データの取得】
        $attendances = Attendance::where('user_id', Auth::id())
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'asc')
            ->with('breakTimes') 
            ->get();

        return view('attendance.list', compact('attendances', 'displayMonth', 'prevMonth', 'nextMonth'));
    }

    // ➄ 勤怠詳細（表示）
    public function show($id)
    {
        // 指定されたIDの勤怠データを1件だけ取ってくる(紐づく休憩データも併せて取得)
        // $idはAttendanceのidを指す
        $attendance = Attendance::with('breakTimes')->findOrFail($id);

        //「承認待ち申請」を探す
        $isPending = AttendanceRequest::where('user_id', Auth::id())
            ->where('date', $attendance->date) // その勤怠と同じ日付
            ->where('status', 0)               // 承認待ち
            ->exists(); // 存在するかどうかだけチェック

        return view('attendance.detail', compact('attendance', 'isPending'));
    }

    // 申請登録処理（POST）
    public function storeRequest(AttendanceUpdateRequest $request, $id)
    {
        // 1. 【勤怠申請】を保存（親）
        $attendanceRequest = new AttendanceRequest(); 
        $attendanceRequest->user_id = Auth::id();
        $attendanceRequest->attendance_id = $id; // どの勤怠に対する申請か
        $attendanceRequest->date = $request->date;
        $attendanceRequest->clock_in = $request->clock_in;
        $attendanceRequest->clock_out = $request->clock_out;
        $attendanceRequest->remarks = $request->remarks; // 備考
        $attendanceRequest->status = 0; // 承認待ち
        $attendanceRequest->save();

        // 2. 【休憩申請】を保存（子）
        // 画面から送られてきた複数の休憩データを保存する
        // $request->break_start という配列があるかチェックして回す
        if ($request->has('break_start')) {
            foreach ($request->break_start as $index => $startTime) {
                // 開始も終了も空っぽなら、その休憩は保存（申請）せずにスルーする
                if (empty($startTime) && empty($request->break_end[$index])) {
                    continue; 
                }

                $breakRequest = new BreakTimeRequest();
                $breakRequest->attendance_request_id = $attendanceRequest->id;
                $breakRequest->start_time = $startTime;
                // 対応する end_time を取得
                $breakRequest->end_time = $request->break_end[$index] ?? null;
                $breakRequest->save();
            }
        }

        return redirect()->route('attendance.show', ['id' => $id])->with('success', '修正申請を提出しました');
    }
}
