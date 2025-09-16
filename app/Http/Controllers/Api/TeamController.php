<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TeamUpdateRequest;
use App\Http\Resources\TeamResource;
use App\Services\Interfaces\TeamServiceInterface;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class TeamController extends Controller
{
    use ApiResponse;

    public function __construct(
        private TeamServiceInterface $teamService
    ) {}

    /**
     * @OA\Put(
     *     path="/api/teams/{team}",
     *     summary="Update Team Information",
     *     description="Update team information (name, country). Only team owner can update their team.",
     *     operationId="updateTeam",
     *     tags={"Teams"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(ref="#/components/parameters/Accept-Language"),
     *     @OA\Parameter(
     *         name="team",
     *         in="path",
     *         description="ID of the team to update",
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
     *         description="Team update data",
     *
     *         @OA\JsonContent(
     *             required={"name", "country_id"},
     *
     *             @OA\Property(
     *                 property="name",
     *                 type="string",
     *                 minLength=2,
     *                 maxLength=100,
     *                 description="Team name",
     *                 example="Manchester United"
     *             ),
     *             @OA\Property(
     *                 property="country_id",
     *                 type="integer",
     *                 description="ID of the team's country",
     *                 example=1
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Team updated successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Team updated successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="uuid", type="string", example="123e4567-e89b-12d3-a456-426614174000"),
     *                 @OA\Property(property="name", type="string", example="Manchester United"),
     *                 @OA\Property(property="balance", type="number", format="float", example=5000000.00),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-01-01T00:00:00.000000Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-01-01T00:00:00.000000Z")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="Bad request - Team not found or not owned by user",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Team not found or you don't have permission to update this team"),
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
     *         description="Forbidden - User doesn't have a team or team doesn't belong to user",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="You can only update your own team"),
     *             @OA\Property(property="errors", type="array", @OA\Items(type="string"))
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Team not found",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Team not found"),
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
     *                     property="name",
     *                     type="array",
     *
     *                     @OA\Items(type="string", example="The team name is required.")
     *                 ),
     *
     *                 @OA\Property(
     *                     property="country_id",
     *                     type="array",
     *
     *                     @OA\Items(type="string", example="The country is required.")
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
    public function update(TeamUpdateRequest $request, $team): JsonResponse
    {
        try {
            $teamId = (int) $team;
            $user = $request->user();
            $validatedData = $request->validated();

            $updatedTeam = $this->teamService->updateTeam(
                $teamId,
                $user->id,
                $validatedData
            );

            return $this->successResponse(
                new TeamResource($updatedTeam),
                'messages.team.updated_successfully'
            );

        } catch (\Exception $e) {
            \Log::error('Team update failed: '.$e->getMessage(), [
                'team_id' => $teamId ?? $team,
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse($e->getMessage(), [], 400);
        }
    }
}
