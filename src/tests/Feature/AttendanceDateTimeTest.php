<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Carbon\Carbon;
use App\Models\User;

class AttendanceDateTimeTest extends TestCase
{
    use RefreshDatabase;

    public function test_現在の日時情報がUIと同じ形式で出力されている()
    {
        $fixedNow = Carbon::create(2026, 1, 3, 18, 45, 0, 'Asia/Tokyo')->locale('ja');
        Carbon::setTestNow($fixedNow);

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $expectedDateLabel = $fixedNow->isoFormat('YYYY年M月D日(ddd)');
        $expectedTimeLabel = $fixedNow->format('H:i');

        $response->assertStatus(200);

        $response->assertSee($expectedDateLabel);
        $response->assertSee($expectedTimeLabel);

        Carbon::setTestNow();
    }
}
