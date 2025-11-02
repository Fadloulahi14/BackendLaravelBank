<?php

namespace App\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Repositories\UserRepository;
use App\Repositories\ClientRepository;
use App\Models\Compte;
use App\Models\User;

class AccountService
{
    protected $users;
    protected $clients;
    public function __construct(UserRepository $users, ClientRepository $clients)
    {
        $this->users = $users;
        $this->clients = $clients;
    }

    public function createAccount(array $data, array $compteOverrides = []): User
    {
        return DB::transaction(function () use ($data, $compteOverrides) {
            $existing = $this->users->findByEmailOrTelephone($data['email'] ?? null, $data['telephone'] ?? null);

            $password = Str::random(10);

            if (! $existing) {
                $user = User::create([
                    'nom' => $data['nom'] ?? null,
                    'prenom' => $data['prenom'] ?? null,
                    'email' => $data['email'] ?? null,
                    'telephone' => $data['telephone'] ?? null,
                    'password' => Hash::make($password),
                ]);
            } else {
                $user = $existing;
            }

            if (! $user->client) {
                $clientData = [
                    'id' => (string) Str::uuid(),
                    'user_id' => $user->id,
                    'nom' => $data['nom'] ?? null,
                    'prenom' => $data['prenom'] ?? null,
                    'email' => $data['email'] ?? null,
                    'telephone' => $data['telephone'] ?? null,
                    'adresse' => $data['adresse'] ?? null,
                    'date_naissance' => $data['date_naissance'] ?? null,
                    'nci' => $data['nci'] ?? null,
                ];
                $this->clients->create($clientData);
                $user->load('client');
            }

            $numero = Compte::generateNumero();
            $compteData = array_merge([
                'client_id' => $user->client->id,
                'numero_compte' => $numero,
                'user_id' => $user->id,
                'type_compte' => 'courant',
                'solde' => 0,
                'devise' => 'FCFA',
                'statut_compte' => 'actif',
                'date_creation' => now(),
            ], $compteOverrides);
            Compte::create($compteData);

            if (! empty($user->email)) {
                try {
                    Mail::raw("Bienvenue {$user->prenom}, vos identifiants de connexion sont :\nEmail: {$user->email}\nMot de passe: {$password}", function ($message) use ($user) {
                        $message->to($user->email)->subject('Création de votre compte');
                    });
                } catch (\Throwable $e) {
                    Log::warning('Failed to send welcome email', [
                        'user_id' => $user->id,
                        'email' => $user->email,
                        'error' => $e->getMessage()
                    ]);
                    // Continue without failing the account creation
                }
            }

            if (! empty($user->telephone)) {
                try {
                    $service = app()->make(\App\Services\MessageServiceInterface::class);
                    $service->sendMessage($user->telephone, "Bienvenue {$user->prenom}, vos identifiants sont Email: {$user->email} Mot de passe: {$password}");
                } catch (\Throwable $e) {
                    Log::warning('Failed to send welcome SMS', [
                        'user_id' => $user->id,
                        'telephone' => $user->telephone,
                        'error' => $e->getMessage()
                    ]);
                    // Continue without failing the account creation
                }
            }

            return $user->fresh();
        });
    }
}
