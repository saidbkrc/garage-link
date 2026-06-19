<?php

namespace Tests\Feature;

use App\Models\Dealer;
use App\Models\DealerUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_renders(): void
    {
        $this->get('/login')->assertOk();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_dealer_user_can_login_with_valid_credentials(): void
    {
        $user = DealerUser::factory()->create([
            'email'    => 'bayi@example.com',
            'password' => 'secret123',
        ]);

        $this->post('/login', [
            'email'    => 'bayi@example.com',
            'password' => 'secret123',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user, 'dealer');
    }

    public function test_login_fails_with_wrong_password(): void
    {
        DealerUser::factory()->create(['email' => 'bayi@example.com']);

        $this->from('/login')->post('/login', [
            'email'    => 'bayi@example.com',
            'password' => 'wrong-password',
        ])->assertRedirect('/login');

        $this->assertGuest('dealer');
    }

    public function test_inactive_user_cannot_login(): void
    {
        DealerUser::factory()->inactive()->create([
            'email'    => 'pasif@example.com',
            'password' => 'secret123',
        ]);

        $this->from('/login')->post('/login', [
            'email'    => 'pasif@example.com',
            'password' => 'secret123',
        ])->assertRedirect('/login');

        $this->assertGuest('dealer');
    }

    public function test_user_of_inactive_dealer_cannot_login(): void
    {
        $dealer = Dealer::factory()->inactive()->create();
        DealerUser::factory()->for($dealer)->create([
            'email'    => 'devredisi@example.com',
            'password' => 'secret123',
        ]);

        $this->from('/login')->post('/login', [
            'email'    => 'devredisi@example.com',
            'password' => 'secret123',
        ])->assertRedirect('/login');

        $this->assertGuest('dealer');
    }

    public function test_login_is_rate_limited(): void
    {
        DealerUser::factory()->create(['email' => 'bayi@example.com']);

        // throttle:6,1 — 6 deneme izinli, 7. denemede 429 dönmeli
        for ($i = 0; $i < 6; $i++) {
            $this->post('/login', [
                'email'    => 'bayi@example.com',
                'password' => 'wrong-password',
            ]);
        }

        $this->post('/login', [
            'email'    => 'bayi@example.com',
            'password' => 'wrong-password',
        ])->assertStatus(429);
    }
}
