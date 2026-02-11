<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;
//以下追加
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        
            Fortify::registerView(function () {
                return view('auth.register');
            });

            Fortify::loginView(function () {
                return view('auth.login');
            });

            RateLimiter::for('login', function (Request $request) {
                $email = (string) $request->email;

                return Limit::perMinute(10)->by($email . $request->ip());
            });

            // ★ログイン時の認証ロジックをカスタマイズ（「一般ユーザー(role=0)だけ」がログインできるように制限をかける）
            Fortify::authenticateUsing(function ($request) {
                $user = User::where('email', $request->email)->first();

                // ユーザーが存在する ＆ パスワードが一致する ＆ 一般ユーザー(role=0)である
                if ($user && 
                    Hash::check($request->password, $user->password) && 
                    $user->role === 0) {
                    return $user;
                }
                
                // 条件に合わなければログイン失敗（管理者のメールアドレスで一般ユーザー用のログインからログインできないようにする）
                return null;
            });
    }
}
