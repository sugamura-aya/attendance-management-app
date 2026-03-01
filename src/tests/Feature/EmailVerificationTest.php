<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\VerifyEmail;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_verification_email_is_sent_after_registration()
    {
        Notification::fake();

        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // 会員登録後はログイン状態になり、一旦リダイレクトされることを確認
        $this->assertAuthenticated();
        $response->assertStatus(302);

        $user = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user);

        // 認証メールが送信されたか確認
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_verification_notice_screen_can_be_rendered()
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $response = $this->actingAs($user)->get('/email/verify');
        $response->assertStatus(200);
        $response->assertSee('認証はこちらから');
    }

    public function test_email_can_be_verified_and_redirects_to_attendance()
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $verificationUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        // リダイレクト先に /attendance が含まれているか（クエリパラメータがあっても許容）
        $response->assertRedirectContains('/attendance');
        
        // DBで認証済みになっているか確認
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }
}