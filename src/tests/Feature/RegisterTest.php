<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase; // テストごとにリフレッシュ

    /*～～～～～～～～～テストケースID1～～～～～～～～～*/
    /**
     * 名前が未入力の場合、バリデーションメッセージが表示される
     */
    public function test_name_is_required()
    {
        $response = $this->post('/register', [
            'name' => "",
            'email' => "test@example.com",
            'password' => "password",
            'password_confirmation' => "password",
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('name');
        
        $this->assertEquals('お名前を入力してください', session('errors')->first('name'));
    }

    /**
     * メールアドレスが未入力の場合、バリデーションメッセージが表示される
     */
    public function test_email_is_required()
    {
        $response = $this->post('/register', [
            'name' => "テストユーザ",
            'email' => "",
            'password' => "password",
            'password_confirmation' => "password",
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('email');
        $this->assertEquals('メールアドレスを入力してください', session('errors')->first('email'));
    }

    /**
     * パスワードが未入力の場合、バリデーションメッセージが表示される
     */
    public function test_password_is_required()
    {
        $response = $this->post('/register', [
            'name' => "テストユーザ",
            'email' => "test@example.com",
            'password' => "",
            'password_confirmation' => "password",
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('password');
        $this->assertEquals('パスワードを入力してください', session('errors')->first('password'));
    }

    /**
     * パスワードが8文字未満の場合、バリデーションメッセージが表示される
     */
    public function test_password_length_short()
    {
        $response = $this->post('/register', [
            'name' => "テストユーザ",
            'email' => "test@example.com",
            'password' => "pass", // 8文字未満
            'password_confirmation' => "pass",
        ]);

        $response->assertStatus(302);
        $this->assertEquals('パスワードは8文字以上で入力してください', session('errors')->first('password'));
    }

    /**
     * パスワードが一致しない場合、バリデーションメッセージが表示される
     */
    public function test_password_confirmation_fails()
    {
        $response = $this->post('/register', [
            'name' => "テストユーザ",
            'email' => "test@example.com",
            'password' => "password",
            'password_confirmation' => "different_password", // 一致しない
        ]);

        $response->assertStatus(302);
        $this->assertEquals('パスワードと一致しません', session('errors')->first('password'));
    }

    /**
     * フォームに内容が入力されていた場合、データが正常に保存される
     */
    public function test_register_success()
    {
        $response = $this->post('/register', [
            'name' => "テストユーザ",
            'email' => "success@example.com",
            'password' => "password",
            'password_confirmation' => "password",
        ]);

        $this->assertDatabaseHas('users', [
            'name' => "テストユーザ",
            'email' => "success@example.com",
        ]);
    }
}
