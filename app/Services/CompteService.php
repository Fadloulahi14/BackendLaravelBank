<?php

namespace App\Services;

use App\Events\CompteCreeEvent;
use App\Models\Compte;
use App\Models\User;
use App\Models\Transaction;
use App\Constants\Messages;
use App\Constants\StatusCodes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Service de gestion des comptes bancaires
 * Contient toute la logique métier liée aux comptes
 */
class CompteService
{
    /**
     * Générer un login unique basé sur le nom du titulaire
     */
    public function generateUniqueLogin(string $nom): string
    {
        $baseLogin = Str::slug($nom, '');
        $login = $baseLogin;
        $counter = 1;

        while (User::where('login', $login)->exists()) {
            $login = $baseLogin . $counter;
            $counter++;
        }

        return $login;
    }

    /**
     * Créer un nouveau compte bancaire
     */
    public function createCompte(array $data): Compte
    {
        return DB::transaction(function () use ($data) {
            $user = null;
            $motDePasseGenere = null;
            $codeActivation = null;
            $isFirstAccount = false;

            // Vérifier si c'est un client existant ou nouveau
            if (!empty($data['client']['id'])) {
                // Client existant
                $user = User::find($data['client']['id']);
                if (!$user) {
                    throw new \Exception(Messages::CLIENT_NOT_FOUND, StatusCodes::NOT_FOUND);
                }

                // Vérifier si c'est le premier compte du client
                $isFirstAccount = $user->comptes()->count() === 0;

                Log::info('Client existant détecté', [
                    'user_id' => $user->id,
                    'isFirstAccount' => $isFirstAccount,
                    'comptes_count' => $user->comptes()->count()
                ]);
            } else {
                // Nouveau client - créer l'utilisateur et le profil client
                $isFirstAccount = true; // C'est forcément le premier compte

                $motDePasseGenere = Str::random(12);
                $codeActivation = Str::random(6);

                Log::info('Nouveau client - génération des identifiants', [
                    'motDePasseGenere' => !empty($motDePasseGenere),
                    'codeActivation' => !empty($codeActivation)
                ]);

                $user = User::create([
                    'id' => (string) Str::uuid(),
                    'login' => $this->generateUniqueLogin($data['client']['titulaire']),
                    'password' => bcrypt($motDePasseGenere),
                    'role' => 'client',
                ]);

                // Créer le profil client
                $user->client()->create([
                    'id' => (string) Str::uuid(),
                    'nom' => $data['client']['titulaire'],
                    'nci' => $data['client']['nci'],
                    'email' => $data['client']['email'],
                    'telephone' => $data['client']['telephone'],
                    'adresse' => $data['client']['adresse'],
                ]);
            }

            // Générer les identifiants pour tous les comptes de nouveaux clients
            if (!$motDePasseGenere) {
                $motDePasseGenere = Str::random(12);
                $codeActivation = Str::random(6);

                // Mettre à jour l'utilisateur avec les nouveaux identifiants
                $user->update([
                    'login' => $this->generateUniqueLogin($user->client->nom),
                    'password' => bcrypt($motDePasseGenere),
                ]);
            }

            // Créer le compte
            $compte = Compte::create([
                'id' => (string) Str::uuid(),
                'numero_compte' => Compte::generateNumeroCompte(),
                'user_id' => $user->id,
                'type' => $data['type'],
                'solde' => $data['soldeInitial'] ?? 0,
                'devise' => $data['devise'] ?? 'FCFA',
                'statut' => 'actif',
                'metadonnees' => [
                    'derniereModification' => now(),
                    'version' => 1,
                    'codeActivation' => $codeActivation,
                ],
            ]);

            // Créer la transaction initiale si solde > 0
            if (($data['soldeInitial'] ?? 0) > 0) {
                Transaction::create([
                    'id' => (string) Str::uuid(),
                    'compte_id' => $compte->id,
                    'type' => 'depot',
                    'montant' => $data['soldeInitial'],
                    'devise' => 'FCF', // Correction: utiliser 3 caractères maximum
                    'description' => 'Solde initial lors de la création du compte',
                    'statut' => 'validee',
                    'date_transaction' => now(),
                ]);
            }

            // Déclencher l'événement pour tous les nouveaux comptes avec génération d'identifiants
            if ($motDePasseGenere && $codeActivation) {
                Log::info('Déclenchement de l\'événement CompteCreeEvent', [
                    'compte_id' => $compte->id,
                    'user_id' => $user->id,
                    'motDePasseGenere' => !empty($motDePasseGenere),
                    'codeActivation' => !empty($codeActivation),
                    'isFirstAccount' => $isFirstAccount
                ]);
                event(new CompteCreeEvent($compte, $user, $motDePasseGenere, $codeActivation));
            } else {
                Log::warning('Événement CompteCreeEvent non déclenché - identifiants manquants', [
                    'compte_id' => $compte->id,
                    'user_id' => $user->id,
                    'motDePasseGenere' => !empty($motDePasseGenere),
                    'codeActivation' => !empty($codeActivation),
                    'isFirstAccount' => $isFirstAccount
                ]);
            }

            return $compte;
        });
    }

    /**
     * Mettre à jour un compte
     */
    public function updateCompte(Compte $compte, array $data): Compte
    {
        // Vérifier les règles métier pour le statut
        if (isset($data['statut'])) {
            if ($data['statut'] === 'bloque' && $compte->type === 'cheque') {
                throw new \Exception(Messages::COMPTE_CHEQUE_CANNOT_BLOCK, StatusCodes::BAD_REQUEST);
            }
            if ($data['statut'] === 'bloque' && $compte->type !== 'epargne') {
                throw new \Exception(Messages::COMPTE_EPARGNE_ONLY_BLOCK, StatusCodes::BAD_REQUEST);
            }
        }

        // Mettre à jour les informations du compte
        $compteData = array_intersect_key($data, array_flip(['titulaire', 'type', 'solde', 'devise', 'statut']));
        if (!empty($compteData)) {
            $compte->update($compteData);
            $compte->metadonnees = array_merge($compte->metadonnees ?? [], [
                'derniereModification' => now(),
                'version' => ($compte->metadonnees['version'] ?? 1) + 1
            ]);
            $compte->save();
        }

        // Mettre à jour les informations client si fournies
        if (isset($data['informationsClient'])) {
            $clientData = $data['informationsClient'];
            $client = $compte->user->client;

            if ($client) {
                $updateData = [];

                if (isset($clientData['telephone'])) {
                    $updateData['telephone'] = $clientData['telephone'];
                }

                if (isset($clientData['email'])) {
                    $updateData['email'] = $clientData['email'];
                }

                if (isset($clientData['password'])) {
                    $updateData['password'] = bcrypt($clientData['password']);
                }

                if (isset($clientData['nci'])) {
                    $updateData['nci'] = $clientData['nci'];
                }

                if (!empty($updateData)) {
                    $client->update($updateData);
                }
            }
        }

        return $compte->fresh();
    }

    /**
     * Supprimer un compte (soft delete)
     */
    public function deleteCompte(Compte $compte): array
    {
        $compte->delete();

        return [
            'id' => $compte->id,
            'numeroCompte' => $compte->numero_compte,
            'statut' => 'ferme',
            'dateFermeture' => now()->toISOString()
        ];
    }

    /**
     * Bloquer un compte
     */
    public function bloquerCompte(Compte $compte, array $data): Compte
    {
        if ($compte->statut !== 'actif') {
            throw new \Exception(Messages::COMPTE_ACTIVE_REQUIRED, StatusCodes::BAD_REQUEST);
        }

        if ($compte->type !== 'epargne') {
            throw new \Exception(Messages::COMPTE_EPARGNE_ONLY_BLOCK, StatusCodes::BAD_REQUEST);
        }

        if ($compte->type === 'cheque') {
            throw new \Exception(Messages::COMPTE_CHEQUE_CANNOT_BLOCK, StatusCodes::BAD_REQUEST);
        }

        $dateDebutBlocage = \Carbon\Carbon::parse($data['dateDebut']);
        $dateFinBlocage = $data['unite'] === 'jours'
            ? $dateDebutBlocage->copy()->addDays($data['duree'])
            : $dateDebutBlocage->copy()->addMonths($data['duree']);

        $compte->update([
            'metadonnees' => array_merge($compte->metadonnees ?? [], [
                'motifBlocage' => $data['motif'],
                'dateDebutBlocage' => $dateDebutBlocage,
                'dateFinBlocage' => $dateFinBlocage,
                'dureeBlocage' => $data['duree'],
                'uniteBlocage' => $data['unite'],
                'statutProgramme' => 'bloque', 
                'derniereModification' => now(),
                'version' => ($compte->metadonnees['version'] ?? 1) + 1
            ])
        ]);

        \App\Jobs\ArchiveExpiredBlockedAccounts::dispatch()->delay($dateDebutBlocage);

        return $compte;
    }

    /**
     * Débloquer un compte
     */
    public function debloquerCompte(Compte $compte, string $motif): Compte
    {
        if ($compte->statut !== 'bloque') {
            throw new \Exception(Messages::COMPTE_NOT_BLOCKED, StatusCodes::BAD_REQUEST);
        }

        $compte->update([
            'statut' => 'actif',
            'metadonnees' => array_merge($compte->metadonnees ?? [], [
                'motifDeblocage' => $motif,
                'dateDeblocage' => now(),
                'derniereModification' => now(),
                'version' => ($compte->metadonnees['version'] ?? 1) + 1
            ])
        ]);

        return $compte;
    }

    /**
     * Lister les comptes avec filtrage et pagination
     */
    public function listComptes($request, $user)
    {
        $query = Compte::with('user')->nonSupprime();

        // Exclure les comptes fermés ou bloqués de la liste générale
        $query->whereNotIn('statut', ['ferme', 'bloque']);

        // Autorisation basée sur le rôle
        if ($user->role === 'client') {
            // Client ne voit que ses propres comptes
            $query->utilisateur($user->id);
        }
        // Admin voit tous les comptes actifs (pas de restriction supplémentaire)

        // Appliquer les scopes de filtrage
        if ($request->has('type') && $request->type) {
            $query->type($request->type);
        }

        if ($request->has('statut') && $request->statut) {
            $query->statut($request->statut);
        }

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->numero($search)
                  ->orWhereHas('user', function ($userQuery) use ($search) {
                      $userQuery->where('nom', 'like', "%{$search}%");
                  });
            });
        }

        // Tri
        $sort = $request->get('sort', 'dateCreation');
        $order = $request->get('order', 'desc');

        switch ($sort) {
            case 'dateCreation':
                $query->orderBy('created_at', $order);
                break;
            case 'solde':
                $query->orderBy('solde', $order);
                break;
            case 'titulaire':
                $query->join('users', 'comptes.user_id', '=', 'users.id')
                      ->orderBy('users.nom', $order)
                      ->select('comptes.*');
                break;
            default:
                $query->orderBy('created_at', $order);
        }

        // Pagination
        $limit = min($request->get('limit', 10), 100);
        return $query->paginate($limit);
    }
}