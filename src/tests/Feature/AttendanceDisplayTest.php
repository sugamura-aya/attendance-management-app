<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\BreakTimeRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Carbon\Carbon;

class AttendanceDisplayTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 【ユーザー側】①打刻画面 
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
     * 【ユーザー側】②勤怠一覧画面 
     * 日時取得機能：現在の日時情報がUIと同じ形式で出力されている
     */
    public function test_attendance_list_page_displays_dates_correctly()
    {
        Carbon::setLocale('ja');

        // 1. 一般ユーザー（role=0）を作成
        $user = User::factory()->create([
            'role' => 0,
        ]);

        // 2. 勤怠一覧画面へ
        $response = $this->actingAs($user)->get('/attendance/list');

        // 3. 画面上の期待される日時を作成
        $now = Carbon::now();
        
        // （見出し・月選択用）2026/02 形式
        $expectedMonth = $now->format('Y/m');
        
        // （一覧用）02/01(日) 形式 
        // 「今日」の日付で一覧に表示される想定
        $expectedListDate = $now->format('m/d') . '(' . $now->isoFormat('ddd') . ')';

        // 4. チェック
        $response->assertStatus(200);
        $response->assertSee($expectedMonth);      // 「2026/02」があるか？
        $response->assertSee($expectedListDate);   // 「02/28(土)」などがあるか？
    }

    /**
     * 【ユーザー側】③勤怠詳細画面 
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
     * 【ユーザー側】⑥申請一覧画面 
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
}
