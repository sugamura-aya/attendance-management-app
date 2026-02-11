<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceRequestController;
use App\Http\Controllers\AdminAttendanceController;
use App\Http\Controllers\AdminAuthController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


// --- トップページ（/ に来たら /attendance に飛ばす：未ログイン者だった場合ミドルウェアにてログインページにリダイレクト） ---
Route::get('/', function () {
    return redirect('/attendance');
});

// --- 【一般ユーザー向け】（ログイン必須グループ） ---
// 「ログイン済み」かつ「role=0」の人しか絶対に通れないよう、自作「user」ミドルウェアを利用。
// ※応用「メール認証」※　着手時には「'verified'」ミドルウェアも加えること
Route::middleware(['auth', 'user'])->group(function () {
    
    // ➂出勤登録画面（表示）
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    
        // 出勤・退勤・休憩の登録（POST処理）
        Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn'])->name('attendance.clock-in');
        Route::post('/attendance/clock-out', [AttendanceController::class, 'clockOut'])->name('attendance.clock-out');
        Route::post('/attendance/break-start', [AttendanceController::class, 'store'])->name('attendance.break-start');
        Route::post('/attendance/break-end', [AttendanceController::class, 'update'])->name('attendance.break-end');

    // ➃勤怠一覧画面（表示）
    Route::get('/attendance/list', [AttendanceController::class, 'list'])->name('attendance.list');

    // ➄勤怠詳細（表示）
    Route::get('/attendance/detail/{id}', [AttendanceController::class, 'show'])->name('attendance.show');

        // 申請登録処理（POST処理）
        Route::post('/attendance/edit/{id}', [AttendanceController::class, 'storeRequest'])->name('attendance.request.store');

    // ➅申請一覧画面（表示）※管理者用とパスが同じため、⑫と一つのミドルウェア内でルーティング設定する。
    //Route::get('/stamp_correction_request/list', [AttendanceRequestController::class, 'index'])->name('attendance.request.list');
});




// --- 【管理者向け】(ログイン・認証系グループ） ---
// ➆ログイン画面（表示）
Route::get('/admin/login', [AdminAuthController::class, 'showLoginForm'])->name('admin.login');
    // ログイン処理（POST処理）
    Route::post('/admin/login', [AdminAuthController::class, 'login'])->name('admin.login.post');
// ログアウト処理（POST処理）
Route::post('/admin/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

// --- 【管理者向け】（ログイン必須グループ） ---
// 「ログイン済み」かつ「role=1」の人しか絶対に通れないよう、自作「admin」ミドルウェアを利用。
Route::middleware(['auth', 'admin'])->group(function () {
    
    // ➇勤怠一覧画面（表示）
    Route::get('/admin/attendance/list', [AdminAttendanceController::class, 'showUserAttendance'])->name('admin.attendance.list');

    // ⓽勤怠詳細画面（表示）
    Route::get('/admin/attendance/{id}', [AdminAttendanceController::class, 'showDetail'])->name('admin.attendance.show');
        // 更新処理（POST処理）
        Route::patch('/admin/attendance/{id}', [AdminAttendanceController::class, 'update'])->name('admin.attendance.update');

    // ⓾スタッフ一覧画面（表示）
    Route::get('/admin/staff/list', [AdminAttendanceController::class, 'showStaffList'])->name('admin.staff.list');

    // ⑪スタッフ別勤怠一覧（表示）
    Route::get('/admin/attendance/staff/{id}', [AdminAttendanceController::class, 'showStaffAttendance'])->name('admin.attendance.staff.list');

    // ⑫申請一覧画面（表示）※一般ユーザー用とパスが同じため、➅と一つのミドルウェア内でルーティング設定する。
    //Route::get('/stamp_correction_request/list', [AdminAttendanceController::class, 'index'])->name('admin.attendance.request.list');

    // ⑬修正申請承認画面（表示）
    Route::get('/stamp_correction_request/approve/{attendance_correct_request_id}', [AdminAttendanceController::class, 'showApproveForm'])->name('admin.stamp_correction_request.approve.show');
        // 修正申請承認処理（POST処理）
        Route::patch('/stamp_correction_request/approve/{attendance_correct_request_id}', [AdminAttendanceController::class, 'approve'])->name('admin.stamp_correction_request.approve.update');
});



// --- 【URLが同じ「申請一覧画面（表示）」の交通整理】 ---
Route::middleware(['auth'])->group(function () {
    Route::get('/stamp_correction_request/list', function (Request $request) {
        // ログインユーザーの role をチェック
        if (Auth::user()->role === 1) {
            // 管理者の場合は AdminAttendanceController の index へ
            return app(AdminAttendanceController::class)->index($request);
        } else {
            // 一般ユーザーの場合は AttendanceRequestController の index へ
            return app(AttendanceRequestController::class)->index($request);
        }
    })->name('stamp_correction_request.list');
});