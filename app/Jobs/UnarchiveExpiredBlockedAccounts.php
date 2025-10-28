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

        // Pour l'instant, on commente la logique d'archivage/désarchivage vers Neon
        // et on se concentre uniquement sur le déblocage automatique des comptes

        // Trouver tous les comptes bloqués dont la date de fin de blocage est échue
        $expiredBlockedAccounts = Compte::where('statut', 'bloque')
            ->where(function ($query) {
                $query->where('metadonnees->dateFinBlocage', '<=', now())
                      ->whereNotNull('metadonnees->dateFinBlocage');
            })
            ->get();

        Log::info("Found {$expiredBlockedAccounts->count()} expired blocked accounts to unblock");

        foreach ($expiredBlockedAccounts as $compte) {
            DB::transaction(function () use ($compte) {
                try {
                    // Mettre à jour le compte pour le débloquer automatiquement
                    $compte->update([
                        'statut' => 'actif',
                        'metadonnees' => array_merge($compte->metadonnees ?? [], [
                            'dateDeblocageAutomatique' => now(),
                            'motifDeblocageAutomatique' => 'Période de blocage expirée',
                            'derniereModification' => now(),
                            'version' => ($compte->metadonnees['version'] ?? 1) + 1
                        ])
                    ]);

                    Log::info("Automatically unblocked account {$compte->numero_compte}");
                } catch (\Exception $e) {
                    Log::error("Failed to unblock account {$compte->numero_compte}: " . $e->getMessage());
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
