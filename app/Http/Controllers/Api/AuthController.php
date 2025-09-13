<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\Interfaces\UserServiceInterface;
use App\Traits\ApiResponse;
use Symfony\Component\HttpFoundation\ParameterBag;

class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(private UserServiceInterface $userService)
    {

    }

    public function register(RegisterRequest $request)
    {
        try {

            $user = $this->userService->register($request->validated());

            return $this->successResponse([
                'user' => new UserResource($user),
            ], 'messages.registration.success', 201);

        } catch (\Exception $e) {
            return $this->errorResponse('messages.registration.failed');
        }
    }
}
