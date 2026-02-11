<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BreakTime extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_id', // どの勤務に紐づく休憩か/*リレーションを設定するため、外部キーのattendance_idにも許可リストをつける*/
        'start_time',    // 休憩開始
        'end_time',      // 休憩終了
    ];

    /*～～～～～リレーション～～～～～～*/

    // ➀BreakTimeモデル：Attendanceモデル ＝ 子：親 ＝ 多：1
    // リレーションを繋げる（子モデル側）
    public function attendance()
    {
        // 「$this(BreakTimeモデル)はAttendanceモデルに属する」
        return $this->belongsTo(Attendance::class);
    }
}
