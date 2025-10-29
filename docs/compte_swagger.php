<?php

/**
 * @OA\Info(
 *     title="API de Gestion des Clients & Comptes",
 *     version="1.0.0",
 *     description="API RESTful pour la gestion des clients et de leurs comptes bancaires"
 * )
 *
 * @OA\Server(
 *     url="https://backendlaravelbank.onrender.com/api/v1",
 *     description="Serveur de production"
 * )
 * @OA\Server(
 *     url="http://localhost:8000/api/v1",
 *     description="Serveur de développement"
 * )
 *
 *
 * @OA\Schema(
 *     schema="ApiResponse",
 *     @OA\Property(property="success", type="boolean"),
 *     @OA\Property(property="message", type="string"),
 *     @OA\Property(property="data", type="object")
 * )
 *
 * @OA\Schema(
 *     schema="PaginationMeta",
 *     @OA\Property(property="currentPage", type="integer"),
 *     @OA\Property(property="totalPages", type="integer"),
 *     @OA\Property(property="totalItems", type="integer"),
 *     @OA\Property(property="itemsPerPage", type="integer"),
 *     @OA\Property(property="hasNext", type="boolean"),
 *     @OA\Property(property="hasPrevious", type="boolean")
 * )
 *
 * @OA\Schema(
 *     schema="Compte",
 *     @OA\Property(property="id", type="string", example="550e8400-e29b-41d4-a716-446655440000"),
 *     @OA\Property(property="numeroCompte", type="string", example="C00123456"),
 *     @OA\Property(property="titulaire", type="string", example="Amadou Diallo"),
 *     @OA\Property(property="type", type="string", enum={"epargne", "cheque"}),
 *     @OA\Property(property="solde", type="number", format="float", example=1250000),
 *     @OA\Property(property="devise", type="string", example="FCFA"),
 *     @OA\Property(property="dateCreation", type="string", format="date-time"),
 *     @OA\Property(property="statut", type="string", enum={"actif", "bloque", "ferme"}),
 *     @OA\Property(property="metadonnees", type="object",
 *         @OA\Property(property="derniereModification", type="string", format="date-time"),
 *         @OA\Property(property="version", type="integer", example=1),
 *         @OA\Property(property="dateDebutBlocage", type="string", format="date-time", description="Date de début du blocage"),
 *         @OA\Property(property="dateFinBlocage", type="string", format="date-time", description="Date de fin du blocage"),
 *         @OA\Property(property="motifBlocage", type="string", description="Motif du blocage"),
 *         @OA\Property(property="dureeBlocage", type="integer", description="Durée du blocage"),
 *         @OA\Property(property="uniteBlocage", type="string", enum={"jours", "mois"}, description="Unité de durée du blocage")
 *     )
 * )
 */

/**
 * @OA\Get(
 *     path="/comptes",
 *     summary="Lister tous les comptes",
 *     description="Récupère une liste paginée de comptes avec possibilité de filtrage et tri",
 *     operationId="getComptes",
 *     tags={"Comptes"},
 *     security={{"bearerAuth":{}}},
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
 *         name="type",
 *         in="query",
 *         description="Filtrer par type de compte",
 *         required=false,
 *         @OA\Schema(type="string", enum={"epargne", "cheque"})
 *     ),
 *     @OA\Parameter(
 *         name="statut",
 *         in="query",
 *         description="Filtrer par statut",
 *         required=false,
 *         @OA\Schema(type="string", enum={"actif", "bloque", "ferme"})
 *     ),
 *     @OA\Parameter(
 *         name="search",
 *         in="query",
 *         description="Recherche par numéro de compte ou nom du titulaire",
 *         required=false,
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Parameter(
 *         name="sort",
 *         in="query",
 *         description="Champ de tri",
 *         required=false,
 *         @OA\Schema(type="string", enum={"dateCreation", "solde", "titulaire"}, default="dateCreation")
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
 *         description="Liste des comptes récupérée avec succès",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Compte")),
 *             @OA\Property(property="pagination", ref="#/components/schemas/PaginationMeta"),
 *             @OA\Property(property="links", type="object",
 *                 @OA\Property(property="self", type="string"),
 *                 @OA\Property(property="next", type="string"),
 *                 @OA\Property(property="first", type="string"),
 *                 @OA\Property(property="last", type="string")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Authentification requise",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=false),
 *             @OA\Property(property="message", type="string", example="Authentification requise")
 *         )
 *     )
 * )
 */

/**
 * @OA\Post(
 *     path="/comptes",
 *     summary="Créer un nouveau compte bancaire",
 *     description="Crée un nouveau compte bancaire avec les informations du client. Tous les champs sont obligatoires. Le téléphone est unique et respecte les critères d'un téléphone portable Sénégalais. Le solde à la création est supérieur ou égal à 10000. L'email est unique. Le numéro de téléphone est unique et respecte les règles d'un NCI Sénégalais.",
 *     operationId="createCompte",
 *     tags={"Comptes"},
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"type", "soldeInitial", "devise", "solde", "client"},
 *             @OA\Property(property="type", type="string", enum={"epargne", "cheque"}, description="Type de compte bancaire", example="cheque"),
 *             @OA\Property(property="soldeInitial", type="number", format="float", minimum=10000, description="Solde initial du compte", example=500000),
 *             @OA\Property(property="devise", type="string", maxLength=3, description="Devise du compte", default="FCFA", example="FCFA"),
 *             @OA\Property(property="solde", type="number", format="float", minimum=10000, description="Solde actuel du compte", example=10000),
 *             @OA\Property(property="client", type="object", description="Informations du client", required={"titulaire", "nci", "email", "telephone", "adresse"},
 *                 @OA\Property(property="id", type="string", format="uuid", nullable=true, description="ID du client existant (null pour nouveau client)", example=null),
 *                 @OA\Property(property="titulaire", type="string", minLength=2, maxLength=255, description="Nom complet du titulaire", example="Fallou ndiaye"),
 *                 @OA\Property(property="nci", type="string", description="Numéro de CNI sénégalais", example=""),
 *                 @OA\Property(property="email", type="string", format="email", description="Adresse e-mail unique", example="falloundiayey@example.com"),
 *                 @OA\Property(property="telephone", type="string", pattern="^(\\+2217[0-9]{8}|7[0-9]{8})$", description="Numéro de téléphone sénégalais", example="+221771234567"),
 *                 @OA\Property(property="adresse", type="string", minLength=5, maxLength=500, description="Adresse complète", example="Dakar, Sénégal")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Compte créé avec succès",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Compte créé avec succès"),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="id", type="string", example="660f9511-f30c-52e5-b827-557766551111"),
 *                 @OA\Property(property="numeroCompte", type="string", example="C00123460"),
 *                 @OA\Property(property="titulaire", type="string", example="fallou ndiaye"),
 *                 @OA\Property(property="type", type="string", enum={"epargne", "cheque"}, example="cheque"),
 *                 @OA\Property(property="solde", type="number", format="float", example=500000),
 *                 @OA\Property(property="devise", type="string", example="FCFA"),
 *                 @OA\Property(property="dateCreation", type="string", format="date-time", example="2025-10-19T10:30:00Z"),
 *                 @OA\Property(property="statut", type="string", enum={"actif", "bloque", "ferme"}, example="actif"),
 *                 @OA\Property(property="metadata", type="object",
 *                     @OA\Property(property="derniereModification", type="string", format="date-time", example="2025-10-19T10:30:00Z"),
 *                     @OA\Property(property="version", type="integer", example=1)
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Erreur de validation",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=false),
 *             @OA\Property(property="error", type="object",
 *                 @OA\Property(property="code", type="string", example="VALIDATION_ERROR"),
 *                 @OA\Property(property="message", type="string", example="Les données fournies sont invalides"),
 *                 @OA\Property(property="details", type="object",
 *                     @OA\Property(property="titulaire", type="array", @OA\Items(type="string"), example={"Le nom du titulaire est requis"}),
 *                     @OA\Property(property="soldeInitial", type="array", @OA\Items(type="string"), example={"Le solde initial doit être supérieur à 0"})
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Authentification requise",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=false),
 *             @OA\Property(property="message", type="string", example="Authentification requise")
 *         )
 *     )
 * )
 */

/**
 * @OA\Get(
 *     path="/comptes/{id}",
 *     summary="Détails d'un compte",
 *     description="Récupère les détails d'un compte spécifique",
 *     operationId="getCompte",
 *     tags={"Comptes"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID du compte",
 *         @OA\Schema(type="string", format="uuid")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Détails du compte récupérés",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", ref="#/components/schemas/Compte")
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Authentification requise",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=false),
 *             @OA\Property(property="message", type="string", example="Authentification requise")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Compte non trouvé",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=false),
 *             @OA\Property(property="message", type="string"),
 *             @OA\Property(property="errors", type="object",
 *                 @OA\Property(property="code", type="string", example="COMPTE_NOT_FOUND"),
 *                 @OA\Property(property="details", type="object")
 *             )
 *         )
 *     )
 * )
 */

/**
 * @OA\Patch(
 *     path="/comptes/{id}",
 *     summary="Mettre à jour un compte",
 *     description="Met à jour partiellement un compte existant",
 *     operationId="updateCompte",
 *     tags={"Comptes"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID du compte",
 *         @OA\Schema(type="string", format="uuid")
 *     ),
 *     @OA\RequestBody(
 *         @OA\JsonContent(
 *             @OA\Property(property="solde", type="number", format="float", description="Nouveau solde"),
 *             @OA\Property(property="statut", type="string", enum={"actif", "bloque", "ferme"}, description="Nouveau statut"),
 *             @OA\Property(property="metadonnees", type="object", description="Métadonnées additionnelles")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Compte mis à jour avec succès",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Compte mis à jour avec succès"),
 *             @OA\Property(property="data", ref="#/components/schemas/Compte")
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Authentification requise",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=false),
 *             @OA\Property(property="message", type="string", example="Authentification requise")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Compte non trouvé",
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
 *     path="/comptes/{id}",
 *     summary="Supprimer un compte",
 *     description="Supprime un compte existant",
 *     operationId="deleteCompte",
 *     tags={"Comptes"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID du compte",
 *         @OA\Schema(type="string", format="uuid")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Compte supprimé avec succès",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Compte supprimé avec succès")
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Authentification requise",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=false),
 *             @OA\Property(property="message", type="string", example="Authentification requise")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Compte non trouvé",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=false),
 *             @OA\Property(property="message", type="string"),
 *             @OA\Property(property="errors", type="object")
 *         )
 *     )
 * )
 */

/**
 * @OA\Post(
 *     path="/comptes/{id}/bloquer",
 *     summary="Bloquer un compte",
 *     description="Bloque un compte bancaire avec motif et durée",
 *     operationId="bloquerCompte",
 *     tags={"Comptes"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID du compte",
 *         @OA\Schema(type="string", format="uuid")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"motif", "duree", "unite", "dateDebut"},
 *             @OA\Property(property="motif", type="string", description="Motif du blocage", example="Suspicion de fraude"),
 *             @OA\Property(property="duree", type="integer", description="Durée du blocage", example=30),
 *             @OA\Property(property="unite", type="string", enum={"jours", "mois"}, description="Unité de durée", example="jours"),
 *             @OA\Property(property="dateDebut", type="string", format="date-time", description="Date de début du blocage", example="2025-10-28T13:00:00Z"),
 *             example={
 *                 "motif": "Suspicion de fraude",
 *                 "duree": 30,
 *                 "unite": "jours",
 *                 "dateDebut": "2025-10-28T13:00:00Z"
 *             }
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Compte bloqué avec succès",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Compte bloqué avec succès"),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="id", type="string", example="550e8400-e29b-41d4-a716-446655440000"),
 *                 @OA\Property(property="numeroCompte", type="string", example="C00123456"),
 *                 @OA\Property(property="titulaire", type="string", example="Amadou Diallo"),
 *                 @OA\Property(property="type", type="string", example="epargne"),
 *                 @OA\Property(property="solde", type="number", format="float", example=1250000),
 *                 @OA\Property(property="devise", type="string", example="FCFA"),
 *                 @OA\Property(property="dateCreation", type="string", format="date-time"),
 *                 @OA\Property(property="statut", type="string", example="bloque"),
 *                 @OA\Property(property="metadonnees", type="object",
 *                     @OA\Property(property="dateDebutBlocage", type="string", format="date-time", example="2025-10-28T13:00:00Z"),
 *                     @OA\Property(property="dateDeblocageAutomatique", type="string", format="date-time", description="Date de déblocage automatique si expiré"),
 *                     @OA\Property(property="motifDeblocageAutomatique", type="string", description="Motif du déblocage automatique"),
 *                     @OA\Property(property="dateFinBlocage", type="string", format="date-time", example="2025-11-27T13:00:00Z"),
 *                     @OA\Property(property="motifBlocage", type="string", example="Suspicion de fraude"),
 *                     @OA\Property(property="dureeBlocage", type="integer", example=30),
 *                     @OA\Property(property="uniteBlocage", type="string", example="jours"),
 *                     @OA\Property(property="derniereModification", type="string", format="date-time"),
 *                     @OA\Property(property="version", type="integer", example=2)
 *                 )
 *             )
 *         )
 *     )
 * )
 */

/**
 * @OA\Post(
 *     path="/comptes/{id}/debloquer",
 *     summary="Débloquer un compte",
 *     description="Débloque un compte bancaire bloqué",
 *     operationId="debloquerCompte",
 *     tags={"Comptes"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID du compte",
 *         @OA\Schema(type="string", format="uuid")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"motif"},
 *             @OA\Property(property="motif", type="string", description="Motif du déblocage")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Compte débloqué avec succès",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Compte débloqué avec succès"),
 *             @OA\Property(property="data", ref="#/components/schemas/Compte")
 *         )
 *     )
 * )
 */