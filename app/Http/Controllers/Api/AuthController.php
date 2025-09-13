<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\Interfaces\UserServiceInterface;
use App\Traits\ApiResponse;
use Illuminate\Validation\ValidationException;
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

    public function login(LoginRequest $request)
    {
        try {
            $result = $this->userService->login(
                $request->email,
                $request->password
            );

            $result['user'] = new UserResource($result['user']);

            return $this->successResponse([
                'data' => $result,
            ], 'messages.login.success');

        } catch (ValidationException $e) {
            return $this->errorResponse('messages.login.invalid_credentials', '', 422);
        } catch (\Exception $e) {
            return $this->errorResponse('messages.general.error');
        }
    }
}
