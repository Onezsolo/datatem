<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryDetailsTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_exposes_saved_delivery_details(): void
    {
        $user = User::create([
            'full_name' => 'Ama Mensah',
            'email' => 'ama-delivery@example.com',
            'phone' => '0244000030',
            'password' => bcrypt('password'),
        ]);

        $this->actingAs($user)
            ->get('/profile')
            ->assertOk()
            ->assertSee('Delivery details')
            ->assertSee('Save delivery details');
    }

    public function test_api_saves_delivery_details_to_the_account(): void
    {
        $user = User::create([
            'full_name' => 'Ama Mensah',
            'email' => 'ama-delivery-2@example.com',
            'phone' => '0244000031',
            'password' => bcrypt('password'),
        ]);

        $token = $this->postJson('/api/auth/login', [
            'login' => $user->email,
            'password' => 'password',
        ])->assertOk()->json('access_token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/auth/user/delivery-details', [
                'delivery_name' => 'Ama Mensah',
                'delivery_phone' => '0244000031',
                'delivery_address' => '5 Oxford Street',
                'delivery_city' => 'Osu',
                'delivery_region' => 'Greater Accra',
                'delivery_zone' => 'accra',
                'delivery_notes' => 'Gate code 12',
            ])
            ->assertOk()
            ->assertJsonPath('user.delivery_address', '5 Oxford Street')
            ->assertJsonPath('user.delivery_zone', 'accra');

        $user->refresh();

        $this->assertSame('5 Oxford Street', $user->delivery_address);
        $this->assertSame('Osu', $user->delivery_city);
        $this->assertSame('Gate code 12', $user->delivery_notes);
    }
}
