<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCompteRequest;
use App\Http\Requests\UpdateCompteRequest;
use App\Http\Resources\CompteResource;
use App\Models\Compte;
use App\Models\User;
use App\Services\CompteService;
use App\Services\EmailService;
use App\Traits\ApiResponse;
use App\Exceptions\CompteNotFoundException;
use App\Constants\Messages;
use App\Constants\StatusCodes;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

// Import des annotations Swagger depuis le fichier séparé
require_once base_path('docs/compte_swagger.php');

/**
 * @OA\Tag(
 *     name="Comptes",
 *     description="Gestion des comptes bancaires"
 * )
 */
class CompteController extends Controller
{
    use ApiResponse;

    protected CompteService $compteService;
    protected EmailService $emailService;

    public function __construct(CompteService $compteService, EmailService $emailService)
    {
        $this->compteService = $compteService;
        $this->emailService = $emailService;
    }

    /**
     * Lister tous les comptes
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return $this->errorResponse(Messages::ERROR_UNAUTHORIZED, StatusCodes::UNAUTHORIZED);
        }

        // Vérifier les scopes
        if (!$request->user()->tokenCan('client') && !$request->user()->tokenCan('admin')) {
            return $this->errorResponse('Scope insuffisant pour accéder aux comptes', StatusCodes::FORBIDDEN);
        }

        $comptes = $this->compteService->listComptes($request, $user);

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
     * Créer un nouveau compte bancaire
     */
    public function store(StoreCompteRequest $request): JsonResponse
    {
        // Vérifier les scopes pour la création de comptes
        if (!$request->user()->tokenCan('admin')) {
            return $this->errorResponse('Seul un administrateur peut créer des comptes', StatusCodes::FORBIDDEN);
        }

        try {
            $compte = $this->compteService->createCompte($request->validated());

            return $this->successResponse(
                new CompteResource($compte),
                Messages::COMPTE_CREATED,
                StatusCodes::CREATED
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: StatusCodes::BAD_REQUEST);
        }
    }
    /**
     * Détails d'un compte
     */
    public function show(string $id): JsonResponse
    {
        $compte = Compte::with('user')->withTrashed()->find($id);

        if (!$compte) {
            throw new CompteNotFoundException($id);
        }

        return $this->successResponse(new CompteResource($compte));
    }

    /**
     * Mettre à jour un compte
     */
    public function update(UpdateCompteRequest $request, string $id): JsonResponse
    {
        $compte = Compte::find($id);

        if (!$compte) {
            throw new CompteNotFoundException($id);
        }

        try {
            $compte = $this->compteService->updateCompte($compte, $request->validated());

            return $this->successResponse(
                new CompteResource($compte),
                Messages::COMPTE_UPDATED
            );
        } catch (\Exception $e) {
            Log::error('Erreur lors de la création du compte', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return $this->errorResponse($e->getMessage(), $e->getCode() ?: StatusCodes::BAD_REQUEST);
        }
    }

    /**
     * Tester l'envoi d'email
     */
    public function testEmail(): JsonResponse
    {
        try {
            $result = $this->emailService->testEmail('fadloulahi14@gmail.com');

            if ($result) {
                return $this->successResponse(
                    null,
                    'Email de test envoyé avec succès',
                    StatusCodes::OK
                );
            } else {
                return $this->errorResponse(
                    'Échec de l\'envoi de l\'email de test',
                    StatusCodes::INTERNAL_SERVER_ERROR
                );
            }

        } catch (\Exception $e) {
            Log::error('Erreur lors du test d\'email', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                'Erreur lors du test d\'email: ' . $e->getMessage(),
                StatusCodes::INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Supprimer un compte
     */
    public function destroy(string $id): JsonResponse
    {
        $compte = Compte::find($id);

        if (!$compte) {
            throw new CompteNotFoundException($id);
        }

        $result = $this->compteService->deleteCompte($compte);

        return $this->successResponse($result, Messages::COMPTE_DELETED);
    }

    /**
     * Bloquer un compte
     */
    public function bloquer(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'motif' => 'required|string|max:255',
            'duree' => 'required|integer|min:1',
            'unite' => 'required|in:jours,mois',
            'dateDebut' => 'required|date|after_or_equal:now'
        ]);

        $compte = Compte::find($id);

        if (!$compte) {
            throw new CompteNotFoundException($id);
        }

        if ($compte->statut !== 'actif') {
            return $this->errorResponse(Messages::COMPTE_ACTIVE_REQUIRED, StatusCodes::BAD_REQUEST);
        }

        if ($compte->type !== 'epargne') {
            return $this->errorResponse(Messages::COMPTE_EPARGNE_ONLY_BLOCK, StatusCodes::BAD_REQUEST);
        }

        if ($compte->type === 'cheque') {
            return $this->errorResponse(Messages::COMPTE_CHEQUE_CANNOT_BLOCK, StatusCodes::BAD_REQUEST);
        }

        $dateDebutBlocage = \Carbon\Carbon::parse($request->dateDebut);
        $dateFinBlocage = $request->unite === 'jours'
            ? $dateDebutBlocage->copy()->addDays($request->duree)
            : $dateDebutBlocage->copy()->addMonths($request->duree);

        $compte->update([
            'metadonnees' => array_merge($compte->metadonnees ?? [], [
                'motifBlocage' => $request->motif,
                'dateDebutBlocage' => $dateDebutBlocage,
                'dateFinBlocage' => $dateFinBlocage,
                'dureeBlocage' => $request->duree,
                'uniteBlocage' => $request->unite,
                'statutProgramme' => 'bloque', // Statut programmé
                'derniereModification' => now(),
                'version' => ($compte->metadonnees['version'] ?? 1) + 1
            ])
        ]);

        \App\Jobs\ArchiveExpiredBlockedAccounts::dispatch()->delay($dateDebutBlocage);

        return $this->successResponse(new CompteResource($compte), Messages::COMPTE_BLOCKED);
    }

    /**
     * Débloquer un compte
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

        try {
            $compte = $this->compteService->debloquerCompte($compte, $request->motif);

            return $this->successResponse(new CompteResource($compte), Messages::COMPTE_UNBLOCKED);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: StatusCodes::BAD_REQUEST);
        }
    }
}
