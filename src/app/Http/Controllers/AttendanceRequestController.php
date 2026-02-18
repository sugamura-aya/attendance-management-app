<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;// フォームの入力値などをまとめて運ぶためのコード
use Illuminate\Support\Facades\Auth; 
use Carbon\Carbon; // 日付や時刻の計算・加工に必須
use App\Models\Attendance; // どの勤怠を直したいか特定するため必須
use App\Models\AttendanceRequest; 
use App\Models\BreakTimeRequest; 

class AttendanceRequestController extends Controller
{
    //
}
