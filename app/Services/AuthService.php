<?php

namespace App\Services;

use App\Models\User;
use App\Constants\Messages;
use App\Constants\StatusCodes;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Service de gestion de l'authentification
 * Contient toute la logique métier liée à l'authentification
 */
class AuthService
{
    /**
     * Authentifier un utilisateur
     *
     * @param array $credentials
     * @return array
     * @throws \Exception
     */
    public function login(array $credentials): array
    {
      
        $this->validateLoginCredentials($credentials);

        
        $user = User::where('login', $credentials['login'])->first();

       
        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw new \Exception(Messages::USER_INVALID_CREDENTIALS, StatusCodes::UNAUTHORIZED);
        }

        try {
            $scopes = $user->type === 'admin' ? ['admin'] : ['client'];
            $token = $user->createToken('API Token', $scopes);

            $refreshToken = \Illuminate\Support\Str::random(1030);

            return [
                'user' => [
                    'id' => $user->id,
                    'login' => $user->login,
                    'type' => $user->type,
                ],
                'access_token' => $token->accessToken,
                'refresh_token' => $refreshToken,
                'token_type' => 'Bearer',
                'scopes' => $scopes,
            ];
        } catch (\Exception $tokenException) {
            Log::error('Token creation error: ' . $tokenException->getMessage());
            Log::error('Token creation error trace: ' . $tokenException->getTraceAsString());
            throw new \Exception('Erreur lors de la génération du token', StatusCodes::INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Inscrire un nouveau client
     *
     * @param array $data
     * @return array
     * @throws \Exception
     */
    public function register(array $data): array
    {
        // Validation des données d'inscription
        $this->validateRegistrationData($data);

        // Vérification de l'unicité du login
        if (User::where('login', $data['login'])->exists()) {
            throw new \Exception('Erreur de validation', StatusCodes::UNPROCESSABLE_ENTITY);
        }

        // Création de l'utilisateur
        $user = User::create([
            'id' => (string) Str::uuid(),
            'login' => $data['login'],
            'password' => Hash::make($data['password']),
        ]);

        // Création du profil client
        $user->client()->create([
            'id' => (string) Str::uuid(),
            'nom' => $data['nom'],
            'nci' => $data['nci'],
            'email' => $data['email'],
            'telephone' => $data['telephone'],
            'adresse' => $data['adresse'],
        ]);

        // Génération du token avec scope approprié
        $scopes = $user->type === 'admin' ? ['admin'] : ['client'];
        $token = $user->createToken('API Token', $scopes);

        return [
            'user' => [
                'id' => $user->id,
                'login' => $user->login,
                'type' => $user->type,
            ],
            'access_token' => $token->accessToken,
            'token_type' => 'Bearer',
            'scopes' => $scopes,
        ];
    }

    /**
     * Déconnecter un utilisateur
     *
     * @param User $user
     * @return bool
     */
    public function logout(User $user): bool
    {
        try {
            $user->token()->revoke();
            return true;
        } catch (\Exception $e) {
            Log::error('Logout error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Valider les données de connexion
     *
     * @param array $credentials
     * @throws \Exception
     */
    private function validateLoginCredentials(array $credentials): void
    {
        if (empty($credentials['login']) || empty($credentials['password'])) {
            throw new \Exception(Messages::ERROR_VALIDATION, StatusCodes::BAD_REQUEST);
        }

        if (!is_string($credentials['login']) || !is_string($credentials['password'])) {
            throw new \Exception(Messages::ERROR_VALIDATION, StatusCodes::BAD_REQUEST);
        }
    }

    /**
     * Valider les données d'inscription
     *
     * @param array $data
     * @throws \Exception
     */
    private function validateRegistrationData(array $data): void
    {
        $requiredFields = ['login', 'password', 'nom', 'nci', 'email', 'telephone', 'adresse'];

        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                throw new \Exception(Messages::ERROR_VALIDATION, StatusCodes::BAD_REQUEST);
            }
        }

        // Validation du format email
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \Exception(Messages::ERROR_VALIDATION, StatusCodes::BAD_REQUEST);
        }

        // Validation de la longueur du mot de passe
        if (strlen($data['password']) < 6) {
            throw new \Exception(Messages::ERROR_VALIDATION, StatusCodes::BAD_REQUEST);
        }
    }

    /**
     * Générer un login unique
     *
     * @param string $baseName
     * @return string
     */
    public function generateUniqueLogin(string $baseName): string
    {
        $baseLogin = Str::slug($baseName, '');
        $login = $baseLogin;
        $counter = 1;

        while (User::where('login', $login)->exists()) {
            $login = $baseLogin . $counter;
            $counter++;
        }

        return $login;
    }

    /**
     * Vérifier si un utilisateur est authentifié
     *
     * @param User|null $user
     * @return bool
     */
    public function isAuthenticated(?User $user): bool
    {
        return $user !== null;
    }

    /**
     * Rafraîchir le token d'un utilisateur
     *
     * @param User $user
     * @return string
     * @throws \Exception
     */
    public function refreshToken(User $user): string
    {
        try {
            // Révoquer l'ancien token
            $user->token()->revoke();

            // Créer un nouveau token
            return $user->createToken('API Token')->accessToken;
        } catch (\Exception $e) {
            Log::error('Token refresh error: ' . $e->getMessage());
            throw new \Exception('Erreur lors du rafraîchissement du token', StatusCodes::INTERNAL_SERVER_ERROR);
        }
    }
}