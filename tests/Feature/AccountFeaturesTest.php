<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Compte;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AccountFeaturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_account_transactions()
    {
        $user = User::factory()->create();
        $compte = Compte::factory()->create(['user_id' => $user->id]);

        // Create some transactions
        Transaction::factory()->create([
            'compte_id' => $compte->id,
            'type' => 'deposit',
            'montant' => 100.00
        ]);
        Transaction::factory()->create([
            'compte_id' => $compte->id,
            'type' => 'withdrawal',
            'montant' => 50.00
        ]);

        $response = $this->actingAs($user, 'api')->getJson("/api/v1/comptes/{$compte->id}/transactions");

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertCount(2, $data['data']);
        $this->assertEquals(2, $data['pagination']['totalItems']);
    }

    public function test_get_account_statistics()
    {
        $user = User::factory()->create();
        $compte = Compte::factory()->create(['user_id' => $user->id]);

        // Create transactions
        Transaction::factory()->create([
            'compte_id' => $compte->id,
            'type' => 'deposit',
            'montant' => 200.00
        ]);
        Transaction::factory()->create([
            'compte_id' => $compte->id,
            'type' => 'deposit',
            'montant' => 100.00
        ]);
        Transaction::factory()->create([
            'compte_id' => $compte->id,
            'type' => 'withdrawal',
            'montant' => 50.00
        ]);

        $response = $this->actingAs($user, 'api')->getJson("/api/v1/comptes/{$compte->id}/statistics");

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertEquals(300.00, $data['data']['totalDepot']);
        $this->assertEquals(50.00, $data['data']['totalRetrait']);
        $this->assertEquals(3, $data['data']['count']);
        $this->assertEquals($compte->id, $data['data']['compteId']);
    }

    public function test_get_account_dashboard()
    {
        $user = User::factory()->create();
        $compte = Compte::factory()->create(['user_id' => $user->id]);

        // Create multiple transactions
        for ($i = 1; $i <= 12; $i++) {
            Transaction::factory()->create([
                'compte_id' => $compte->id,
                'type' => $i % 2 === 0 ? 'deposit' : 'withdrawal',
                'montant' => $i * 10.00
            ]);
        }

        $response = $this->actingAs($user, 'api')->getJson("/api/v1/comptes/{$compte->id}/dashboard");

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertTrue($data['success']);

        // Check dashboard data
        $this->assertEquals(60.00, $data['data']['totalDepot']); // 20+40+60+80+100+120
        $this->assertEquals(60.00, $data['data']['totalRetrait']); // 10+30+50+70+90+110
        $this->assertEquals(0.00, $data['data']['balance']); // 60 - 60
        $this->assertEquals(12, $data['data']['count']);
        $this->assertCount(10, $data['data']['latest10']); // Should return only 10 latest
        $this->assertEquals($compte->id, $data['data']['compte']['id']);
    }

    public function test_access_denied_for_other_users_account()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $compte = Compte::factory()->create(['user_id' => $user1->id]);

        // User2 tries to access user1's account
        $response = $this->actingAs($user2, 'api')->getJson("/api/v1/comptes/{$compte->id}/transactions");
        $response->assertStatus(403);

        $response = $this->actingAs($user2, 'api')->getJson("/api/v1/comptes/{$compte->id}/statistics");
        $response->assertStatus(403);

        $response = $this->actingAs($user2, 'api')->getJson("/api/v1/comptes/{$compte->id}/dashboard");
        $response->assertStatus(403);
    }

    public function test_admin_can_access_any_account()
    {
        $admin = User::factory()->create(['admin' => true]);
        $user = User::factory()->create();
        $compte = Compte::factory()->create(['user_id' => $user->id]);

        // Admin can access any account
        $response = $this->actingAs($admin, 'api')->getJson("/api/v1/comptes/{$compte->id}/transactions");
        $response->assertStatus(200);

        $response = $this->actingAs($admin, 'api')->getJson("/api/v1/comptes/{$compte->id}/statistics");
        $response->assertStatus(200);

        $response = $this->actingAs($admin, 'api')->getJson("/api/v1/comptes/{$compte->id}/dashboard");
        $response->assertStatus(200);
    }

    public function test_account_not_found()
    {
        $user = User::factory()->create();
        $nonExistentId = '00000000-0000-0000-0000-000000000000';

        $response = $this->actingAs($user, 'api')->getJson("/api/v1/comptes/{$nonExistentId}/transactions");
        $response->assertStatus(404);

        $response = $this->actingAs($user, 'api')->getJson("/api/v1/comptes/{$nonExistentId}/statistics");
        $response->assertStatus(404);

        $response = $this->actingAs($user, 'api')->getJson("/api/v1/comptes/{$nonExistentId}/dashboard");
        $response->assertStatus(404);
    }
}