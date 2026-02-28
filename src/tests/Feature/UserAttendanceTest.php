<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\BreakTime;
use App\Models\BreakTimeRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class UserAttendanceTest extends TestCase
{
    use RefreshDatabase;

    /*～～～～～～～～～テストケースID9～～～～～～～～～*/
    /**
     * 1. 自分の勤怠情報のみが表示され、他人の情報が表示されない
     */
    public function test_user_can_see_only_own_attendance()
    {
        $me = User::factory()->create(['role' => 0]);
        $others = User::factory()->create(['role' => 0]);

        // 自分のデータ
        Attendance::create([
            'user_id' => $me->id,
            'date' => Carbon::today()->format('Y-m-d'),
            'clock_in' => '09:00:00',
        ]);

        // 他人のデータ
        Attendance::create([
            'user_id' => $others->id,
            'date' => Carbon::today()->format('Y-m-d'),
            'clock_in' => '10:00:00', // 他人の時間は10時
        ]);

        $response = $this->actingAs($me)->get('/attendance/list');

        $response->assertStatus(200);
        $response->assertSee('09:00'); // 自分の時間は見える
        $response->assertDontSee('10:00'); // 他人の時間は見えない
    }

    /**
     * 2. 初期状態で現在の月が表示される
     */
    public function test_attendance_list_shows_current_month_initially()
    {
        $user = User::factory()->create(['role' => 0]);
        $currentMonth = Carbon::now()->format('Y/m');

        $response = $this->actingAs($user)->get('/attendance/list');

        $response->assertSee($currentMonth);
    }

    /**
     * 3. 「前月」を押した時に表示月の前月の情報が表示される
     */
    public function test_can_switch_to_previous_month()
    {
        $user = User::factory()->create(['role' => 0]);
        $prevMonth = Carbon::now()->subMonth()->format('Y-m'); // 2026-02
        $prevMonthDisplay = Carbon::now()->subMonth()->format('Y/m'); // 2026/02

        // クエリパラメータ ?month=2026-02 を付けてアクセス
        $response = $this->actingAs($user)->get("/attendance/list?month={$prevMonth}");

        $response->assertSee($prevMonthDisplay);
    }

    /**
     * 4. 「翌月」を押した時に表示月の翌月の情報が表示される
     */
    public function test_can_switch_to_next_month()
    {
        $user = User::factory()->create(['role' => 0]);
        $nextMonth = Carbon::now()->addMonth()->format('Y-m'); // 2026-04
        $nextMonthDisplay = Carbon::now()->addMonth()->format('Y/m'); // 2026/04

        // クエリパラメータ ?month=2026-04 を付けてアクセス
        $response = $this->actingAs($user)->get("/attendance/list?month={$nextMonth}");

        $response->assertSee($nextMonthDisplay);
    }

    /**
     * 5. 「詳細」を押すとその日の詳細画面に遷移する
     */
    public function test_can_navigate_to_attendance_detail_page()
    {
        $user = User::factory()->create(['role' => 0]);
        
        // テスト用のデータを作成
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today()->format('Y-m-d'),
            'clock_in' => '09:00:00',
        ]);

        $response = $this->actingAs($user)->get('/attendance/list');

        // 正しいURL「/attendance/detail/{id}」が含まれているか確認！
        $response->assertSee("/attendance/detail/{$attendance->id}");
    }


    /*～～～～～～～～～テストケースID10～～～～～～～～～*/
    /**
     * 勤怠詳細：表示内容（名前・日付・時間）が正しいか
     */
    public function test_attendance_detail_displays_correct_info()
    {
        // 1. ユーザー作成
        $user = User::factory()->create([
            'name' => 'テスト太郎',
            'role' => 0
        ]);

        // 2. 勤怠データ作成（2026年3月1日のデータとする）
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date'    => '2026-03-01',
            'clock_in'  => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        // 3. 休憩データも作成
        BreakTime::create([
            'attendance_id' => $attendance->id,
            'start_time'    => '12:00:00',
            'end_time'      => '13:00:00',
        ]);

        // 4. 詳細画面へアクセス
        $response = $this->actingAs($user)->get("/attendance/detail/{$attendance->id}");

        // --- 検証 ---
        $response->assertStatus(200);

        // 名前が一致しているか
        $response->assertSee('テスト太郎');

        // 日付が一致しているか（コントローラーの表示形式に合わせて適宜調整）
        // 「2026年」「3月1日」がばらして表示される前提
        $response->assertSee('2026年');
        $response->assertSee('3月1日');

        // 出勤・退勤時間が一致しているか
        $response->assertSee('09:00');
        $response->assertSee('18:00');

        // 休憩時間が一致しているか
        $response->assertSee('12:00');
        $response->assertSee('13:00');
    }


    /*～～～～～～～～～テストケースID11～～～～～～～～～*/
    /**
     * バリデーション：出勤時間が退勤時間より後の場合
     */
    public function test_error_when_clock_in_is_after_clock_out()
    {
        $user = User::factory()->create(['role' => 0]);
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-03-01',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->actingAs($user)->post("/attendance/edit/{$attendance->id}", [
            'date' => '2026-03-01',
            'clock_in' => '19:00', 
            'clock_out' => '18:00',
            'remarks' => '修正します',
        ]);

        // フォームリクエストの messages() で定義した正確な文言に合わせる
        $response->assertSessionHasErrors([
            'clock_out' => '出勤時間もしくは退勤時間が不適切な値です'
        ]);
    }

    /**
     * バリデーション：休憩開始時間が退勤時間より後の場合
     */
    public function test_error_when_break_start_is_after_clock_out()
    {
        $user = User::factory()->create(['role' => 0]);
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-03-01',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->actingAs($user)->post("/attendance/edit/{$attendance->id}", [
            'date' => '2026-03-01',
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'break_start' => ['19:00'], // 退勤(18:00)より後
            'break_end' => ['20:00'],
            'remarks' => '修正します',
        ]);

        $response->assertSessionHasErrors(['break_start.0' => '休憩時間が不適切な値です']);
    }

    /**
     * バリデーション：備考欄が未入力の場合
     */
    public function test_error_when_remarks_is_empty()
    {
        $user = User::factory()->create(['role' => 0]);
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-03-01',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->actingAs($user)->post("/attendance/edit/{$attendance->id}", [
            'date' => '2026-03-01',
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'remarks' => '', // 未入力
        ]);

        $response->assertSessionHasErrors(['remarks' => '備考を記入してください']);
    }

    /**
     * 修正申請処理が実行され、DBに保存される
     */
    public function test_correction_request_is_stored_correctly()
    {
        $user = User::factory()->create(['role' => 0]);
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-03-01',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $this->actingAs($user)->post("/attendance/edit/{$attendance->id}", [
            'date' => '2026-03-01',
            'clock_in' => '10:00', 
            'clock_out' => '19:00',
            'remarks' => '遅刻したので修正します',
        ]);

        // 秒数まで含めた「10:00:00」で探す
        $this->assertDatabaseHas('attendance_requests', [
            'user_id' => $user->id,
            'clock_in' => '10:00:00',
            'status' => 0,
        ]);
    }

    /**
     * 申請一覧画面に自分の申請が表示されている
     */
    public function test_user_can_see_own_requests_in_list()
    {
        $user = User::factory()->create(['role' => 0]);
        
        // 500エラー回避：attendance_id をちゃんと紐付ける
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-03-01',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        AttendanceRequest::create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id, 
            'date' => '2026-03-01',
            'clock_in' => '10:00:00',
            'clock_out' => '19:00:00',
            'remarks' => '承認待ちテスト',
            'status' => 0,
        ]);

        $response = $this->actingAs($user)->get('/stamp_correction_request/list');

        $response->assertStatus(200);
        $response->assertSee('承認待ちテスト');
    }

}