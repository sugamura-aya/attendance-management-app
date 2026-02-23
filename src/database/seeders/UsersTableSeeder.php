<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // ➀ 管理者を作る
        User::create([
            'name' => '管理者 太郎',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 1,
        ]);

        // ➁ ユーザー（自分操作用）：1名
        User::create([
            'name' => '操作テスト用ユーザー',
            'email' => 'new@example.com',
            'password' => Hash::make('password123'),
            'role' => 0,
        ]);

        // ➂ 一般ユーザーを3人作って、それぞれに90日分のデータを入れる
        User::factory(3)->create()->each(function ($user) {
            

            // 【精密計算】先月の1日を取得
            $startDate = Carbon::now()->subMonth()->startOfMonth();
            // 【精密計算】来月の末日を取得
            $endDate = Carbon::now()->addMonth()->endOfMonth();


            // 開始日から終了日まで、1日ずつ増やしながらループ
            // （$date が $endDate を超えるまで繰り返す）
            for ($date = $startDate; $date->lte($endDate); $date->addDay()) {

                if ($date->lte(Carbon::today())) {
                    // 勤怠データ作成（親）
                    $attendance = Attendance::factory()->create([
                        'user_id' => $user->id,// ここで自動作成されたユーザーのIDを紐付け
                        'date' => $date->toDateString(),
                    ]);

                    // その日の休憩データも1つ作成（子）
                    BreakTime::factory()->create([
                        'attendance_id' => $attendance->id,// 上で作った勤怠IDを紐付け
                    ]);
                }
            }
        });
    }
}
