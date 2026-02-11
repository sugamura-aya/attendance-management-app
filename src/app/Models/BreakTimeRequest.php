<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BreakTimeRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_request_id', // どの修正申請に紐づく休憩か/*リレーションを設定するため、外部キーのattendance_request_idにも許可リストをつける*/
        'start_time',            // 修正後の休憩開始
        'end_time',              // 修正後の休憩終了
    ];

    /*～～～～～リレーション～～～～～～*/

    // ➀BreakTimeRequestモデル：AttendanceRequestモデル ＝ 子：親 ＝ 多：1
    public function attendanceRequest()
    {
        // 「$this(BreakTimeRequest)はAttendanceRequestに属する」
        return $this->belongsTo(AttendanceRequest::class);
    }
}
