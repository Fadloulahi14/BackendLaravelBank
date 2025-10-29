<?php
/**
 * @OA\PathItem(
 *     path="/api/v1"
 * )
 */

/**
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */

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