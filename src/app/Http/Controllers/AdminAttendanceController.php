<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;// フォームの入力値などをまとめて運ぶためのコード
use Illuminate\Support\Facades\Auth; 
use Carbon\Carbon;// 日付や時刻の計算・加工に必須
use App\Models\User;// 「どのスタッフか」を表示するために必須
use App\Models\Attendance; 
use App\Models\AttendanceRequest; 
use App\Models\BreakTime; 
use App\Models\BreakTimeRequest; 

use App\Http\Requests\AttendanceUpdateRequest;

class AdminAttendanceController extends Controller
{
    //
}
