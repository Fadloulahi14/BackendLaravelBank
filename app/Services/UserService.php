<?php

namespace App\Services;

use App\Models\User;
use App\Models\Admin;
use App\Models\Client;
use App\Models\Role;
use App\Constants\Messages;
use App\Constants\StatusCodes;
use Illuminate\Support\Str;

/**
 * Service de gestion des utilisateurs
 * Contient toute la logique métier liée aux utilisateurs
 */
class UserService
{
    /**
     * Créer un nouvel utilisateur
     *
     * @param array $data
     * @return User
     * @throws \Exception
     */
    public function createUser(array $data): User
    {
        // Validation des données
        $this->validateUserData($data);

        // Déterminer le modèle à utiliser selon le rôle
        $roleSlug = $this->getRoleSlug($data['role_id'] ?? null);

        if ($roleSlug === 'admin') {
            return $this->createAdmin($data);
        } else {
            return $this->createClient($data);
        }
    }

    /**
     * Créer un administrateur
     *
     * @param array $data
     * @return Admin
     */
    private function createAdmin(array $data): Admin
    {
        return Admin::create([
            'id' => (string) Str::uuid(),
            'nom' => $data['nom'],
            'nci' => $data['nci'],
            'email' => $data['email'],
            'telephone' => $data['telephone'],
            'adresse' => $data['adresse'],
        ]);
    }

    /**
     * Créer un client
     *
     * @param array $data
     * @return Client
     */
    private function createClient(array $data): Client
    {
        return Client::create([
            'id' => (string) Str::uuid(),
            'nom' => $data['nom'],
            'nci' => $data['nci'],
            'email' => $data['email'],
            'telephone' => $data['telephone'],
            'adresse' => $data['adresse'],
        ]);
    }

    /**
     * Mettre à jour un utilisateur
     *
     * @param User $user
     * @param array $data
     * @return User
     * @throws \Exception
     */
    public function updateUser(User $user, array $data): User
    {
        // Validation des données
        $this->validateUserData($data, false);

        // Mise à jour des données communes
        $updateData = array_intersect_key($data, array_flip([
            'nom', 'nci', 'email', 'telephone', 'adresse'
        ]));

        if ($user->client) {
            $user->client->update($updateData);
        } elseif ($user->admin) {
            $user->admin->update($updateData);
        }

        // Recharger l'utilisateur avec ses relations
        return $user->fresh(['client', 'admin']);
    }

    /**
     * Supprimer un utilisateur
     *
     * @param User $user
     * @return bool
     * @throws \Exception
     */
    public function deleteUser(User $user): bool
    {
        // Vérifications de sécurité avant suppression
        if ($this->hasActiveAccounts($user)) {
            throw new \Exception('Impossible de supprimer un utilisateur avec des comptes actifs', StatusCodes::BAD_REQUEST);
        }

        try {
            $user->delete();
            return true;
        } catch (\Exception $e) {
            throw new \Exception('Erreur lors de la suppression de l\'utilisateur', StatusCodes::INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Lister les utilisateurs avec filtrage et pagination
     *
     * @param array $filters
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function listUsers(array $filters = [], int $perPage = 10)
    {
        $query = User::query();

        // Filtrage par type
        if (isset($filters['type']) && in_array($filters['type'], ['client', 'admin'])) {
            if ($filters['type'] === 'client') {
                $query->whereHas('client');
            } elseif ($filters['type'] === 'admin') {
                $query->whereHas('admin');
            }
        }

        // Recherche par nom, email ou téléphone
        if (isset($filters['search']) && !empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->whereHas('client', function ($clientQuery) use ($search) {
                    $clientQuery->where('nom', 'like', "%{$search}%")
                               ->orWhere('email', 'like', "%{$search}%")
                               ->orWhere('telephone', 'like', "%{$search}%");
                })->orWhereHas('admin', function ($adminQuery) use ($search) {
                    $adminQuery->where('nom', 'like', "%{$search}%")
                              ->orWhere('email', 'like', "%{$search}%")
                              ->orWhere('telephone', 'like', "%{$search}%");
                });
            });
        }

        // Tri
        $sortBy = $filters['sort'] ?? 'created_at';
        $sortOrder = $filters['order'] ?? 'desc';

        if ($sortBy === 'nom') {
            $query->join('clients', 'users.id', '=', 'clients.user_id')
                  ->orderBy('clients.nom', $sortOrder)
                  ->select('users.*');
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        return $query->with(['client', 'admin'])->paginate($perPage);
    }

    /**
     * Trouver un utilisateur par ID
     *
     * @param string $id
     * @return User
     * @throws \Exception
     */
    public function findUserById(string $id): User
    {
        $user = User::with(['client', 'admin'])->find($id);

        if (!$user) {
            throw new \Exception(Messages::USER_NOT_FOUND, StatusCodes::NOT_FOUND);
        }

        return $user;
    }

    /**
     * Vérifier si un utilisateur a des comptes actifs
     *
     * @param User $user
     * @return bool
     */
    private function hasActiveAccounts(User $user): bool
    {
        return $user->comptes()->where('statut', 'actif')->exists();
    }

    /**
     * Obtenir le slug du rôle
     *
     * @param string|null $roleId
     * @return string
     */
    private function getRoleSlug(?string $roleId): string
    {
        if ($roleId) {
            $role = Role::find($roleId);
            return $role ? $role->slug : 'client';
        }

        return 'client'; // Rôle par défaut
    }

    /**
     * Valider les données utilisateur
     *
     * @param array $data
     * @param bool $isCreation
     * @throws \Exception
     */
    private function validateUserData(array $data, bool $isCreation = true): void
    {
        $requiredFields = ['nom', 'nci', 'email', 'telephone', 'adresse'];

        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                throw new \Exception(Messages::ERROR_VALIDATION, StatusCodes::BAD_REQUEST);
            }
        }

        // Validation du format email
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \Exception(Messages::ERROR_VALIDATION, StatusCodes::BAD_REQUEST);
        }

        // Vérifications d'unicité pour la création
        if ($isCreation) {
            if (Client::where('email', $data['email'])->exists() ||
                Admin::where('email', $data['email'])->exists()) {
                throw new \Exception(Messages::EMAIL_ALREADY_EXISTS, StatusCodes::CONFLICT);
            }

            if (Client::where('telephone', $data['telephone'])->exists() ||
                Admin::where('telephone', $data['telephone'])->exists()) {
                throw new \Exception('Ce numéro de téléphone est déjà utilisé', StatusCodes::CONFLICT);
            }

            if (Client::where('nci', $data['nci'])->exists() ||
                Admin::where('nci', $data['nci'])->exists()) {
                throw new \Exception('Ce numéro CNI est déjà utilisé', StatusCodes::CONFLICT);
            }
        }
    }

    /**
     * Obtenir les statistiques des utilisateurs
     *
     * @return array
     */
    public function getUserStats(): array
    {
        return [
            'total_users' => User::count(),
            'total_clients' => Client::count(),
            'total_admins' => Admin::count(),
            'active_clients' => Client::whereHas('user.comptes', function ($query) {
                $query->where('statut', 'actif');
            })->count(),
        ];
    }

    /**
     * Rechercher des utilisateurs
     *
     * @param string $query
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function searchUsers(string $query, int $limit = 10)
    {
        return User::whereHas('client', function ($clientQuery) use ($query) {
            $clientQuery->where('nom', 'like', "%{$query}%")
                       ->orWhere('email', 'like', "%{$query}%");
        })->orWhereHas('admin', function ($adminQuery) use ($query) {
            $adminQuery->where('nom', 'like', "%{$query}%")
                      ->orWhere('email', 'like', "%{$query}%");
        })->with(['client', 'admin'])->limit($limit)->get();
    }
}