<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\DashboardService;

class DashboardController extends Controller
{
    protected $service;

    public function __construct(DashboardService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/dashboard",
     *     tags={"Dashboard"},
     *     summary="Get global dashboard data (Admin only)",
     *     description="Retrieve global dashboard statistics including total deposits, withdrawals, transaction count, accounts, and recent data. Requires admin privileges.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Global dashboard data retrieved successfully",
     *         @OA\JsonContent(ref="#/components/schemas/DashboardGlobalResponse")
     *     ),
     *     @OA\Response(response=403, description="Unauthorized - Admin access required"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function global(Request $request)
    {
        $request->user();
        // only admin
        if (! $request->user() || ! $request->user()->admin) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($this->service->globalDashboard());
    }

    /**
     * @OA\Get(
     *     path="/api/v1/dashboard/me",
     *     tags={"Dashboard"},
     *     summary="Get personal dashboard data",
     *     description="Retrieve personal dashboard data for the authenticated user including their transaction totals, balance, recent transactions, and accounts.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Personal dashboard data retrieved successfully",
     *         @OA\JsonContent(ref="#/components/schemas/DashboardPersonalResponse")
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function me(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return response()->json($this->service->personalDashboard($user));
    }
}
