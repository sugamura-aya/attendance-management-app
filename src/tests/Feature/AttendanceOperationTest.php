<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class AttendanceOperationTest extends TestCase
{
    use RefreshDatabase;

    /*～～～～～～～～～テストケースID6～～～～～～～～～*/
    /**
     * 出勤機能：出勤ボタンを押すと正しく記録される
     */
    public function test_clock_in_is_recorded_correctly()
    {
        $user = User::factory()->create(['role' => 0]);
        
        // 出勤ボタン（POST）を叩く
        $response = $this->actingAs($user)->post('/attendance/clock-in');

        // 1. 打刻画面へリダイレクトされるか
        $response->assertRedirect('/attendance');

        // 2. DBに今日の出勤データが保存されているか
        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'date'    => Carbon::today()->toDateString(),
        ]);
    }


    /*～～～～～～～～～テストケースID7～～～～～～～～～*/
    /**
     * 休憩開始機能：休憩ボタンを押すと正しく記録される
     */
    public function test_break_start_is_recorded_correctly()
    {
        $user = User::factory()->create(['role' => 0]);
        
        // まずは出勤データがある状態にする
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date'    => Carbon::today()->toDateString(),
            'clock_in' => '09:00:00',
        ]);

        // 休憩入ボタン（POST）を叩く（コントローラーのstoreメソッド）
        $response = $this->actingAs($user)->post('/attendance/break-start');

        $response->assertRedirect('/attendance');

        // DBに休憩データが紐づいて保存されているか
        $this->assertDatabaseHas('break_times', [
            'attendance_id' => $attendance->id,
        ]);
    }

    /**
     * 休憩終了機能：休憩戻ボタンを押すと正しく記録される
     */
    public function test_break_end_is_recorded_correctly()
    {
        $user = User::factory()->create(['role' => 0]);
        
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date'    => Carbon::today()->toDateString(),
            'clock_in' => '09:00:00',
        ]);

        // すでに休憩中のデータを作っておく
        $break = BreakTime::create([
            'attendance_id' => $attendance->id,
            'start_time'    => '12:00:00',
        ]);

        // 休憩戻ボタン（POST）を叩く（コントローラーのupdateメソッド）
        $response = $this->actingAs($user)->post('/attendance/break-end');

        $response->assertRedirect('/attendance');

        // DBの休憩データの終了時間が埋まっているか
        $this->assertDatabaseMissing('break_times', [
            'id' => $break->id,
            'end_time' => null,
        ]);
    }

    /*～～～～～～～～～テストケースID8～～～～～～～～～*/
    /**
     * 退勤機能：退勤ボタンを押すと正しく記録される
     */
    public function test_clock_out_is_recorded_correctly()
    {
        $user = User::factory()->create(['role' => 0]);
        
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date'    => Carbon::today()->toDateString(),
            'clock_in' => '09:00:00',
        ]);

        // 退勤ボタン（POST）を叩く
        $response = $this->actingAs($user)->post('/attendance/clock-out');

        $response->assertRedirect('/attendance');

        // DBの出勤データの退勤時間が埋まっているか
        $this->assertDatabaseMissing('attendances', [
            'id' => $attendance->id,
            'clock_out' => null,
        ]);
    }
}