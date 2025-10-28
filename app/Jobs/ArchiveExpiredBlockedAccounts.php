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

class ArchiveExpiredBlockedAccounts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    
    public function __construct()
    {
        //
    }

   
    public function handle(): void
    {
        Log::info('Starting ArchiveExpiredBlockedAccounts job');

        // Vérifier les comptes à bloquer (date de début atteinte)
        $accountsToBlock = Compte::where('statut', 'actif')
            ->where('metadonnees->statutProgramme', 'bloque')
            ->whereNotNull('metadonnees->dateDebutBlocage')
            ->get()
            ->filter(function ($compte) {
                return \Carbon\Carbon::parse($compte->metadonnees['dateDebutBlocage'])->lte(now());
            });

        Log::info("Found {$accountsToBlock->count()} accounts to block");

        foreach ($accountsToBlock as $compte) {
            DB::transaction(function () use ($compte) {
                try {
                    // Bloquer le compte
                    $compte->update(['statut' => 'bloque']);
                    Log::info("Blocked account {$compte->numero_compte} as scheduled");
                } catch (\Exception $e) {
                    Log::error("Failed to block account {$compte->numero_compte}: " . $e->getMessage());
                    throw $e;
                }
            });
        }

        // Vérifier les comptes bloqués expirés à archiver
        $expiredBlockedAccounts = Compte::where('statut', 'bloque')
            ->where(function ($query) {
                $query->where('metadonnees->dateFinBlocage', '<=', now())
                      ->whereNotNull('metadonnees->dateFinBlocage');
            })
            ->get();

        Log::info("Found {$expiredBlockedAccounts->count()} expired blocked accounts to archive");

        foreach ($expiredBlockedAccounts as $compte) {
            DB::transaction(function () use ($compte) {
                try {
                    // Pour l'instant, on commente l'archivage vers Neon
                    // $this->archiveToNeon($compte);

                    // Archiver toutes les transactions du compte
                    // $transactions = Transaction::where('compte_id', $compte->id)->get();
                    // foreach ($transactions as $transaction) {
                    //     $this->archiveTransactionToNeon($transaction);
                    // }

                    // Simplement supprimer le compte (soft delete)
                    $compte->delete();

                    Log::info("Soft deleted expired blocked account {$compte->numero_compte}");
                } catch (\Exception $e) {
                    Log::error("Failed to process account {$compte->numero_compte}: " . $e->getMessage());
                    throw $e;
                }
            });
        }

        Log::info('Completed ArchiveExpiredBlockedAccounts job');
    }

   
    private function archiveToNeon(Compte $compte): void
    {
       
        DB::connection('neon')->table('archived_comptes')->insert([
            'original_id' => $compte->id,
            'numero_compte' => $compte->numero_compte,
            'user_id' => $compte->user_id,
            'type' => $compte->type,
            'solde' => $compte->solde,
            'devise' => $compte->devise,
            'statut' => $compte->statut,
            'metadonnees' => json_encode($compte->metadonnees),
            'created_at' => $compte->created_at,
            'updated_at' => $compte->updated_at,
            'archived_at' => now(),
            'reason' => 'blocking_period_expired'
        ]);
    }

    /**
     * Archive une transaction vers la base Neon
     */
    private function archiveTransactionToNeon(Transaction $transaction): void
    {
        DB::connection('neon')->table('archived_transactions')->insert([
            'original_id' => $transaction->id,
            'compte_id' => $transaction->compte_id,
            'type' => $transaction->type,
            'montant' => $transaction->montant,
            'devise' => $transaction->devise,
            'description' => $transaction->description,
            'statut' => $transaction->statut,
            'date_transaction' => $transaction->date_transaction,
            'created_at' => $transaction->created_at,
            'updated_at' => $transaction->updated_at,
            'archived_at' => now()
        ]);
    }
}
