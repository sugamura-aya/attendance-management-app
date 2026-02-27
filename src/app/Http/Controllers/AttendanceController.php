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

        // 2. 【重要：当日の勤怠記録探し】
        // 「当日の自分の出勤データ」を探し出す（休憩テーブルに「何番の出勤に紐づく休憩か」を記録する必要があるため）
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
        $displayMonth = $currentMonth->format('Y/m');

        // 2. 【DBから既存データを取得（あとで検索しやすいように keyBy する）】
        $dbAttendances = Attendance::where('user_id', Auth::id())
            ->whereBetween('date', [$startDate, $endDate])
            ->with('breakTimes')
            ->get()
            ->keyBy('date'); // 日付をキーにして取り出しやすくする

        // 3. 【1日から末日までループして、全日付のリストを作る】
        $attendances = [];
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $dateStr = $date->format('Y-m-d');
            
            // その日のデータがあればそれを使う、なければ空のインスタンスを作る
            if (isset($dbAttendances[$dateStr])) {
                $attendances[] = $dbAttendances[$dateStr];
            } else {
                // DBにない日も、日付だけ持ったモデルを仮で作る
                $attendances[] = new Attendance([
                    'date' => $dateStr,
                    'user_id' => Auth::id(),
                ]);
            }
        }

        return view('attendance.list', compact('attendances', 'displayMonth', 'prevMonth', 'nextMonth'));
    }

    // ➄ 勤怠詳細（表示）
    public function show($id)
    {
        // $idがハイフンを含んでいる（日付 2026-02-15 などの形式）かチェック
        if (str_contains($id, '-')) {
            // 日付で検索
            $attendance = Attendance::where('user_id', Auth::id())
                ->where('date', $id)
                ->with(['user', 'breakTimes'])
                ->first();
        
            // 取得できなかったら（新規登録用）、その日付で空のモデルを作る
            if (!$attendance) {
                    $attendance = new Attendance([
                        'date' => $id,
                        'user_id' => Auth::id(),
                    ]);
                    $attendance->setRelation('user', Auth::user());
                    $attendance->setRelation('breakTimes', collect());
            }
        } else {
            // ID（数字）で検索
            $attendance = Attendance::with(['user', 'breakTimes'])->findOrFail($id);
        }

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
        //dd($request->all());

        // 1. $id が日付形式（ハイフン入り）かチェックして、仕分けする
        if (str_contains($id, '-')) {
            $attendanceId = null; // まだ本番データがないので null
            $redirectId = $id;    // リダイレクト先は日付を使う
        } else {
            $attendanceId = $id;  // 既存の勤怠IDを入れる
            $redirectId = $id;    // リダイレクト先はIDを使う
        }

        // 2. 【勤怠申請】を保存
        $attendanceRequest = new AttendanceRequest(); 
        $attendanceRequest->user_id = Auth::id();
        
        // 判定した $attendanceId を入れる
        $attendanceRequest->attendance_id = $attendanceId; 
        
        $attendanceRequest->date = $request->date;
        $attendanceRequest->clock_in = $request->clock_in;
        $attendanceRequest->clock_out = $request->clock_out;
        $attendanceRequest->remarks = $request->remarks;
        $attendanceRequest->status = 0;
        $attendanceRequest->save();

        // 2. 【休憩申請】を保存
        if ($request->has('break_start')) {
            foreach ($request->break_start as $index => $startTime) {
                if (empty($startTime) && empty($request->break_end[$index])) {
                    continue; 
                }
                $breakRequest = new BreakTimeRequest();
                $breakRequest->attendance_request_id = $attendanceRequest->id;
                $breakRequest->start_time = $startTime;
                $breakRequest->end_time = $request->break_end[$index] ?? null;
                $breakRequest->save();
            }
        }

        // リダイレクト先も $redirectId にしておく
        return redirect()->route('attendance.show', ['id' => $redirectId])
            ->with('success', '修正申請を提出しました。承認されるまで元の勤怠データが表示されます。');
        }
}
