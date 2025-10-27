<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\CompteCreeEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCompteRequest;
use App\Http\Requests\UpdateCompteRequest;
use App\Http\Resources\CompteResource;
use App\Models\Compte;
use App\Models\User;
use App\Models\Transaction;
use App\Traits\ApiResponse;
use App\Exceptions\CompteNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * @OA\Info(
 *     title="API de Gestion des Clients & Comptes",
 *     version="1.0.0",
 *     description="API RESTful pour la gestion des clients et de leurs comptes bancaires"
 * )
 *
 * @OA\Server(
 *     url="http://localhost:8000/api/v1",
 *     description="Serveur de développement"
 * )
 *
 * @OA\Server(
 *     url="https://backendlaravelbank.onrender.com/api/v1",
 *     description="Serveur de production"
 * )
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
 *         @OA\Property(property="version", type="integer", example=1)
 *     )
 * )
 */
class CompteController extends Controller
{
    use ApiResponse;

    /**
     * Générer un login unique basé sur le nom du titulaire
     */
    private function generateUniqueLogin(string $nom): string
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
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Vérifier les autorisations
        if (!$user) {
            return $this->errorResponse('Authentification requise', 401);
        }

        $query = Compte::with('user')->nonSupprime();

        // Autorisation basée sur le rôle
        if ($user->role === 'client') {
            // Client ne voit que ses propres comptes
            $query->utilisateur($user->id);
        }
        // Admin voit tous les comptes (pas de restriction supplémentaire)

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
        $comptes = $query->paginate($limit);

        $links = [
            'self' => $request->url() . '?' . $request->getQueryString(),
            'first' => $request->url() . '?page=1&' . $request->getQueryString(),
            'last' => $request->url() . '?page=' . $comptes->lastPage() . '&' . $request->getQueryString(),
        ];

        if ($comptes->hasMorePages()) {
            $links['next'] = $request->url() . '?page=' . ($comptes->currentPage() + 1) . '&' . $request->getQueryString();
        }

        return $this->paginatedResponse(
            CompteResource::collection($comptes->items()),
            $comptes->currentPage(),
            $comptes->lastPage(),
            $comptes->total(),
            $comptes->perPage(),
            $links
        );
    }

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
    public function store(StoreCompteRequest $request): JsonResponse
    {
        return DB::transaction(function () use ($request) {
            $user = null;
            $motDePasseGenere = null;
            $codeActivation = null;

            // Vérifier si c'est un client existant ou nouveau
            if (!empty($request->input('client.id'))) {
                // Client existant
                $user = User::find($request->input('client.id'));
                if (!$user) {
                    return $this->errorResponse('Client non trouvé', 404);
                }
            } else {
                // Nouveau client - créer l'utilisateur et le profil client
                $motDePasseGenere = Str::random(12);
                $codeActivation = Str::random(6);

                $user = User::create([
                    'id' => (string) Str::uuid(),
                    'login' => $this->generateUniqueLogin($request->input('client.titulaire')),
                    'password' => bcrypt($motDePasseGenere),
                    'role' => 'client',
                ]);

                // Créer le profil client
                $user->client()->create([
                    'id' => (string) Str::uuid(),
                    'nom' => $request->input('client.titulaire'),
                    'nci' => $request->input('client.nci'),
                    'email' => $request->input('client.email'),
                    'telephone' => $request->input('client.telephone'),
                    'adresse' => $request->input('client.adresse'),
                ]);
            }

            // Créer le compte
            $compte = Compte::create([
                'id' => (string) Str::uuid(),
                'numero_compte' => Compte::generateNumeroCompte(),
                'user_id' => $user->id,
                'type' => $request->input('type'),
                'solde' => $request->input('soldeInitial', 0),
                'devise' => $request->input('devise', 'FCFA'),
                'statut' => 'actif',
                'metadonnees' => [
                    'derniereModification' => now(),
                    'version' => 1,
                    'codeActivation' => $codeActivation,
                ],
            ]);

            // Créer la transaction initiale si solde > 0
            if ($request->input('soldeInitial', 0) > 0) {
                Transaction::create([
                    'id' => (string) Str::uuid(),
                    'compte_id' => $compte->id,
                    'type' => 'depot',
                    'montant' => $request->input('soldeInitial'),
                    'devise' => $request->input('devise', 'FCFA'),
                    'description' => 'Solde initial lors de la création du compte',
                    'statut' => 'validee',
                    'date_transaction' => now(),
                ]);
            }

            // Déclencher l'événement pour les notifications
            if ($motDePasseGenere && $codeActivation) {
                event(new CompteCreeEvent($compte, $user, $motDePasseGenere, $codeActivation));
            }

            return $this->successResponse(
                new CompteResource($compte),
                'Compte créé avec succès',
                201
            );
        });
    }

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
    public function show(string $id): JsonResponse
    {
        $compte = Compte::with('user')->find($id);

        if (!$compte) {
            throw new CompteNotFoundException($id);
        }

        return $this->successResponse(new CompteResource($compte));
    }

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
    public function update(UpdateCompteRequest $request, string $id): JsonResponse
    {
        $compte = Compte::find($id);

        if (!$compte) {
            throw new CompteNotFoundException($id);
        }

        $validated = $request->validated();

        // Mettre à jour les informations du compte
        $compteData = array_intersect_key($validated, array_flip(['titulaire', 'type', 'solde', 'devise', 'statut']));
        if (!empty($compteData)) {
            $compte->update($compteData);
            $compte->metadonnees = array_merge($compte->metadonnees ?? [], [
                'derniereModification' => now(),
                'version' => ($compte->metadonnees['version'] ?? 1) + 1
            ]);
            $compte->save();
        }

        // Mettre à jour les informations client si fournies
        if ($request->has('informationsClient')) {
            $clientData = $request->input('informationsClient');
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

        return $this->successResponse(
            new CompteResource($compte->fresh()),
            'Compte mis à jour avec succès'
        );
    }

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
    public function destroy(string $id): JsonResponse
    {
        $compte = Compte::find($id);

        if (!$compte) {
            throw new CompteNotFoundException($id);
        }

        // Soft delete
        $compte->delete();

        return $this->successResponse([
            'id' => $compte->id,
            'numeroCompte' => $compte->numero_compte,
            'statut' => 'ferme',
            'dateFermeture' => now()->toISOString()
        ], 'Compte supprimé avec succès');
    }

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
     *             required={"motif", "duree", "unite"},
     *             @OA\Property(property="motif", type="string", description="Motif du blocage"),
     *             @OA\Property(property="duree", type="integer", description="Durée du blocage"),
     *             @OA\Property(property="unite", type="string", enum={"jours", "mois"}, description="Unité de durée")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte bloqué avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte bloqué avec succès"),
     *             @OA\Property(property="data", ref="#/components/schemas/Compte")
     *         )
     *     )
     * )
     */
    public function bloquer(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'motif' => 'required|string|max:255',
            'duree' => 'required|integer|min:1',
            'unite' => 'required|in:jours,mois'
        ]);

        $compte = Compte::find($id);

        if (!$compte) {
            throw new CompteNotFoundException($id);
        }

        // Vérifier que le compte est actif et de type épargne
        if ($compte->statut !== 'actif') {
            return $this->errorResponse('Le compte doit être actif pour être bloqué', 400);
        }

        if ($compte->type !== 'epargne') {
            return $this->errorResponse('Seuls les comptes épargne peuvent être bloqués', 400);
        }

        // Calculer la date de fin de blocage
        $dateDebutBlocage = now();
        $dateFinBlocage = $request->unite === 'jours'
            ? $dateDebutBlocage->copy()->addDays($request->duree)
            : $dateDebutBlocage->copy()->addMonths($request->duree);

        // Mettre à jour le compte
        $compte->update([
            'statut' => 'bloque',
            'metadonnees' => array_merge($compte->metadonnees ?? [], [
                'motifBlocage' => $request->motif,
                'dateBlocage' => $dateDebutBlocage,
                'dateDeblocagePrevue' => $dateFinBlocage,
                'dureeBlocage' => $request->duree,
                'uniteBlocage' => $request->unite,
                'derniereModification' => now(),
                'version' => ($compte->metadonnees['version'] ?? 1) + 1
            ])
        ]);

        return $this->successResponse(new CompteResource($compte), 'Compte bloqué avec succès');
    }

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
    public function debloquer(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'motif' => 'required|string|max:255'
        ]);

        $compte = Compte::find($id);

        if (!$compte) {
            throw new CompteNotFoundException($id);
        }

        // Vérifier que le compte est bloqué
        if ($compte->statut !== 'bloque') {
            return $this->errorResponse('Le compte n\'est pas bloqué', 400);
        }

        // Mettre à jour le compte
        $compte->update([
            'statut' => 'actif',
            'metadonnees' => array_merge($compte->metadonnees ?? [], [
                'motifDeblocage' => $request->motif,
                'dateDeblocage' => now(),
                'derniereModification' => now(),
                'version' => ($compte->metadonnees['version'] ?? 1) + 1
            ])
        ]);

        return $this->successResponse(new CompteResource($compte), 'Compte débloqué avec succès');
    }
}
