<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\CompteService;
use App\Services\TransactionService;
use App\Models\Compte;
use App\Models\Transaction;

class CompteController extends Controller
{
    protected $compteService;
    protected $transactionService;

    public function __construct(CompteService $compteService, TransactionService $transactionService)
    {
        $this->compteService = $compteService;
        $this->transactionService = $transactionService;
    }

    // Existing methods from the original CompteController should be included here
    // For brevity, I'm only showing the new methods. In a real implementation,
    // you would need to merge the existing methods from app/Http/Controllers/CompteController.php

    // ... existing methods ...

    /**
     * @OA\Get(
     *     path="/api/v1/comptes/{compteId}/transactions",
     *     tags={"Comptes"},
     *     summary="Lister les transactions d'un compte spécifique",
     *     description="Récupère toutes les transactions d'un compte spécifique appartenant à l'utilisateur connecté",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="compteId",
     *         in="path",
     *         required=true,
     *         description="ID du compte (UUID ou numero_compte)",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer", default=1), description="Numéro de page"),
     *     @OA\Parameter(name="limit", in="query", @OA\Schema(type="integer", default=20), description="Nombre d'éléments par page"),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des transactions récupérée",
     *         @OA\JsonContent(ref="#/components/schemas/TransactionsResponse")
     *     ),
     *     @OA\Response(response=403, description="Accès refusé"),
     *     @OA\Response(response=404, description="Compte introuvable")
     * )
     */
    public function getTransactions(Request $request, $compteId)
    {
        $user = $request->user();

        // Find the account
        $compte = null;
        if (preg_match('/^[0-9a-fA-F\-]{36}$/', (string) $compteId)) {
            $compte = Compte::find($compteId);
        }
        if (!$compte) {
            $compte = Compte::where('numero_compte', $compteId)->first();
        }

        if (!$compte) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'COMPTE_NOT_FOUND', 'message' => 'Compte introuvable']
            ], 404);
        }

        // Check if user owns this account
        if (!$user->admin && $compte->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'ACCESS_DENIED', 'message' => 'Accès refusé']
            ], 403);
        }

        // Get transactions for this account
        $query = Transaction::where('compte_id', $compte->id)
            ->with('compte', 'agent')
            ->latest();

        $perPage = $request->get('limit', 20);
        $pag = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $pag->items(),
            'pagination' => [
                'currentPage' => $pag->currentPage(),
                'totalPages' => $pag->lastPage(),
                'totalItems' => $pag->total(),
                'itemsPerPage' => $pag->perPage(),
                'hasNext' => $pag->hasMorePages(),
                'hasPrevious' => $pag->currentPage() > 1,
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/comptes/{compteId}/statistics",
     *     tags={"Comptes"},
     *     summary="Statistiques d'un compte spécifique",
     *     description="Récupère les statistiques détaillées d'un compte spécifique (dépôts totaux, retraits totaux, nombre de transactions, dernière transaction)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="compteId",
     *         in="path",
     *         required=true,
     *         description="ID du compte (UUID ou numero_compte)",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Statistiques du compte récupérées",
     *         @OA\JsonContent(ref="#/components/schemas/AccountStatisticsResponse")
     *     ),
     *     @OA\Response(response=403, description="Accès refusé"),
     *     @OA\Response(response=404, description="Compte introuvable")
     * )
     */
    public function getStatistics(Request $request, $compteId)
    {
        $user = $request->user();

        // Find the account
        $compte = null;
        if (preg_match('/^[0-9a-fA-F\-]{36}$/', (string) $compteId)) {
            $compte = Compte::find($compteId);
        }
        if (!$compte) {
            $compte = Compte::where('numero_compte', $compteId)->first();
        }

        if (!$compte) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'COMPTE_NOT_FOUND', 'message' => 'Compte introuvable']
            ], 404);
        }

        // Check if user owns this account
        if (!$user->admin && $compte->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'ACCESS_DENIED', 'message' => 'Accès refusé']
            ], 403);
        }

        // Calculate statistics
        $totalDepot = $compte->transactions()->where('type', 'deposit')->sum('montant');
        $totalRetrait = $compte->transactions()->where('type', 'withdrawal')->sum('montant');
        $count = $compte->transactions()->count();
        $lastTransaction = $compte->transactions()->with('agent')->latest()->first();

        return response()->json([
            'success' => true,
            'data' => [
                'compteId' => $compte->id,
                'numeroCompte' => $compte->numero_compte,
                'totalDepot' => $totalDepot,
                'totalRetrait' => $totalRetrait,
                'count' => $count,
                'lastTransaction' => $lastTransaction
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/comptes/{compteId}/dashboard",
     *     tags={"Comptes"},
     *     summary="Dashboard d'un compte spécifique",
     *     description="Récupère le dashboard complet d'un compte spécifique avec statistiques et dernières transactions",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="compteId",
     *         in="path",
     *         required=true,
     *         description="ID du compte (UUID ou numero_compte)",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Dashboard du compte récupéré",
     *         @OA\JsonContent(ref="#/components/schemas/AccountDashboardResponse")
     *     ),
     *     @OA\Response(response=403, description="Accès refusé"),
     *     @OA\Response(response=404, description="Compte introuvable")
     * )
     */
    public function getDashboard(Request $request, $compteId)
    {
        $user = $request->user();

        // Find the account
        $compte = null;
        if (preg_match('/^[0-9a-fA-F\-]{36}$/', (string) $compteId)) {
            $compte = Compte::find($compteId);
        }
        if (!$compte) {
            $compte = Compte::where('numero_compte', $compteId)->first();
        }

        if (!$compte) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'COMPTE_NOT_FOUND', 'message' => 'Compte introuvable']
            ], 404);
        }

        // Check if user owns this account
        if (!$user->admin && $compte->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'ACCESS_DENIED', 'message' => 'Accès refusé']
            ], 403);
        }

        // Calculate dashboard data
        $totalDepot = $compte->transactions()->where('type', 'deposit')->sum('montant');
        $totalRetrait = $compte->transactions()->where('type', 'withdrawal')->sum('montant');
        $balance = $totalDepot - $totalRetrait;
        $count = $compte->transactions()->count();
        $latest10 = $compte->transactions()->with('agent')->latest()->take(10)->get();

        return response()->json([
            'success' => true,
            'data' => [
                'compte' => $compte,
                'totalDepot' => $totalDepot,
                'totalRetrait' => $totalRetrait,
                'balance' => $balance,
                'count' => $count,
                'latest10' => $latest10
            ]
        ]);
    }

    // ... existing methods ...
}