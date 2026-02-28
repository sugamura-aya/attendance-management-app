<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase; // テストごとにリフレッシュ

    /* --- 一般ユーザーのログインテスト --- */

    public function test_user_email_is_required()
    {
        $response = $this->post('/login', [ // 一般ユーザー用URL
            'email' => "",
            'password' => "password",
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('email');
        $this->assertEquals('メールアドレスを入力してください', session('errors')->first('email'));
    }

    public function test_user_password_is_required()
    {
        $response = $this->post('/login', [
            'email' => "test@example.com",
            'password' => "",
        ]);

        $response->assertStatus(302);
        $this->assertEquals('パスワードを入力してください', session('errors')->first('password'));
    }

    public function test_user_login_failure()
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => "user@example.com",
            'password' => "wrong_pass",
        ]);

        $response->assertStatus(302);
        $this->assertEquals('ログイン情報が登録されていません', session('errors')->first('email'));
    }



    /* --- 管理者のログインテスト --- */

    public function test_admin_email_is_required()
    {
        $response = $this->post('/admin/login', [ // 管理者用URL
            'email' => "",
            'password' => "password",
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('email');
        $this->assertEquals('メールアドレスを入力してください', session('errors')->first('email'));
    }

    public function test_admin_password_is_required()
    {
        $response = $this->post('/admin/login', [
            'email' => "admin@example.com",
            'password' => "",
        ]);

        $response->assertStatus(302);
        $this->assertEquals('パスワードを入力してください', session('errors')->first('password'));
    }

    public function test_admin_login_failure()
    {
        // 管理者としてユーザーを作成
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('admin123'),
        ]);

        $response = $this->post('/admin/login', [
            'email' => "admin@example.com",
            'password' => "wrong_admin_pass",
        ]);

        $response->assertStatus(302);
        $this->assertEquals('ログイン情報が登録されていません', session('errors')->first('email'));
    }
}
