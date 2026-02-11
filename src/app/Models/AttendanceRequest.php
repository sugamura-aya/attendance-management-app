<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',   // 誰の申請か/*リレーションを設定するため、外部キーのuser_idにも許可リストをつける*/
        'date',      // 修正したい日
        'clock_in',  // 修正後の出勤
        'clock_out', // 修正後の退勤
        'remarks',   // 申請理由
        'status',    // 承認ステータス（承認待ち、承認済み、却下など）
    ];

    /*～～～～～リレーション～～～～～～*/

    // ➀AttendanceRequestモデル：Userモデル ＝ 子：親 ＝ 多：1
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ➁AttendanceRequestモデル：BreakTimeRequestモデル ＝ 親：子 ＝ 1：多
    public function breakTimeRequests()
    {
        return $this->hasMany(BreakTimeRequest::class);
    }
}
