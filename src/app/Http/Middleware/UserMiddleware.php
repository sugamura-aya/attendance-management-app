<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class UserMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // ログインしていて、かつ role が 0（一般）ならOK
        if (auth()->check() && auth()->user()->role === 0) {
            return $next($request);
        }
        // 管理者が一般用URLに来た場合は、管理者用ログイン画面へリダイレクト
        if (auth()->check() && auth()->user()->role === 1) {
            return redirect()->route('admin.login');
        }

        // それ以外（未ログインなど）は一般ユーザー用ログイン画面へ（基本は auth ミドルウェアが先に処理するが念のため記述）
        return redirect('/login');
    }
}
