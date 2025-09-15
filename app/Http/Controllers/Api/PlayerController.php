<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePlayerRequest;
use App\Http\Resources\PlayerResource;
use App\Services\Interfaces\PlayerAuthorizationServiceInterface;
use App\Services\Interfaces\PlayerServiceInterface;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class PlayerController extends Controller
{
    use ApiResponse;

    public function __construct(
        private PlayerServiceInterface $playerService,
        private PlayerAuthorizationServiceInterface $authorizationService
    ) {}

    /**
     * @OA\Put(
     *     path="/api/players/{player}",
     *     summary="Update Player Information",
     *     description="Update player information (first name, last name, country). Only team owner can update their players.",
     *     operationId="updatePlayer",
     *     tags={"Players"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(ref="#/components/parameters/Accept-Language"),
     *     @OA\Parameter(
     *         name="player",
     *         in="path",
     *         description="ID of the player to update",
     *         required=true,
     *
     *         @OA\Schema(
     *             type="integer",
     *             example=1
     *         )
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         description="Player update data",
     *
     *         @OA\JsonContent(
     *             required={"first_name", "last_name", "country_id"},
     *
     *             @OA\Property(
     *                 property="first_name",
     *                 type="string",
     *                 minLength=2,
     *                 maxLength=30,
     *                 description="Player's first name",
     *                 example="John"
     *             ),
     *             @OA\Property(
     *                 property="last_name",
     *                 type="string",
     *                 minLength=2,
     *                 maxLength=30,
     *                 description="Player's last name",
     *                 example="Doe"
     *             ),
     *             @OA\Property(
     *                 property="country_id",
     *                 type="integer",
     *                 description="ID of the player's country",
     *                 example=1
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Player updated successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Player updated successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="first_name", type="string", example="John"),
     *                 @OA\Property(property="last_name", type="string", example="Doe"),
     *                 @OA\Property(property="date_of_birth", type="string", format="date", example="2000-01-01"),
     *                 @OA\Property(property="position", type="string", example="attacker"),
     *                 @OA\Property(property="age", type="integer", example=24),
     *                 @OA\Property(property="value", type="number", format="float", example=1000000.50),
     *                 @OA\Property(property="team_id", type="integer", example=1),
     *                 @OA\Property(property="country_id", type="integer", example=1),
     *                 @OA\Property(
     *                     property="country",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="United States"),
     *                     @OA\Property(property="code", type="string", example="US")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="Bad request - Player not found or not owned by user",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Player not found or you don't have permission to update this player"),
     *             @OA\Property(property="errors", type="array", @OA\Items(type="string"))
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
     *         response=403,
     *         description="Forbidden - User doesn't have a team or player doesn't belong to user's team",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="You can only update players from your own team"),
     *             @OA\Property(property="errors", type="array", @OA\Items(type="string"))
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="The given data was invalid"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="first_name",
     *                     type="array",
     *
     *                     @OA\Items(type="string", example="The first name field is required.")
     *                 ),
     *
     *                 @OA\Property(
     *                     property="last_name",
     *                     type="array",
     *
     *                     @OA\Items(type="string", example="The last name field is required.")
     *                 ),
     *
     *                 @OA\Property(
     *                     property="country_id",
     *                     type="array",
     *
     *                     @OA\Items(type="string", example="The country id field is required.")
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
     *             @OA\Property(property="message", type="string", example="Internal server error"),
     *             @OA\Property(property="errors", type="array", @OA\Items(type="string"))
     *         )
     *     )
     * )
     */
    public function update(UpdatePlayerRequest $request, $player): JsonResponse
    {
        try {
            if (! is_numeric($player) || (int) $player <= 0) {
                return $this->errorResponse('messages.player.not_found', [], 404);
            }

            $playerId = (int) $player;
            $user = $request->user();

            if (! $user || ! $user->team_id) {
                return $this->errorResponse('messages.player.no_team', [], 403);
            }

            if (! $this->authorizationService->canUserUpdatePlayer($user->id, $playerId)) {
                return $this->errorResponse('messages.player.not_owned', [], 403);
            }

            $validatedData = $request->validated();

            $updatedPlayer = $this->playerService->updatePlayer(
                $playerId,
                $user->team_id,
                $validatedData
            );

            return $this->successResponse(
                new PlayerResource($updatedPlayer),
                'messages.player.updated_successfully'
            );

        } catch (\Exception $e) {
            \Log::error('Player update failed: '.$e->getMessage(), [
                'player_id' => $playerId ?? $player,
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse($e->getMessage(), [], 400);
        }
    }
}
