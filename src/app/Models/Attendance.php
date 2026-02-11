<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', // 誰の勤怠か/*リレーションを設定するため、外部キーのuser_idにも許可リストをつける*/
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
}
