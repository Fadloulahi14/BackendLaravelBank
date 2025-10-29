<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use App\Traits\ApiResponse;
use App\Exceptions\UserNotFoundException;
use App\Constants\Messages;
use App\Constants\StatusCodes;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

// Import des annotations Swagger depuis le fichier séparé
require_once base_path('docs/user_swagger.php');

class UserController extends Controller
{
    use ApiResponse;

    protected UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function index(Request $request): JsonResponse
    {
        $filters = [];
        if ($request->has('type') && in_array($request->type, ['client', 'admin'])) {
            $filters['type'] = $request->type;
        }
        if ($request->has('search')) {
            $filters['search'] = $request->search;
        }
        if ($request->has('sort')) {
            $filters['sort'] = $request->sort;
        }
        if ($request->has('order')) {
            $filters['order'] = $request->order;
        }

        $users = $this->userService->listUsers($filters, $request->get('per_page', 10));

        return $this->paginatedResponse(
            UserResource::collection($users),
            $users->currentPage(),
            $users->lastPage(),
            $users->total(),
            $users->perPage()
        );
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        try {
            $user = $this->userService->createUser($request->validated());

            return $this->successResponse(
                new UserResource($user),
                Messages::SUCCESS_CREATED,
                StatusCodes::CREATED
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: StatusCodes::BAD_REQUEST);
        }
    }

    public function show(string $id): JsonResponse
    {
        try {
            $user = $this->userService->findUserById($id);
            return $this->successResponse(new UserResource($user));
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: StatusCodes::NOT_FOUND);
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $user = User::find($id);
            if (!$user) {
                throw new UserNotFoundException($id);
            }

            $updatedUser = $this->userService->updateUser($user, $request->only(['nom', 'nci', 'email', 'telephone', 'adresse']));

            return $this->successResponse(
                new UserResource($updatedUser),
                Messages::SUCCESS_UPDATED
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: StatusCodes::BAD_REQUEST);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $user = User::find($id);
            if (!$user) {
                throw new UserNotFoundException($id);
            }

            $this->userService->deleteUser($user);

            return $this->successResponse(null, Messages::SUCCESS_DELETED);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: StatusCodes::BAD_REQUEST);
        }
    }
}
