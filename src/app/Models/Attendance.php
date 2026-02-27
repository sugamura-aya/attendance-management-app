<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', // 誰の勤怠か（リレーションを設定するため、外部キーのuser_idにも許可リストをつける）
        'date',      // 勤務日
        'clock_in',  // 出勤時間
        'clock_out', // 退勤時間
        'remarks',   // 備考（もしあれば）
    ];


    /*～～～～～リレーション～～～～～～*/
    // ➀Attendanceモデル：Userモデル ＝ 子：親 ＝ 多：1
    // リレーションを繋げる（子モデル側）
    public function user()
    {
        // 「$this(Attendanceモデル)はUserモデルに属する」
        return $this->belongsTo(User::class);
    }

    // ➁Attendanceモデル：BreakTimeモデル ＝ 親：子 ＝ 1：多
    // リレーションを繋げる（親モデル側）
    public function breakTimes()
    {
        // 「$this(Attendanceモデル)はBreakTimeモデルを複数有する」
        return $this->hasMany(BreakTime::class);
    }



    /*～計算ロジック～*/
    //　➀【休憩時間】
    // --- 休憩の合計時間を秒で計算する関数 ---
    public function getTotalBreakSeconds()
    {
        $totalSeconds = 0;
        foreach ($this->breakTimes as $break) {
            if ($break->start_time && $break->end_time) {
                $totalSeconds += \Carbon\Carbon::parse($break->end_time)->diffInSeconds(\Carbon\Carbon::parse($break->start_time));
            }
        }
        return $totalSeconds;
    }

    // --- 「H:i」形式の休憩時間を返す ---
    public function getFormattedTotalBreakTime()
    {
        $seconds = $this->getTotalBreakSeconds();
        return sprintf('%02d:%02d', floor($seconds / 3600), ($seconds / 60) % 60);
    }

    //　➁【休憩時間】
    // --- 「H:i」形式の勤務合計時間を返す ---
    public function getFormattedTotalWorkingTime()
    {
        //出勤か退勤がなければ、-:-と返す
        //if (!$this->clock_in || !$this->clock_out) return '-:-';

        // 出勤か退勤がなければ、何も返さない（空っぽにする）
        if (!$this->clock_in || !$this->clock_out) return '';

        $totalStaySeconds = \Carbon\Carbon::parse($this->clock_out)->diffInSeconds(\Carbon\Carbon::parse($this->clock_in));
        $workingSeconds = $totalStaySeconds - $this->getTotalBreakSeconds();

        return sprintf('%02d:%02d', floor($workingSeconds / 3600), ($workingSeconds / 60) % 60);
    }
}
