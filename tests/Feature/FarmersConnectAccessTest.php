<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FarmersConnectAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_uses_farmers_connect_brand_and_vision_anchor(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Farmers Connect')
            ->assertSee('id="vision"', false);
    }

    public function test_unverified_farmer_is_redirected_to_review_and_cannot_publish(): void
    {
        $farmer = User::factory()->create([
            'role' => 'farmer',
            'phone' => '+254712345678',
            'phone_verified_at' => now(),
            'farmer_verified_at' => null,
        ]);

        $this->actingAs($farmer)
            ->get('/farm/dashboard')
            ->assertRedirect('/farm/pending-verification');

        $this->actingAs($farmer)
            ->get('/farm/pending-verification')
            ->assertOk()
            ->assertSee('Your farm is in review');
    }

    public function test_only_admin_can_approve_a_phone_verified_farmer(): void
    {
        $farmer = User::factory()->create([
            'role' => 'farmer',
            'phone' => '+254712345678',
            'phone_verified_at' => now(),
        ]);
        $buyer = User::factory()->create(['role' => 'buyer']);

        $this->actingAs($buyer)
            ->patch('/admin/farmers/'.$farmer->id.'/approve')
            ->assertForbidden();

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)
            ->patch('/admin/farmers/'.$farmer->id.'/approve')
            ->assertRedirect();

        $this->assertNotNull($farmer->fresh()->farmer_verified_at);
    }

    public function test_repeated_bad_passwords_lock_out_phone_sign_in(): void
    {
        User::factory()->create([
            'role' => 'buyer',
            'phone' => '+254712345678',
            'password' => bcrypt('correct-password'),
            'phone_verified_at' => now(),
        ]);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/login', ['phone' => '0712345678', 'password' => 'wrong-password'])
                ->assertSessionHasErrors('phone');
        }

        $this->post('/login', ['phone' => '0712345678', 'password' => 'correct-password'])
            ->assertSessionHasErrors('phone');
    }

    public function test_signup_starts_phone_verification_and_does_not_create_an_unverified_account(): void
    {
        config([
            'services.africas_talking.username' => 'sandbox',
            'services.africas_talking.api_key' => 'test-key',
        ]);
        Http::fake([
            '*' => Http::response([
                'SMSMessageData' => ['Recipients' => [['statusCode' => '101']]],
            ], 200),
        ]);

        $this->post('/register', [
            'name' => 'Test Customer',
            'phone' => '0712345678',
            'role' => 'buyer',
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
        ])->assertRedirect('/verify-phone');

        $this->assertDatabaseCount('users', 0);
        Http::assertSent(fn ($request) => $request['to'] === '+254712345678');
    }
}
