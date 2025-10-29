<?php

namespace App\Jobs;

use App\Models\Compte;
use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UnarchiveExpiredBlockedAccounts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting UnarchiveExpiredBlockedAccounts job');

        // Trouver tous les comptes bloqués dans Neon dont la date de fin de blocage est échue
        $expiredBlockedAccounts = DB::connection('neon')
            ->table('archived_comptes_neon')
            ->where('statut', 'bloque')
            ->whereRaw("metadonnees->>'dateFinBlocage' <= ?", [now()->toISOString()])
            ->whereNotNull('metadonnees->dateFinBlocage')
            ->get();

        Log::info("Found {$expiredBlockedAccounts->count()} expired blocked accounts in Neon to unarchive");

        foreach ($expiredBlockedAccounts as $archivedCompte) {
            DB::transaction(function () use ($archivedCompte) {
                try {
                    // Restaurer le compte depuis Neon vers la base locale
                    $this->unarchiveFromNeon($archivedCompte);

                    // Supprimer de la base d'archivage
                    DB::connection('neon')->table('archived_comptes_neon')
                        ->where('id', $archivedCompte->id)
                        ->delete();

                    Log::info("Unarchived account {$archivedCompte->numero_compte} from Neon to main database");
                } catch (\Exception $e) {
                    Log::error("Failed to unarchive account {$archivedCompte->numero_compte}: " . $e->getMessage());
                    throw $e;
                }
            });
        }

        Log::info('Completed UnarchiveExpiredBlockedAccounts job');
    }

    /**
     * Restaure le compte depuis Neon vers la base locale
     */
    private function unarchiveFromNeon($archivedCompte): void
    {
        Compte::create([
            'id' => $archivedCompte->original_id,
            'numero_compte' => $archivedCompte->numero_compte,
            'user_id' => $archivedCompte->user_id,
            'type' => $archivedCompte->type,
            'solde' => $archivedCompte->solde,
            'devise' => $archivedCompte->devise,
            'statut' => 'actif', // Remettre à actif après expiration du blocage
            'metadonnees' => array_merge(
                json_decode($archivedCompte->metadonnees, true) ?? [],
                [
                    'derniereModification' => now(),
                    'version' => (json_decode($archivedCompte->metadonnees, true)['version'] ?? 1) + 1,
                    'unarchived_at' => now(),
                    'reason_unarchived' => 'blocking_period_expired'
                ]
            ),
            'created_at' => $archivedCompte->created_at,
            'updated_at' => now(),
        ]);
    }

    /**
     * Restaure une transaction depuis Neon vers la base locale
     */
    private function unarchiveTransactionFromNeon($archivedTransaction): void
    {
        Transaction::create([
            'id' => $archivedTransaction->original_id,
            'compte_id' => $archivedTransaction->compte_id,
            'type' => $archivedTransaction->type,
            'montant' => $archivedTransaction->montant,
            'devise' => $archivedTransaction->devise,
            'description' => $archivedTransaction->description,
            'statut' => $archivedTransaction->statut,
            'date_transaction' => $archivedTransaction->date_transaction,
            'created_at' => $archivedTransaction->created_at,
            'updated_at' => $archivedTransaction->updated_at,
        ]);
    }
}
