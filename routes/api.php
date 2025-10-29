<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\CompteController;
use App\Http\Controllers\Api\V1\UserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// API Version 1
Route::prefix('v1')->group(function () {
    // Routes d'authentification Passport
    Route::post('login', [App\Http\Controllers\Api\V1\AuthController::class, 'login']);
    Route::post('register', [App\Http\Controllers\Api\V1\AuthController::class, 'register']);
    Route::middleware('auth:api')->post('logout', [App\Http\Controllers\Api\V1\AuthController::class, 'logout']);
    Route::middleware('auth:api')->post('refresh', [App\Http\Controllers\Api\V1\AuthController::class, 'refresh']);

    // Routes protégées par authentification
    Route::middleware(['auth:api', 'api.rating'])->group(function () {
        // Routes des comptes bancaires
        Route::apiResource('comptes', CompteController::class);
        Route::post('comptes/{compte}/bloquer', [CompteController::class, 'bloquer']);
        Route::post('comptes/{compte}/debloquer', [CompteController::class, 'debloquer']);
        Route::post('comptes/test-email', [CompteController::class, 'testEmail']);

        // Routes des utilisateurs - COMMENTÉES TEMPORAIREMENT
        // Route::apiResource('users', UserController::class);
    });
});

// Route par défaut de Laravel (peut être supprimée si non nécessaire)
// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });
