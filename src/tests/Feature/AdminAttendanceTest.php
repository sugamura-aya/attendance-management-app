<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceRequest;
use Carbon\Carbon;

class AdminAttendanceTest extends TestCase
{
    use RefreshDatabase;

    /*～～～～～～～～～テストケースID12～～～～～～～～～*/
    /**
     * 管理者：その日の全ユーザーの勤怠情報が正確に確認できる
     */
    public function test_admin_can_see_all_users_attendance_for_the_day()
    {
        // 1. 管理者（role:1）と一般ユーザー（role:0）を作成
        $admin = User::factory()->create(['role' => 1]);
        $user = User::factory()->create(['name' => 'テスト太郎', 'role' => 0]);

        // 2. 今日の勤怠データを作成
        $today = Carbon::today()->format('Y-m-d');
        Attendance::create([
            'user_id' => $user->id,
            'date' => $today,
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        // 3. 管理者としてログインして一覧画面へ
        $response = $this->actingAs($admin)->get('/admin/attendance/list');

        $response->assertStatus(200);
        $response->assertSee('テスト太郎');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    /**
     * 管理者：遷移した際に現在の日付が表示される
     */
    public function test_admin_list_shows_current_date_initially()
    {
        $admin = User::factory()->create(['role' => 1]);
        
        // テスト上の「今日」を固定
        $fixedDate = Carbon::create(2026, 3, 1);
        Carbon::setTestNow($fixedDate);

        $response = $this->actingAs($admin)->get('/admin/attendance/list');

        $response->assertStatus(200);
        // ビューでの日付表示形式
        $response->assertSee($fixedDate->isoFormat('YYYY年M月D日')); 
    }

    /**
     * 管理者：「前日」を押下した時に前の日の勤怠情報が表示される
     */
    public function test_admin_can_switch_to_previous_day()
    {
        $admin = User::factory()->create(['role' => 1]);
        
        $previousDay = Carbon::today()->subDay()->format('Y-m-d');
        
        $response = $this->actingAs($admin)->get("/admin/attendance/list?date={$previousDay}");

        $response->assertStatus(200);
        $response->assertSee(Carbon::today()->subDay()->isoFormat('YYYY年M月D日'));
    }

    /**
     * 管理者：「翌日」を押下した時に次の日の勤怠情報が表示される
     */
    public function test_admin_can_switch_to_next_day()
    {
        $admin = User::factory()->create(['role' => 1]);

        $nextDay = Carbon::today()->addDay()->format('Y-m-d');
        
        $response = $this->actingAs($admin)->get("/admin/attendance/list?date={$nextDay}");

        $response->assertStatus(200);
        $response->assertSee(Carbon::today()->addDay()->isoFormat('YYYY年M月D日'));
    }


    /*～～～～～～～～～テストケースID13～～～～～～～～～*/
    /**
     * 管理者：勤怠詳細画面に表示されるデータが選択したものになっている
     */
    public function test_admin_can_see_specific_attendance_detail()
    {
        $admin = User::factory()->create(['role' => 1]);
        $user = User::factory()->create(['role' => 0]);
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-03-01',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->actingAs($admin)->get("/admin/attendance/{$attendance->id}");

        $response->assertStatus(200);
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    /**
     * 管理者バリデーション：出勤時間が退勤時間より後の場合
     */
    public function test_admin_error_when_clock_in_is_after_clock_out()
    {
        $admin = User::factory()->create(['role' => 1]);
        $user = User::factory()->create(['role' => 0]);
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-03-01',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->actingAs($admin)->patch("/admin/attendance/{$attendance->id}", [
            'clock_in' => '19:00', 
            'clock_out' => '18:00',
            'remarks' => '管理者修正',
        ]);

        $response->assertSessionHasErrors(['clock_out' => '出勤時間もしくは退勤時間が不適切な値です']);
    }

    /**
     * 管理者バリデーション：休憩開始時間が退勤時間より後の場合
     */
    public function test_admin_error_when_break_start_is_after_clock_out()
    {
        $admin = User::factory()->create(['role' => 1]);
        $user = User::factory()->create(['role' => 0]);
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-03-01',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->actingAs($admin)->patch("/admin/attendance/{$attendance->id}", [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'break_start' => ['19:00'], 
            'break_end' => ['20:00'],
            'remarks' => '管理者修正',
        ]);

        $response->assertSessionHasErrors(['break_start.0' => '休憩時間が不適切な値です']);
    }

    /**
     * 管理者バリデーション：休憩終了時間が退勤時間より後の場合
     */
    public function test_admin_error_when_break_end_is_after_clock_out()
    {
        $admin = User::factory()->create(['role' => 1]);
        $user = User::factory()->create(['role' => 0]);
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-03-01',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->actingAs($admin)->patch("/admin/attendance/{$attendance->id}", [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'break_start' => ['10:00'],
            'break_end' => ['19:00'], 
            'remarks' => '管理者修正',
        ]);

        $response->assertSessionHasErrors(['break_end.0' => '休憩時間もしくは退勤時間が不適切な値です']);
    }

    /**
     * 管理者バリデーション：備考欄が未入力の場合
     */
    public function test_admin_error_when_remarks_is_empty()
    {
        $admin = User::factory()->create(['role' => 1]);
        $user = User::factory()->create(['role' => 0]);
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-03-01',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->actingAs($admin)->patch("/admin/attendance/{$attendance->id}", [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'remarks' => '', 
        ]);

        $response->assertSessionHasErrors(['remarks' => '備考を記入してください']);
    }


    /*～～～～～～～～～テストケースID14～～～～～～～～～*/
    /**
     * 管理者：スタッフ一覧ページで全一般ユーザーの氏名とメールアドレスが確認できる
     */
    public function test_admin_can_see_all_staff_info()
    {
        $admin = User::factory()->create(['role' => 1]);
        $user1 = User::factory()->create(['name' => 'スタッフA', 'email' => 'staff_a@example.com', 'role' => 0]);
        $user2 = User::factory()->create(['name' => 'スタッフB', 'email' => 'staff_b@example.com', 'role' => 0]);

        $response = $this->actingAs($admin)->get('/admin/staff/list');

        $response->assertStatus(200);
        $response->assertSee('スタッフA');
        $response->assertSee('staff_a@example.com');
        $response->assertSee('スタッフB');
        $response->assertSee('staff_b@example.com');
    }

    /**
     * 管理者：選択したユーザーの勤怠一覧ページが正確に表示される
     */
    public function test_admin_can_see_specific_staff_attendance_list()
    {
        $admin = User::factory()->create(['role' => 1]);
        $user = User::factory()->create(['role' => 0]);
        
        // そのユーザーの勤怠データを作成
        Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-03-01',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->actingAs($admin)->get("/admin/attendance/staff/{$user->id}");

        $response->assertStatus(200);
        $response->assertSee('03/01');
        $response->assertSee('09:00');
    }

    /**
     * 管理者：スタッフ別勤怠一覧で「前月」に切り替えられる
     */
    public function test_admin_can_switch_staff_attendance_to_previous_month()
    {
        $admin = User::factory()->create(['role' => 1]);
        $user = User::factory()->create(['role' => 0]);
        
        // 2月のデータを表示させたい
        $response = $this->actingAs($admin)->get("/admin/attendance/staff/{$user->id}?month=2026-02");

        $response->assertStatus(200);
        // 2月の表示（例：2026年2月）が含まれているか確認
        $response->assertSee('2026');
        $response->assertSee('2'); 
    }

    /**
     * 管理者：スタッフ別勤怠一覧で「翌月」に切り替えられる
     */
    public function test_admin_can_switch_staff_attendance_to_next_month()
    {
        $admin = User::factory()->create(['role' => 1]);
        $user = User::factory()->create(['role' => 0]);
        
        $response = $this->actingAs($admin)->get("/admin/attendance/staff/{$user->id}?month=2026-04");

        $response->assertStatus(200);
        $response->assertSee('2026');
        $response->assertSee('4');
    }

    /**
     * 管理者：スタッフ別勤怠一覧から「詳細」を押すと、その日の詳細画面に遷移する
     */
    public function test_admin_can_navigate_to_detail_from_staff_list()
    {
        $admin = User::factory()->create(['role' => 1]);
        $user = User::factory()->create(['role' => 0]);
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-03-01',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->actingAs($admin)->get("/admin/attendance/staff/{$user->id}");

        // 詳細画面へのリンク（/admin/attendance/{id}）が存在するか確認
        $response->assertSee("/admin/attendance/{$attendance->id}");
    }


    /*～～～～～～～～～テストケースID15～～～～～～～～～*/
    /**
     * 管理者：修正申請一覧（承認待ち）に未承認の申請が表示される
     */
    public function test_admin_can_see_pending_requests()
    {
        $admin = User::factory()->create(['role' => 1]);
        $user = User::factory()->create(['name' => '申請スタッフ']);
        // 紐付ける勤怠データを先に作る
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-03-01',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);
        
        $request = AttendanceRequest::create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id, // 作成したIDを使う
            'date' => '2026-03-01',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'status' => 0, 
            'remarks' => '打刻忘れのため',
        ]);

        $response = $this->actingAs($admin)->get('/stamp_correction_request/list?status=pending');

        $response->assertStatus(200);
        $response->assertSee('申請スタッフ');
        $response->assertSee('打刻忘れのため');
    }

    /**
     * 管理者：修正申請一覧（承認済み）に承認済みの申請が表示される
     */
    public function test_admin_can_see_approved_requests()
    {
        $admin = User::factory()->create(['role' => 1]);
        $user = User::factory()->create(['name' => '承認済みスタッフ']);
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-03-01',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);
        
        $request = AttendanceRequest::create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'date' => '2026-03-01',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'status' => 1, 
            'remarks' => '修正完了分',
        ]);

        $response = $this->actingAs($admin)->get('/stamp_correction_request/list?tab=approved');

        $response->assertStatus(200);
        $response->assertSee('承認済みスタッフ');
        $response->assertSee('修正完了分');
    }

    /**
     * 管理者：修正申請の詳細内容が正しく表示される
     */
    public function test_admin_can_see_request_detail()
    {
        $admin = User::factory()->create(['role' => 1]);
        $user = User::factory()->create();
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-03-01',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $request = AttendanceRequest::create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'date' => '2026-03-01',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'status' => 0,
            'remarks' => '詳細確認用',
        ]);

        $response = $this->actingAs($admin)->get("/stamp_correction_request/approve/{$request->id}");

        $response->assertStatus(200);
        $response->assertSee('詳細確認用');
    }

    /**
     * 管理者：承認処理が正しく行われ、勤怠情報が更新される
     */
    public function test_admin_can_approve_request()
    {
        $admin = User::factory()->create(['role' => 1]);
        $attendance = Attendance::create([
            'user_id' => User::factory()->create()->id,
            'date' => '2026-03-01',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $request = AttendanceRequest::create([
            'user_id' => $attendance->user_id,
            'attendance_id' => $attendance->id,
            'date' => '2026-03-01',
            'status' => 0,
            'clock_in' => '10:00:00', // 10時に修正したい
            'clock_out' => '19:00:00',
            'remarks' => '電車遅延',
        ]);

        // ★Route::patch なので patch() メソッドを使う
        $response = $this->actingAs($admin)->patch("/stamp_correction_request/approve/{$request->id}");

        // 承認後の遷移先を確認
        $response->assertStatus(302);
        
        // 1. 申請のステータスが「承認済み(1)」になっているか
        $this->assertDatabaseHas('attendance_requests', [
            'id' => $request->id,
            'status' => 1
        ]);

        // 2. 元の勤怠データが申請内容で更新されているか
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'clock_in' => '10:00:00',
            'clock_out' => '19:00:00'
        ]);
    }
}