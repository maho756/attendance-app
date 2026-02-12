<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_メールアドレスが未入力の場合、バリデーションメッセージが表示される()
    {
        $response = $this->from('/admin/login')->post('/login',[
            'email' => '',
            'password' => 'password',
            'login_type' => 'admin',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください'
        ]);

        $response->assertRedirect('/admin/login');
    }

    public function test_パスワードが未入力の場合、バリデーションメッセージが表示される()
    {
        $response = $this->from('/admin/login')->post('/login',[
            'email' => 'admin@example.com',
            'password' => '',
            'login_type' => 'admin',
        ]);

        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください'
        ]);

        $response->assertRedirect('/admin/login');
    }

    public function test_登録内容と一致しない場合、バリデーションメッセージが表示される()
    {
        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);

        $response = $this->from('/admin/login')->post('/login',[
            'email' => 'notfound@example.com',
            'password' => 'wrongpassword',
            'login_type' => 'admin'
        ]);

        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません'
        ]);

        $response->assertRedirect('/admin/login');
    }
}
