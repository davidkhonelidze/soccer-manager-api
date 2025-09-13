<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\Interfaces\UserServiceInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

class AuthController extends Controller
{
    public function __construct(private UserServiceInterface $userService)
    {

    }

    public function register(RegisterRequest $request)
    {
        try {

            $user = $this->userService->register($request->validated());
            return new UserResource($user);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred',
                'errors' => []
            ], 500);

        }
    }
}
