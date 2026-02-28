<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\AdminLoginRequest;

class AdminAuthController extends Controller
{
    // ➆ 管理者ログイン画面（表示）
    public function showLoginForm()
    {
        return view('auth.admin_login');
    }


    // ログイン処理（POST）
    public function login(AdminLoginRequest $request)
    {
        // 1. メアドとパスワードを受け取る
        $credentials = $request->only('email', 'password');

        // 2. 相違なければ管理者ページへ
        if (Auth::attempt($credentials)) {
            return redirect()->route('admin.attendance.list');
        }

        // 3. ダメだったらエラーを抱えて戻る
        //「このメアドとパスワード、DBにある本物のデータと一致するか」を確認するため、Controllerにてチェック（mail、PWの見た目はRequestformにてチェック）
        return back()->withInput()->withErrors([
            'email' => 'ログイン情報が登録されていません',
        ]);
    }


    // ログアウト処理（POST）
    public function logout()
    {
        Auth::logout();

        return redirect()->route('admin.login');
    }
}
