<?php

namespace Tests\Feature;

use App\Jobs\ArchiveExpiredBlockedAccounts;
use App\Models\Compte;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ArchiveExpiredBlockedAccountsTest extends TestCase
{
    use RefreshDatabase;

    public function test_archive_expired_blocked_accounts()
    {
        // Créer un utilisateur et un compte bloqué expiré
        $user = User::factory()->create();
        $compte = Compte::factory()->create([
            'user_id' => $user->id,
            'statut' => 'bloque',
            'metadonnees' => [
                'dateDeblocagePrevue' => now()->subDay(), // Date expirée
                'motifBlocage' => 'Test de blocage',
                'version' => 1
            ]
        ]);

        // Créer une transaction pour ce compte
        $transaction = Transaction::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'compte_id' => $compte->id,
            'type' => 'depot',
            'montant' => 50000,
            'devise' => 'FCFA',
            'description' => 'Test transaction',
            'statut' => 'validee',
            'date_transaction' => now(),
        ]);

        // Vérifier que le compte existe avant l'archivage
        $this->assertDatabaseHas('comptes', ['id' => $compte->id]);
        $this->assertDatabaseHas('transactions', ['id' => $transaction->id]);

        // Exécuter le job
        $job = new ArchiveExpiredBlockedAccounts();
        $job->handle();

        // Vérifier que le compte a été archivé dans Neon
        $archivedCompte = DB::connection('neon')->table('archived_comptes')
            ->where('original_id', $compte->id)
            ->first();

        $this->assertNotNull($archivedCompte);
        $this->assertEquals('blocking_period_expired', $archivedCompte->reason);

        // Vérifier que la transaction a été archivée
        $archivedTransaction = DB::connection('neon')->table('archived_transactions')
            ->where('original_id', $transaction->id)
            ->first();

        $this->assertNotNull($archivedTransaction);

        // Vérifier que le compte a été soft deleted de la base locale
        $this->assertSoftDeleted('comptes', ['id' => $compte->id]);
    }

    public function test_does_not_archive_non_expired_blocked_accounts()
    {
        // Créer un utilisateur et un compte bloqué non expiré
        $user = User::factory()->create();
        $compte = Compte::factory()->create([
            'user_id' => $user->id,
            'statut' => 'bloque',
            'metadonnees' => [
                'dateDeblocagePrevue' => now()->addDay(), // Date future
                'motifBlocage' => 'Test de blocage',
                'version' => 1
            ]
        ]);

        // Exécuter le job
        $job = new ArchiveExpiredBlockedAccounts();
        $job->handle();

        // Vérifier que le compte n'a pas été archivé
        $archivedCompte = DB::connection('neon')->table('archived_comptes')
            ->where('original_id', $compte->id)
            ->first();

        $this->assertNull($archivedCompte);

        // Vérifier que le compte existe toujours
        $this->assertDatabaseHas('comptes', ['id' => $compte->id]);
    }

    public function test_does_not_archive_active_accounts()
    {
        // Créer un utilisateur et un compte actif
        $user = User::factory()->create();
        $compte = Compte::factory()->create([
            'user_id' => $user->id,
            'statut' => 'actif',
        ]);

        // Exécuter le job
        $job = new ArchiveExpiredBlockedAccounts();
        $job->handle();

        // Vérifier que le compte n'a pas été archivé
        $archivedCompte = DB::connection('neon')->table('archived_comptes')
            ->where('original_id', $compte->id)
            ->first();

        $this->assertNull($archivedCompte);

        // Vérifier que le compte existe toujours
        $this->assertDatabaseHas('comptes', ['id' => $compte->id]);
    }
}
