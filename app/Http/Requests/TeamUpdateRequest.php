<?php

namespace App\Http\Requests;

use App\Services\Interfaces\TeamAuthorizationServiceInterface;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class TeamUpdateRequest extends FormRequest
{
    private ?string $failureReason = null;

    public function __construct(
        private TeamAuthorizationServiceInterface $teamAuthorizationService
    ) {
        parent::__construct();
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user) {
            $this->failureReason = 'unauthenticated';

            return false;
        }

        if (! $this->teamAuthorizationService->userHasTeam($user->id)) {
            $this->failureReason = 'no_team';

            return false;
        }

        $teamId = $this->route('team');

        if (! is_numeric($teamId) || (int) $teamId <= 0) {
            $this->failureReason = 'invalid_team_id';

            return false;
        }

        $teamId = (int) $teamId;

        if (! $this->teamAuthorizationService->teamExists($teamId)) {
            $this->failureReason = 'team_not_found';

            return false;
        }

        if (! $this->teamAuthorizationService->canUserUpdateTeam($user->id, $teamId)) {
            $this->failureReason = 'no_permission';

            return false;
        }

        return true;
    }

    /**
     * Handle a failed authorization attempt.
     */
    protected function failedAuthorization(): void
    {
        switch ($this->failureReason) {
            case 'unauthenticated':
                throw new HttpResponseException(
                    response()->json([
                        'success' => false,
                        'message' => trans('messages.auth.unauthenticated'),
                        'errors' => [],
                    ], 401)
                );

            case 'no_team':
                throw new HttpResponseException(
                    response()->json([
                        'success' => false,
                        'message' => trans('messages.team.no_team'),
                        'errors' => [],
                    ], 403)
                );

            case 'invalid_team_id':
            case 'team_not_found':
                throw new HttpResponseException(
                    response()->json([
                        'success' => false,
                        'message' => trans('messages.team.not_found'),
                        'errors' => [],
                    ], 404)
                );

            case 'no_permission':
            default:
                throw new HttpResponseException(
                    response()->json([
                        'success' => false,
                        'message' => trans('messages.team.not_owned'),
                        'errors' => [],
                    ], 403)
                );
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $teamId = $this->route('team');

        return [
            'name' => [
                'required',
                'string',
                'min:2',
                'max:100',
                'unique:teams,name,'.$teamId, // Allow current team to keep its name
            ],
            'country_id' => [
                'required',
                'integer',
                'exists:countries,id',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The team name is required.',
            'name.string' => 'The team name must be a string.',
            'name.min' => 'The team name must be at least 2 characters.',
            'name.max' => 'The team name must not exceed 100 characters.',
            'name.unique' => 'The team name has already been taken.',
            'country_id.required' => 'The country is required.',
            'country_id.integer' => 'The country must be a valid integer.',
            'country_id.exists' => 'The selected country does not exist.',
        ];
    }
}
