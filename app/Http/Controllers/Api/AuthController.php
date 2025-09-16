<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\Interfaces\UserServiceInterface;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(private UserServiceInterface $userService) {}

    /**
     * @OA\Post(
     *     path="/api/register",
     *     summary="User Registration",
     *     description="Register a new user account",
     *     operationId="registerUser",
     *     tags={"Authentication"},
     *
     *     @OA\Parameter(ref="#/components/parameters/Accept-Language"),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         description="User registration data",
     *
     *         @OA\JsonContent(
     *             required={"name", "email", "password", "password_confirmation"},
     *
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
     *
     *     @OA\Response(
     *         response=201,
     *         description="User registered successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="User registered successfully"),
     *             @OA\Property(
     *                  property="data",
     *                  type="object",
     *                  @OA\Property(
     *                      property="user",
     *                      type="object",
     *                      @OA\Property(property="id", type="integer", example=1),
     *                      @OA\Property(property="name", type="string", example="John Doe"),
     *                      @OA\Property(property="email", type="string", example="john.doe@example.com"),
     *                      @OA\Property(property="email_verified_at", type="string", format="date-time", nullable=true, example=null),
     *                      @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-15T10:30:00.000000Z"),
     *                      @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-15T10:30:00.000000Z")
     *                  )
     *              )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="name",
     *                     type="array",
     *
     *                     @OA\Items(type="string"),
     *                     example={"The name field is required.", "The name must be at least 2 characters."}
     *                 ),
     *
     *                 @OA\Property(
     *                     property="email",
     *                     type="array",
     *
     *                     @OA\Items(type="string"),
     *                     example={"The email field is required.", "The email must be a valid email address.", "The email has already been taken."}
     *                 ),
     *
     *                 @OA\Property(
     *                     property="password",
     *                     type="array",
     *
     *                     @OA\Items(type="string"),
     *                     example={"The password field is required.", "The password confirmation does not match.", "The password must contain at least one uppercase and one lowercase letter."}
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *
     *         @OA\JsonContent(
     *
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
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/api/login",
     *     summary="User Login",
     *     description="Login user with email and password",
     *     operationId="loginUser",
     *     tags={"Authentication"},
     *
     *     @OA\Parameter(ref="#/components/parameters/Accept-Language"),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         description="User login credentials",
     *
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *
     *             @OA\Property(
     *                 property="email",
     *                 type="string",
     *                 format="email",
     *                 description="User's email address",
     *                 example="email@email.com"
     *             ),
     *             @OA\Property(
     *                 property="password",
     *                 type="string",
     *                 minLength=6,
     *                 format="password",
     *                 description="User's password (minimum 6 characters)",
     *                 example="password123"
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Login successful",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="messages.login.success"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="data",
     *                     type="object",
     *                     @OA\Property(
     *                         property="user",
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="David"),
     *                         @OA\Property(property="email", type="string", example="email@email.com"),
     *                         @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-13T13:02:48.000000Z")
     *                     ),
     *                     @OA\Property(property="token", type="string", example="6|3OAgigLYuX8y8HfgizsxTWaj2JL4tQ1OzueaXaX7784f0797")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Invalid credentials",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Invalid credentials")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="email",
     *                     type="array",
     *
     *                     @OA\Items(type="string"),
     *                     example={"The email field is required.", "The email must be a valid email address."}
     *                 ),
     *
     *                 @OA\Property(
     *                     property="password",
     *                     type="array",
     *
     *                     @OA\Items(type="string"),
     *                     example={"The password field is required.", "The password must be at least 6 characters."}
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Internal server error")
     *         )
     *     )
     * )
     */
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
            return $this->errorResponse('messages.login.invalid_credentials', '', 401);
        } catch (\Exception $e) {
            return $this->errorResponse('messages.general.error');
        }
    }

    /**
     * @OA\Get(
     *     path="/api/me",
     *     summary="Get Current User Information",
     *     description="Get the authenticated user's information including team details",
     *     operationId="getCurrentUser",
     *     tags={"Authentication"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(ref="#/components/parameters/Accept-Language"),
     *
     *     @OA\Response(
     *         response=200,
     *         description="User information retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User information retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", example="john.doe@example.com"),
     *                 @OA\Property(property="team_id", type="integer", example=1),
     *                 @OA\Property(
     *                     property="team",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="uuid", type="string", example="123e4567-e89b-12d3-a456-426614174000"),
     *                     @OA\Property(property="name", type="string", example="Manchester United"),
     *                     @OA\Property(property="balance", type="number", format="float", example=5000000.00),
     *                     @OA\Property(property="value", type="number", format="float", example=15000000.00, description="Total value of all team players"),
     *                     @OA\Property(property="country_id", type="integer", example=1),
     *                     @OA\Property(
     *                         property="country",
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="England"),
     *                         @OA\Property(property="code", type="string", example="EN")
     *                     ),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-15T10:30:00.000000Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-15T10:30:00.000000Z")
     *                 ),
     *                 @OA\Property(property="email_verified_at", type="string", format="date-time", nullable=true, example=null),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-15T10:30:00.000000Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-15T10:30:00.000000Z")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Invalid or missing authentication token",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthenticated"),
     *             @OA\Property(property="errors", type="array", @OA\Items(type="string"))
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Internal server error"),
     *             @OA\Property(property="errors", type="array", @OA\Items(type="string"))
     *         )
     *     )
     * )
     */
    public function me(): JsonResponse
    {
        try {
            $user = auth()->user();

            if (! $user) {
                return $this->errorResponse('messages.auth.unauthenticated', [], 401);
            }

            $userWithTeam = $this->userService->getCurrentUserWithTeam($user->id);

            if (! $userWithTeam) {
                return $this->errorResponse('messages.user.not_found', [], 404);
            }

            return $this->successResponse(
                new UserResource($userWithTeam),
                'messages.user.info_retrieved_successfully'
            );

        } catch (\Exception $e) {
            \Log::error('Get user info failed: '.$e->getMessage(), [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('messages.general.error', [], 500);
        }
    }
}
