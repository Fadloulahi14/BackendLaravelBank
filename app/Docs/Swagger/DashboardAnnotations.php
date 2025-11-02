<?php

/**
 * @OA\Schema(
 *     schema="DashboardGlobalResponse",
 *     type="object",
 *     title="Dashboard Global Response",
 *     description="Response for global dashboard data (admin only)",
 *     @OA\Property(property="totalDepot", type="number", format="float", description="Total amount of all deposit transactions"),
 *     @OA\Property(property="totalRetrait", type="number", format="float", description="Total amount of all withdrawal transactions"),
 *     @OA\Property(property="count", type="integer", description="Total number of transactions"),
 *     @OA\Property(property="last", ref="#/components/schemas/Transaction", description="Last transaction"),
 *     @OA\Property(property="totalComptes", type="integer", description="Total number of accounts"),
 *     @OA\Property(property="soldeGlobal", type="number", format="float", description="Global balance (totalDepot - totalRetrait)"),
 *     @OA\Property(
 *         property="latest10",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/Transaction"),
 *         description="Last 10 transactions"
 *     ),
 *     @OA\Property(
 *         property="comptesToday",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/Compte"),
 *         description="Accounts created today"
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="DashboardPersonalResponse",
 *     type="object",
 *     title="Dashboard Personal Response",
 *     description="Response for personal dashboard data",
 *     @OA\Property(property="totalDepot", type="number", format="float", description="Total deposits for user's accounts"),
 *     @OA\Property(property="totalRetrait", type="number", format="float", description="Total withdrawals for user's accounts"),
 *     @OA\Property(property="count", type="integer", description="Number of transactions for user's accounts"),
 *     @OA\Property(property="balance", type="number", format="float", description="User's balance (totalDepot - totalRetrait)"),
 *     @OA\Property(
 *         property="latest10",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/Transaction"),
 *         description="Last 10 transactions for user's accounts"
 *     ),
 *     @OA\Property(
 *         property="comptes",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/Compte"),
 *         description="User's accounts"
 *     )
 * )
 */