<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\BreakTime;
use App\Models\BreakTimeRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Carbon\Carbon;

class AttendanceDisplayTest extends TestCase
{
    use RefreshDatabase;

    /*～～～～～～～～～テストケースID4～～～～～～～～～*/
    /**
     * 【ユーザー側】➀打刻画面 
     * 日時取得機能：現在の日時情報がUIと同じ形式で出力されている
     */
    public function test_current_date_is_displayed_correctly()
    {
        // 1. 日本語の曜日を使えるように設定
        Carbon::setLocale('ja');

        // 2. ユーザーを用意する時に、roleを0（一般ユーザー）に指定する
        $user = User::factory()->create([
            'role' => 0, 
        ]);

        // 3. 2のユーザーとしてログインして打刻画面を開く
        $response = $this->actingAs($user)->get('/attendance');

        // 4. 画面上の期待される日時を作成
        // 「2026年2月28日(土)」の形式に精密に合わせる
        $now = Carbon::now();
        $expectedDate = $now->format('Y年n月j日') . '(' . $now->isoFormat('ddd') . ')';

        // 5. チェック
        $response->assertStatus(200);
        $response->assertSee($expectedDate);
    }

    /**
     * 【ユーザー側】➁勤怠一覧画面 
     * 日時取得機能：現在の日時情報がUIと同じ形式で出力されている
     */
    public function test_attendance_list_page_displays_dates_correctly()
    {
        Carbon::setLocale('ja');

        // 1. 一般ユーザー（role=0）を作成
        $user = User::factory()->create([
            'role' => 0,
        ]);

        // 2. 今日の日付のデータを作っておく
        $now = Carbon::now();
        Attendance::create([
            'user_id' => $user->id,
            'date'    => $now->format('Y-m-d'), // 2026-02-28
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        // 3. 勤怠一覧画面へ
        $response = $this->actingAs($user)->get('/attendance/list');

        // 4. 画面上の期待される日時を作成
        $now = Carbon::now();
        
        // （見出し・月選択用）2026/02 形式
        $expectedMonth = $now->format('Y/m');
        
        // （一覧用）02/01(日) 形式 
        // 「今日」の日付で一覧に表示される想定
        $expectedListDate = $now->format('m/d') . '(' . $now->isoFormat('ddd') . ')';

        // 5. チェック
        $response->assertStatus(200);
        $response->assertSee($expectedMonth);      // 「2026/02」があるか？
        $response->assertSee($expectedListDate);   // 「02/28(土)」などがあるか？
    }

    /**
     * 【ユーザー側】➂勤怠詳細画面 
     * 日時取得機能：現在の日時情報がUIと同じ形式で出力されている
     */
    public function test_attendance_detail_page_displays_dates_correctly()
    {
        Carbon::setLocale('ja');

        // 1. 一般ユーザー（role=0）を作成
        $user = User::factory()->create(['role' => 0]);

        // 2. このユーザーに紐づく「打刻データ」を1件作成
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date'    => '2026-02-01',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        // 3. 詳細画面へ（URLの {id} の部分に、今作ったデータのIDを入れる！）
        $response = $this->actingAs($user)->get("/attendance/detail/{$attendance->id}");

        // 4. チェックしたい日時の文字列を分ける
        // ※「年」と「月日」は別々に表示しているため分けてチェックする
        $expectedYear  = '2026年';
        $expectedDay   = '2月1日';
        $expectedTime  = '09:00';

        // 5. チェック
        $response->assertStatus(200);
        $response->assertSee($expectedYear); // 「2026年」があるか？
        $response->assertSee($expectedDay);  // 「2月1日」があるか？
        $response->assertSee($expectedTime); // 「09:00」があるか？
    }

    /**
     * 【ユーザー側】➃申請一覧画面 
     * 日時取得機能：修正申請と休憩申請の日時がUIと同じ形式で出力されている
     */
    public function test_user_correction_request_list_page_displays_dates_correctly()
    {
        Carbon::setLocale('ja');

        // 1. 一般ユーザー（role=0）を作成
        $user = User::factory()->create(['role' => 0]);

        // 2. 勤怠データを作成（親データ）
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date'    => '2026-02-01',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        // 3. 【勤怠】修正申請データを作成
        $attendanceRequest = AttendanceRequest::create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'status' => 0,
            'date'   => '2026-02-01',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'remarks' => '打刻ミス', 
        ]);

        // 4. 【休憩】修正申請データを作成
        // 親の ID ($attendanceRequest->id) をセット
        BreakTimeRequest::create([
            'attendance_request_id' => $attendanceRequest->id, 
            'start_time' => '12:00:00',
            'end_time'   => '13:00:00',
        ]);

        // 5. 申請一覧画面へ
        $response = $this->actingAs($user)->get('/stamp_correction_request/list');

        // 6. チェックしたい日時の文字列（2026/02/01）
        $expectedDate = '2026/02/01';

        // 7. チェック
        $response->assertStatus(200);
        $response->assertSee($expectedDate);
    }



    /**
     * 【管理者】5つの画面ですべて正しく日付・時間が表示されるか
     */
    public function test_admin_all_pages_display_correct_datetime()
    {
        // 1. 下準備（管理者・一般ユーザー・検証用パーツ・勤怠・申請データ）
        $admin = User::factory()->create(['role' => 1]);
        $user  = User::factory()->create(['role' => 0]);
        
        // 検証用パーツ
        $now = \Carbon\Carbon::now(); 
        $todayDb = $now->format('Y-m-d'); // 例）2026-03-01
        $expectedY  = $now->format('Y年');    // 2026年
        $expectedMj = $now->format('n月j日'); // 3月1日（0埋めなし）
        $expectedS  = $now->format('Y/m/d');   // 2026/03/01
        $expectedYM = $now->format('Y/m');     // 2026/03
        $expectedMD = $now->format('m/d');     // 03/01（スタッフ別用）

        // 今日の勤怠データを作成
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date'    => $todayDb,
            'clock_in'  => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        // 休憩データ (1時間)
        BreakTime::create([
            'attendance_id' => $attendance->id,
            'start_time'    => '12:00:00',
            'end_time'      => '13:00:00',
        ]);

        // 修正申請データ
        $attRequest = AttendanceRequest::create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'status'  => 0,
            'date'    => $todayDb,
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'remarks' => '修正願い',
        ]);

        // --- 実行＆検証 ---
        $this->actingAs($admin);

        // ➀ 勤怠一覧画面【ばらし不要：2026/03/01】
        $res1 = $this->get('/admin/attendance/list');
        $res1->assertSee($expectedS)->assertSee('09:00')->assertSee('1:00');

        // ➁ 勤怠詳細画面【ばらし必要！：2026年 / 3月1日】
        $res2 = $this->get("/admin/attendance/{$attendance->id}");
        $res2->assertSee($expectedY)->assertSee($expectedMj)->assertSee('09:00');

        // ➂ スタッフ別勤怠一覧画面 【ばらし不要：2026/03 と 03/01】
        $res3 = $this->get("/admin/attendance/staff/{$user->id}");
        $res3->assertSee($expectedYM)->assertSee($expectedMD)->assertSee('09:00');

        // ➃ 勤怠申請画面（申請一覧）【ばらし不要：2026/03/01】
        $res4 = $this->get('/stamp_correction_request/list');
        $res4->assertSee($expectedS);

        // ➄ 修正申請承認画面【ばらし必要！：2026年 / 3月1日】
        $res5 = $this->get("/stamp_correction_request/approve/{$attRequest->id}");
        $res5->assertSee($expectedY)->assertSee($expectedMj)->assertSee('09:00');
    }


    /*～～～～～～～～～テストケースID5～～～～～～～～～*/
    /**
     * 【ユーザー側】ステータス確認：勤務外（データなし）
     */
    public function test_status_is_off_work_initially()
    {
        $user = User::factory()->create(['role' => 0]);
        // 今日はまだ何もしていない状態

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('勤務外');
    }

    /**
     * 【ユーザー側】ステータス確認：出勤中（出勤データあり、休憩なし）
     */
    public function test_status_is_working_after_clock_in()
    {
        $user = User::factory()->create(['role' => 0]);
        
        // 今日の出勤データを作成（clock_outは空）
        Attendance::create([
            'user_id' => $user->id,
            'date'    => \Carbon\Carbon::today()->format('Y-m-d'),
            'clock_in' => '09:00:00',
        ]);

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertSee('出勤中');
    }

    /**
     * 【ユーザー側】ステータス確認：休憩中（休憩終了時間が空）
     */
    public function test_status_is_breaking_during_break()
    {
        $user = User::factory()->create(['role' => 0]);
        
        // 出勤データ作成
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date'    => \Carbon\Carbon::today()->format('Y-m-d'),
            'clock_in' => '09:00:00',
        ]);

        // 休憩開始データ作成（end_timeは空）
        BreakTime::create([
            'attendance_id' => $attendance->id,
            'start_time'    => '12:00:00',
        ]);

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertSee('休憩中');
    }

    /**
     * 【ユーザー側】ステータス確認：退勤済（退勤時間に値あり）
     */
    public function test_status_is_finished_after_clock_out()
    {
        $user = User::factory()->create(['role' => 0]);
        
        // 退勤済みのデータを作成
        Attendance::create([
            'user_id' => $user->id,
            'date'    => \Carbon\Carbon::today()->format('Y-m-d'),
            'clock_in'  => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertSee('退勤済');
    }
}
