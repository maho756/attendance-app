<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Carbon;

class EmailVerificationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_会員登録後に認証メールが送信される()
    {
        Notification::fake();

        $payload = [
            'name' => 'テストユーザー',
            'email' => 'verify@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ];

        $res = $this->post('/register', $payload);

        $res->assertStatus(302);

        $user = User::where('email', 'verify@example.com')->first();
        $this->assertNotNull($user);

        Notification::assertSentTo(
            $user,
            VerifyEmail::class
        );
    }

    public function test_誘導画面が表示できる()
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);
        $res = $this->actingAs($user)->get('/email/verify');
        $res->assertStatus(200);
    }

    public function test_メール認証完了で勤怠登録画面に遷移する()
    {
        $user = User::factory()->create([
            'email' => 'needverify@example.com',
            'email_verified_at' => null,
        ]);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1($user->email),
            ]
        );

        $res = $this->actingAs($user)->get($verificationUrl);

        $res->assertStatus(302);
        $this->assertStringStartsWith(
            'http://localhost/attendance',
            $res->headers->get('Location')
        );

        $this->assertNotNull($user->fresh()->email_verified_at);

        $this->actingAs($user->fresh())->get('/attendance')->assertStatus(200);
    }
}