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

    /**
     * @OA\Post(
     *     path="/api/register",
     *     summary="User Registration",
     *     description="Register a new user account",
     *     operationId="registerUser",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="User registration data",
     *         @OA\JsonContent(
     *             required={"name", "email", "password", "password_confirmation"},
     *             @OA\Property(
     *                 property="name",
     *                 type="string",
     *                 minLength=2,
     *                 maxLength=255,
     *                 description="User's full name",
     *                 example="John Doe"
     *             ),
     *             @OA\Property(
     *                 property="email",
     *                 type="string",
     *                 format="email",
     *                 maxLength=255,
     *                 description="User's email address (must be unique)",
     *                 example="john.doe@example.com"
     *             ),
     *             @OA\Property(
     *                 property="password",
     *                 type="string",
     *                 minLength=8,
     *                 format="password",
     *                 description="Password (min 8 chars, must contain: uppercase, lowercase, numbers, symbols)",
     *                 example="MyP@ssw0rd123"
     *             ),
     *             @OA\Property(
     *                 property="password_confirmation",
     *                 type="string",
     *                 format="password",
     *                 description="Password confirmation (must match password)",
     *                 example="MyP@ssw0rd123"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User registered successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User registered successfully"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="name",
     *                     type="array",
     *                     @OA\Items(type="string"),
     *                     example={"The name field is required.", "The name must be at least 2 characters."}
     *                 ),
     *                 @OA\Property(
     *                     property="email",
     *                     type="array",
     *                     @OA\Items(type="string"),
     *                     example={"The email field is required.", "The email must be a valid email address.", "The email has already been taken."}
     *                 ),
     *                 @OA\Property(
     *                     property="password",
     *                     type="array",
     *                     @OA\Items(type="string"),
     *                     example={"The password field is required.", "The password confirmation does not match.", "The password must contain at least one uppercase and one lowercase letter."}
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Internal server error")
     *         )
     *     )
     * )
     */
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
