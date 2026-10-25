<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\SeedBankListing;
use App\Models\SeedBankRequest;
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
            ->assertSee('RMC Farmers Connect')
            ->assertSee('RMC SEED BANK')
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

    public function test_customer_cannot_open_farmer_workspace_and_public_pages_remain_available(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);

        $this->get('/shop')->assertOk();
        $this->get('/farm-map')->assertOk();
        $this->actingAs($buyer)->get('/farm/dashboard')->assertForbidden();
    }

    public function test_authenticated_user_can_log_out(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);

        $this->actingAs($buyer)
            ->post('/logout')
            ->assertRedirect('/');

        $this->assertGuest();
    }

    public function test_seed_bank_only_publishes_listings_from_verified_farms(): void
    {
        $verifiedFarmer = User::factory()->create(['role' => 'farmer', 'farmer_verified_at' => now()]);
        $pendingFarmer = User::factory()->create(['role' => 'farmer', 'farmer_verified_at' => null]);

        SeedBankListing::create([
            'farmer_id' => $verifiedFarmer->id, 'crop_name' => 'Maize', 'variety' => 'Local White',
            'provenance' => 'Saved from Juja harvest', 'quantity_available' => 4, 'unit' => 'kilograms',
            'status' => 'available', 'location' => 'Juja',
        ]);
        SeedBankListing::create([
            'farmer_id' => $pendingFarmer->id, 'crop_name' => 'Beans', 'variety' => 'Hidden Lot',
            'provenance' => 'Not reviewed', 'quantity_available' => 2, 'unit' => 'packets',
            'status' => 'available',
        ]);

        $this->get('/seed-bank')->assertOk()->assertSee('Local White')->assertDontSee('Hidden Lot');
    }

    public function test_verified_farmers_can_request_seed_and_seller_acceptance_updates_stock(): void
    {
        $seller = User::factory()->create(['role' => 'farmer', 'farmer_verified_at' => now()]);
        $requester = User::factory()->create(['role' => 'farmer', 'farmer_verified_at' => now()]);
        $listing = SeedBankListing::create([
            'farmer_id' => $seller->id, 'crop_name' => 'Maize', 'variety' => 'Local White',
            'provenance' => 'Saved from Juja harvest', 'quantity_available' => 5, 'unit' => 'kilograms',
            'status' => 'available',
        ]);

        $this->actingAs($requester)->post('/seed-bank/'.$listing->id.'/requests', [
            'quantity' => 2, 'message' => 'Can exchange for bean seed.',
        ])->assertRedirect();
        $request = SeedBankRequest::firstOrFail();
        $this->assertSame('pending', $request->status);
        $this->assertEquals(5, (float) $listing->fresh()->quantity_available);

        $this->actingAs($seller)->patch('/farm/seed-bank/requests/'.$request->id, ['status' => 'accepted'])->assertRedirect();
        $this->assertSame('accepted', $request->fresh()->status);
        $this->assertEquals(3, (float) $listing->fresh()->quantity_available);

        $buyer = User::factory()->create(['role' => 'buyer']);
        $this->actingAs($buyer)->post('/seed-bank/'.$listing->id.'/requests', ['quantity' => 1])->assertForbidden();
    }

    public function test_verified_farmer_can_publish_a_seed_lot_with_traceable_details(): void
    {
        $farmer = User::factory()->create(['role' => 'farmer', 'farmer_verified_at' => now(), 'location_name' => 'Juja']);

        $this->actingAs($farmer)->post('/farm/seed-bank', [
            'crop_name' => 'Beans',
            'variety' => 'Rose Coco',
            'provenance' => 'Farm-saved from the June harvest',
            'harvest_date' => '2026-06-15',
            'germination_tested_at' => '2026-07-10',
            'germination_rate' => 86.5,
            'quantity_available' => 3.5,
            'unit' => 'kilograms',
            'location' => 'Juja',
            'status' => 'available',
        ])->assertRedirect();

        $this->get('/farm/seed-bank')->assertOk()->assertSee('Rose Coco');
        $this->get('/seed-bank')->assertOk()->assertSee('Rose Coco')->assertSee('86.5%');
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
