<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use App\Traits\ApiResponse;
use App\Constants\Messages;
use App\Constants\StatusCodes;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

// Import des annotations Swagger depuis le fichier séparé
require_once base_path('docs/auth_swagger.php');
class AuthController extends Controller
{
    use ApiResponse;

    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function login(Request $request): JsonResponse
    {
        try {
            $result = $this->authService->login($request->only(['login', 'password']));

            return $this->successResponse($result, Messages::SUCCESS_LOGIN);
        } catch (\Exception $e) {
            Log::error('Login error: ' . $e->getMessage());
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: StatusCodes::UNAUTHORIZED);
        }
    }

    public function register(Request $request): JsonResponse
    {
        try {
            $result = $this->authService->register($request->all());

            return $this->successResponse($result, 'Inscription réussie', StatusCodes::CREATED);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: StatusCodes::BAD_REQUEST);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return $this->successResponse(null, Messages::SUCCESS_LOGOUT);
    }
}
