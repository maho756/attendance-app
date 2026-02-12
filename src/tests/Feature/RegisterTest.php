<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_名前が未入力の場合、バリデーションメッセージが表示される()
    {
        $response = $this->from('/register')->post('/register', [
            'name' => '',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors([
            'name' => 'お名前を入力してください',
        ]);

        $response->assertRedirect('/register');
    }

    public function test_メールアドレスが未入力の場合、バリデーションメッセージが表示される()
    {
        $response = $this->from('/register')->post('/register', [
            'name' => 'user',
            'email' => '',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);

        $response->assertRedirect('/register');
    }

    public function test_パスワードが8文字未満の場合、バリデーションメッセージが表示される()
    {
        $response = $this->from('/register')->post('/register', [
            'name' => 'user',
            'email' => 'test@example.com',
            'password' => '1234567',
            'password_confirmation' => '1234567',
        ]);

        $response->assertSessionHasErrors([
            'password' => 'パスワードは8文字以上で入力してください',
        ]);

        $response->assertRedirect('/register');
    }

    public function test_パスワードが一致しない場合、バリデーションメッセージが表示される()
    {
        $response = $this->from('/register')->post('/register', [
            'name' => 'user',
            'email' => 'test@example.com',
            'password' => '12345678',
            'password_confirmation' => '87654321',
        ]);

        $response->assertSessionHasErrors([
            'password' => 'パスワードと一致しません',
        ]);

        $response->assertRedirect('/register');
    }

    public function test_パスワードが未入力の場合、バリデーションメッセージが表示される()
    {
        $response = $this->from('/register')->post('/register', [
            'name' => 'user',
            'email' => 'test@example.com',
            'password' => '',
            'password_confirmation' => '',
        ]);

        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);

        $response->assertRedirect('/register');
    }

    public function test_フォームに内容が入力されていた場合、データが正常に保存される()
    {
        $response = $this->from('/register')->post('/register', [
            'name' => 'user',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect('/attendance');

        $this->assertDatabaseHas('users', [
            'name' => 'user',
            'email' => 'test@example.com',
        ]);
    }
}
