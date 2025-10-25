<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use App\Config\ValidationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */
class AuthController extends Controller
{
    use ApiResponse;

    private ValidationService $validationService;

    public function __construct()
    {
        $this->validationService = ValidationService::getInstance();
    }

    /**
     * @OA\Post(
     *     path="/login",
     *     summary="Connexion utilisateur",
     *     description="Authentifie un utilisateur et retourne un token d'accès",
     *     operationId="login",
     *     tags={"Authentification"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"login", "password"},
     *             @OA\Property(property="login", type="string", description="Login de l'utilisateur"),
     *             @OA\Property(property="password", type="string", format="password", description="Mot de passe")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Connexion réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Connexion réussie"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="id", type="string"),
     *                     @OA\Property(property="login", type="string"),
     *                     @OA\Property(property="type", type="string", enum={"admin", "client"})
     *                 ),
     *                 @OA\Property(property="token", type="string", description="Token d'accès Bearer")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Identifiants invalides",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Identifiants invalides")
     *         )
     *     )
     * )
     */
    public function login(Request $request): JsonResponse
    {
        // Validation avec notre système personnalisé
        $validation = $this->validationService->validateLogin($request->all());

        if (!$validation['isValid']) {
            return $this->errorResponse('Erreur de validation', 422, [
                'errors' => $validation['errors']
            ]);
        }

        $user = User::where('login', $request->login)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->errorResponse('Identifiants invalides', 401);
        }

        $token = $user->createToken('API Token')->accessToken;

        return $this->successResponse([
            'user' => [
                'id' => $user->id,
                'login' => $user->login,
                'type' => $user->type,
            ],
            'token' => $token,
        ], 'Connexion réussie');
    }

    /**
     * @OA\Post(
     *     path="/register",
     *     summary="Inscription d'un nouveau client",
     *     description="Crée un nouveau compte client",
     *     operationId="register",
     *     tags={"Authentification"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"login", "password", "nom", "nci", "email", "telephone", "adresse"},
     *             @OA\Property(property="login", type="string", description="Login unique"),
     *             @OA\Property(property="password", type="string", format="password", description="Mot de passe"),
     *             @OA\Property(property="nom", type="string", description="Nom complet"),
     *             @OA\Property(property="nci", type="string", description="Numéro de carte d'identité"),
     *             @OA\Property(property="email", type="string", format="email", description="Adresse email"),
     *             @OA\Property(property="telephone", type="string", description="Numéro de téléphone"),
     *             @OA\Property(property="adresse", type="string", description="Adresse complète")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Inscription réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Inscription réussie"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", type="object"),
     *                 @OA\Property(property="token", type="string")
     *             )
     *         )
     *     )
     * )
     */
    public function register(Request $request): JsonResponse
    {
        // Validation avec notre système personnalisé
        $validation = $this->validationService->validateRegister($request->all());

        if (!$validation['isValid']) {
            return $this->errorResponse('Erreur de validation', 422, [
                'errors' => $validation['errors']
            ]);
        }

        // Vérifier l'unicité du login
        if (User::where('login', $request->login)->exists()) {
            return $this->errorResponse('Erreur de validation', 422, [
                'errors' => [
                    'login' => 'Ce login est déjà utilisé'
                ]
            ]);
        }

        $user = User::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'login' => $request->login,
            'password' => Hash::make($request->password),
        ]);

        // Créer le profil client (les validations d'unicité sont gérées au niveau des tables séparées)
        $user->client()->create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'nom' => $request->nom,
            'nci' => $request->nci,
            'email' => $request->email,
            'telephone' => $request->telephone,
            'adresse' => $request->adresse,
        ]);

        $token = $user->createToken('API Token')->accessToken;

        return $this->successResponse([
            'user' => [
                'id' => $user->id,
                'login' => $user->login,
                'type' => $user->type,
            ],
            'token' => $token,
        ], 'Inscription réussie', 201);
    }

    /**
     * @OA\Post(
     *     path="/logout",
     *     summary="Déconnexion",
     *     description="Révoque le token d'accès actuel",
     *     operationId="logout",
     *     tags={"Authentification"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Déconnexion réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Déconnexion réussie")
     *         )
     *     )
     * )
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->token()->revoke();

        return $this->successResponse(null, 'Déconnexion réussie');
    }
}
