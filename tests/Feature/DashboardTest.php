<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Compte;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_global_dashboard_admin_only()
    {
        $admin = User::factory()->create(['admin' => true]);
        $client = User::factory()->create();

        // Test admin access
        $response = $this->actingAs($admin, 'api')->getJson('/api/v1/dashboard');
        $response->assertStatus(200);

        // Test client access denied
        $response = $this->actingAs($client, 'api')->getJson('/api/v1/dashboard');
        $response->assertStatus(403);
    }

    public function test_personal_dashboard()
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

        $response = $this->actingAs($user, 'api')->getJson('/api/v1/dashboard/me');
        $response->assertStatus(200);

        $data = $response->json();
        $this->assertEquals(100.00, $data['totalDepot']);
        $this->assertEquals(50.00, $data['totalRetrait']);
        $this->assertEquals(2, $data['count']);
        $this->assertEquals(50.00, $data['balance']);
        $this->assertCount(2, $data['latest10']);
        $this->assertCount(1, $data['comptes']);
    }

    public function test_global_dashboard_data()
    {
        $admin = User::factory()->create(['admin' => true]);

        // Create some data
        $compte = Compte::factory()->create();
        Transaction::factory()->create([
            'compte_id' => $compte->id,
            'type' => 'deposit',
            'montant' => 200.00
        ]);
        Transaction::factory()->create([
            'compte_id' => $compte->id,
            'type' => 'withdrawal',
            'montant' => 100.00
        ]);
        Compte::factory()->create(['created_at' => now()]);

        $response = $this->actingAs($admin, 'api')->getJson('/api/v1/dashboard');
        $response->assertStatus(200);

        $data = $response->json();
        $this->assertEquals(200.00, $data['totalDepot']);
        $this->assertEquals(100.00, $data['totalRetrait']);
        $this->assertEquals(2, $data['count']);
        $this->assertEquals(100.00, $data['soldeGlobal']);
        $this->assertCount(2, $data['latest10']);
        $this->assertGreaterThanOrEqual(1, $data['comptesToday']);
        $this->assertGreaterThanOrEqual(1, $data['totalComptes']);
    }
}