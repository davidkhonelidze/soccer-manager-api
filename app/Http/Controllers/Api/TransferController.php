<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PurchasePlayerRequest;
use App\Http\Resources\PlayerResource;
use App\Http\Resources\TeamResource;
use App\Services\Interfaces\TeamServiceInterface;
use App\Services\Interfaces\TransferServiceInterface;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransferController extends Controller
{
    use ApiResponse;

    public function __construct(
        private TransferServiceInterface $transferService,
        private TeamServiceInterface $teamService
    ) {}

    /**
     * @OA\Post(
     *     path="/api/transfer/purchase/{player}",
     *     summary="Purchase Player",
     *     description="Purchase a player from the transfer market",
     *     operationId="purchasePlayer",
     *     tags={"Transfers"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(ref="#/components/parameters/Accept-Language"),
     *     @OA\Parameter(
     *         name="player",
     *         in="path",
     *         description="ID of the player to purchase",
     *         required=true,
     *
     *         @OA\Schema(
     *             type="integer",
     *             example=1
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Player purchased successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Player purchased successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="player",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="first_name", type="string", example="John"),
     *                     @OA\Property(property="last_name", type="string", example="Doe"),
     *                     @OA\Property(property="position", type="string", example="midfielder"),
     *                     @OA\Property(property="team_id", type="integer", example=2)
     *                 ),
     *                 @OA\Property(
     *                     property="buying_team",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=2),
     *                     @OA\Property(property="name", type="string", example="Team Beta"),
     *                     @OA\Property(property="balance", type="number", format="float", example=4000000.00)
     *                 ),
     *                 @OA\Property(
     *                     property="selling_team",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Team Alpha"),
     *                     @OA\Property(property="balance", type="number", format="float", example=6000000.00)
     *                 ),
     *                 @OA\Property(property="transfer_fee", type="number", format="float", example=1000000.00),
     *                 @OA\Property(property="transfer_uuid", type="string", example="550e8400-e29b-41d4-a716-446655440000")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="Bad request - Transfer validation failed",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Insufficient funds for transfer."),
     *             @OA\Property(property="errors", type="object", example={})
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Player not found or not available for transfer",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Player is not available for transfer."),
     *             @OA\Property(property="errors", type="object", example={})
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
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
    public function purchasePlayer(PurchasePlayerRequest $request, int $player): JsonResponse
    {
        try {
            $user = $request->user();

            if (! $user || ! $user->team_id) {
                return $this->errorResponse('messages.transfer.team_not_found', [], 400);
            }

            $team = $this->teamService->find($user->team_id);
            if (! $team) {
                return $this->errorResponse('messages.transfer.team_not_found', [], 400);
            }

            $result = $this->transferService->purchasePlayer($player, $team->uuid);

            return $this->successResponse([
                'player' => new PlayerResource($result['player']),
                'buying_team' => new TeamResource($result['buying_team']),
                'selling_team' => new TeamResource($result['selling_team']),
                'transfer_fee' => $result['transfer_fee'],
                'transfer_uuid' => $result['transfer_uuid'],
            ], 'messages.transfer.purchase_successful');

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), [], 400);
        }
    }
}
