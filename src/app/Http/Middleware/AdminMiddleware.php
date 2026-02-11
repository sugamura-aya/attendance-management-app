<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminMiddleware
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
        // ログインしている ＆ roleが 1（管理者）であるかチェック
        if (auth()->check() && auth()->user()->role === 1) {
            return $next($request); // 条件OKなら、そのまま進ませる
        }

        // 条件に合わなければ、管理者ログイン画面へ
        return redirect('/admin/login')->with('error', '管理者権限がありません');
    }
}
