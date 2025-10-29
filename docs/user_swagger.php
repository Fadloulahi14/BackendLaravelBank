<?php
/**
 * @OA\Schema(
 *     schema="User",
 *     @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
 *     @OA\Property(property="nom", type="string", example="Amadou Diallo"),
 *     @OA\Property(property="nci", type="string", example="123456789012"),
 *     @OA\Property(property="email", type="string", format="email", example="amadou.diallo@email.com"),
 *     @OA\Property(property="telephone", type="string", example="+221771234567"),
 *     @OA\Property(property="adresse", type="string", example="Dakar, Sénégal"),
 *     @OA\Property(property="role", type="string", enum={"admin", "client"}, example="client"),
 *     @OA\Property(property="dateCreation", type="string", format="date-time"),
 *     @OA\Property(property="derniereModification", type="string", format="date-time")
 * )
 */

/**
 * @OA\Get(
 *     path="/users",
 *     summary="Lister tous les utilisateurs",
 *     description="Récupère une liste paginée d'utilisateurs avec possibilité de filtrage et tri",
 *     operationId="getUsers",
 *     tags={"Utilisateurs"},
 *     @OA\Parameter(
 *         name="page",
 *         in="query",
 *         description="Numéro de page",
 *         required=false,
 *         @OA\Schema(type="integer", default=1)
 *     ),
 *     @OA\Parameter(
 *         name="limit",
 *         in="query",
 *         description="Nombre d'éléments par page",
 *         required=false,
 *         @OA\Schema(type="integer", default=10, maximum=100)
 *     ),
 *     @OA\Parameter(
 *         name="role",
 *         in="query",
 *         description="Filtrer par rôle",
 *         required=false,
 *         @OA\Schema(type="string", enum={"admin", "client"})
 *     ),
 *     @OA\Parameter(
 *         name="search",
 *         in="query",
 *         description="Recherche par nom, email ou téléphone",
 *         required=false,
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Parameter(
 *         name="sort",
 *         in="query",
 *         description="Champ de tri",
 *         required=false,
 *         @OA\Schema(type="string", enum={"dateCreation", "nom"}, default="dateCreation")
 *     ),
 *     @OA\Parameter(
 *         name="order",
 *         in="query",
 *         description="Ordre de tri",
 *         required=false,
 *         @OA\Schema(type="string", enum={"asc", "desc"}, default="desc")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Liste des utilisateurs récupérée avec succès",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/User")),
 *             @OA\Property(property="pagination", ref="#/components/schemas/PaginationMeta"),
 *             @OA\Property(property="links", type="object",
 *                 @OA\Property(property="self", type="string"),
 *                 @OA\Property(property="next", type="string"),
 *                 @OA\Property(property="first", type="string"),
 *                 @OA\Property(property="last", type="string")
 *             )
 *         )
 *     )
 * )
 */

/**
 * @OA\Post(
 *     path="/users",
 *     summary="Créer un nouvel utilisateur",
 *     description="Crée un nouvel utilisateur dans le système",
 *     operationId="createUser",
 *     tags={"Utilisateurs"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"nom", "nci", "email", "telephone", "adresse"},
 *             @OA\Property(property="nom", type="string", description="Nom complet"),
 *             @OA\Property(property="nci", type="string", description="Numéro CNI"),
 *             @OA\Property(property="email", type="string", format="email", description="Adresse email"),
 *             @OA\Property(property="telephone", type="string", description="Numéro de téléphone"),
 *             @OA\Property(property="adresse", type="string", description="Adresse complète"),
 *             @OA\Property(property="role", type="string", enum={"admin", "client"}, description="Rôle utilisateur", default="client")
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Utilisateur créé avec succès",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Utilisateur créé avec succès"),
 *             @OA\Property(property="data", ref="#/components/schemas/User")
 *         )
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Erreur de validation",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=false),
 *             @OA\Property(property="message", type="string", example="Erreur de validation"),
 *             @OA\Property(property="errors", type="object")
 *         )
 *     )
 * )
 */

/**
 * @OA\Get(
 *     path="/users/{id}",
 *     summary="Détails d'un utilisateur",
 *     description="Récupère les détails d'un utilisateur spécifique avec ses comptes",
 *     operationId="getUser",
 *     tags={"Utilisateurs"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID de l'utilisateur",
 *         @OA\Schema(type="string", format="uuid")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Détails de l'utilisateur récupérés",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", ref="#/components/schemas/User")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Utilisateur non trouvé",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=false),
 *             @OA\Property(property="message", type="string"),
 *             @OA\Property(property="errors", type="object",
 *                 @OA\Property(property="code", type="string", example="USER_NOT_FOUND"),
 *                 @OA\Property(property="details", type="object")
 *             )
 *         )
 *     )
 * )
 */

/**
 * @OA\Patch(
 *     path="/users/{id}",
 *     summary="Mettre à jour un utilisateur",
 *     description="Met à jour partiellement un utilisateur existant",
 *     operationId="updateUser",
 *     tags={"Utilisateurs"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID de l'utilisateur",
 *         @OA\Schema(type="string", format="uuid")
 *     ),
 *     @OA\RequestBody(
 *         @OA\JsonContent(
 *             @OA\Property(property="nom", type="string", description="Nouveau nom"),
 *             @OA\Property(property="nci", type="string", description="Nouveau numéro CNI"),
 *             @OA\Property(property="email", type="string", format="email", description="Nouvel email"),
 *             @OA\Property(property="telephone", type="string", description="Nouveau téléphone"),
 *             @OA\Property(property="adresse", type="string", description="Nouvelle adresse"),
 *             @OA\Property(property="role", type="string", enum={"admin", "client"}, description="Nouveau rôle")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Utilisateur mis à jour avec succès",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Utilisateur mis à jour avec succès"),
 *             @OA\Property(property="data", ref="#/components/schemas/User")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Utilisateur non trouvé",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=false),
 *             @OA\Property(property="message", type="string"),
 *             @OA\Property(property="errors", type="object")
 *         )
 *     )
 * )
 */

/**
 * @OA\Delete(
 *     path="/users/{id}",
 *     summary="Supprimer un utilisateur",
 *     description="Supprime un utilisateur existant",
 *     operationId="deleteUser",
 *     tags={"Utilisateurs"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID de l'utilisateur",
 *         @OA\Schema(type="string", format="uuid")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Utilisateur supprimé avec succès",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Utilisateur supprimé avec succès")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Utilisateur non trouvé",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=false),
 *             @OA\Property(property="message", type="string"),
 *             @OA\Property(property="errors", type="object")
 *         )
 *     )
 * )
 */