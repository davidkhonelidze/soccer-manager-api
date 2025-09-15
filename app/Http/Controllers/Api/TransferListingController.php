<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TransferListingRequest;
use App\Http\Resources\TransferListingResource;
use App\Services\Interfaces\TransferListingServiceInterface;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;

class TransferListingController extends Controller
{
    use ApiResponse;

    public function __construct(
        private TransferListingServiceInterface $transferListingService
    ) {}

    /**
     * @OA\Get(
     *     path="/api/transfer-listings",
     *     summary="Get Transfer Listings",
     *     description="Returns paginated list of transfer listings with player and team information",
     *     operationId="getTransferListings",
     *     tags={"Transfer Listings"},
     *
     *     @OA\Parameter(ref="#/components/parameters/Accept-Language"),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number for pagination",
     *         required=false,
     *
     *         @OA\Schema(
     *             type="integer",
     *             default=1,
     *             minimum=1
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *
     *                 @OA\Items(
     *
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="player_id", type="integer", example=1),
     *                     @OA\Property(property="selling_team_id", type="integer", example=1),
     *                     @OA\Property(property="asking_price", type="number", format="float", example=1000000.50),
     *                     @OA\Property(property="status", type="string", example="active"),
     *                     @OA\Property(property="unique_key", type="string", example="active"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-15T10:30:00.000000Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-15T10:30:00.000000Z")
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="links",
     *                 type="object",
     *                 @OA\Property(property="first", type="string", example="http://127.0.0.1/api/transfer-listings?page=1"),
     *                 @OA\Property(property="last", type="string", example="http://127.0.0.1/api/transfer-listings?page=1"),
     *                 @OA\Property(property="prev", type="string", nullable=true, example=null),
     *                 @OA\Property(property="next", type="string", nullable=true, example=null)
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="from", type="integer", example=1),
     *                 @OA\Property(property="last_page", type="integer", example=1),
     *                 @OA\Property(
     *                     property="links",
     *                     type="array",
     *
     *                     @OA\Items(
     *
     *                         @OA\Property(property="url", type="string", nullable=true),
     *                         @OA\Property(property="label", type="string"),
     *                         @OA\Property(property="page", type="integer", nullable=true),
     *                         @OA\Property(property="active", type="boolean")
     *                     )
     *                 ),
     *                 @OA\Property(property="path", type="string", example="http://127.0.0.1/api/transfer-listings"),
     *                 @OA\Property(property="per_page", type="integer", example=15),
     *                 @OA\Property(property="to", type="integer", example=3),
     *                 @OA\Property(property="total", type="integer", example=3)
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="Bad request"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error"
     *     )
     * )
     */
    public function index()
    {
        $result = $this->transferListingService->getPaginatedTransferListings();

        return TransferListingResource::collection($result);
    }

    /**
     * @OA\Post(
     *     path="/api/transfer-listings",
     *     summary="List Player for Transfer",
     *     description="Add a player to the transfer market",
     *     operationId="createTransferListing",
     *     tags={"Transfer Listings"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(ref="#/components/parameters/Accept-Language"),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         description="Transfer listing data",
     *
     *         @OA\JsonContent(
     *             required={"player_id", "asking_price"},
     *
     *             @OA\Property(
     *                 property="player_id",
     *                 type="integer",
     *                 description="ID of the player to list for transfer",
     *                 example=1
     *             ),
     *             @OA\Property(
     *                 property="asking_price",
     *                 type="number",
     *                 format="float",
     *                 minimum=1,
     *                 description="Asking price for the player (minimum 1)",
     *                 example=1000000.50
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Player listed for transfer successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Player listed for transfer successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="transfer_listing",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="player_id", type="integer", example=1),
     *                     @OA\Property(property="selling_team_id", type="integer", example=1),
     *                     @OA\Property(property="asking_price", type="number", format="float", example=1000000.50),
     *                     @OA\Property(property="status", type="string", example="active"),
     *                     @OA\Property(property="unique_key", type="string", example="active"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-15T10:30:00.000000Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-15T10:30:00.000000Z")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="Bad request - Player already listed or validation error",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Player is already listed for transfer."),
     *             @OA\Property(property="errors", type="object", example={})
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Player not found or does not belong to user's team",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Player not found or does not belong to your team."),
     *             @OA\Property(property="errors", type="object", example={})
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
     *                     property="player_id",
     *                     type="array",
     *
     *                     @OA\Items(type="string"),
     *                     example={"The player id field is required.", "The selected player id is invalid."}
     *                 ),
     *
     *                 @OA\Property(
     *                     property="asking_price",
     *                     type="array",
     *
     *                     @OA\Items(type="string"),
     *                     example={"The asking price field is required.", "The asking price must be at least 1."}
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
    public function store(TransferListingRequest $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (! $user->team_id) {
                return $this->errorResponse('messages.transfer.no_team', [], 400);
            }

            $transferListing = $this->transferListingService->listPlayerForTransfer(
                $request->player_id,
                $user->team_id,
                $request->asking_price
            );

            return $this->successResponse([
                'transfer_listing' => new TransferListingResource($transferListing),
            ], 'messages.transfer.listed_successfully', 201);

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('messages.transfer.player_not_found', [], 404);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), [], 400);
        }
    }
}
