<?php

namespace App\Http\Requests;

use App\Services\Interfaces\PlayerAuthorizationServiceInterface;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdatePlayerRequest extends FormRequest
{
    private ?string $failureReason = null;

    public function __construct(
        private PlayerAuthorizationServiceInterface $playerAuthorizationService
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

        if (! $this->playerAuthorizationService->userHasTeam($user->id)) {
            $this->failureReason = 'no_team';

            return false;
        }

        $playerId = $this->route('player');

        if (! is_numeric($playerId) || (int) $playerId <= 0) {
            $this->failureReason = 'invalid_player_id';

            return false;
        }

        $playerId = (int) $playerId;

        if (! $this->playerAuthorizationService->playerExists($playerId)) {
            $this->failureReason = 'player_not_found';

            return false;
        }

        if (! $this->playerAuthorizationService->canUserUpdatePlayer($user->id, $playerId)) {
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
                        'message' => trans('messages.player.no_team'),
                        'errors' => [],
                    ], 403)
                );

            case 'invalid_player_id':
            case 'player_not_found':
                throw new HttpResponseException(
                    response()->json([
                        'success' => false,
                        'message' => trans('messages.player.not_found'),
                        'errors' => [],
                    ], 404)
                );

            case 'no_permission':
            default:
                throw new HttpResponseException(
                    response()->json([
                        'success' => false,
                        'message' => trans('messages.player.not_owned'),
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
        return [
            'first_name' => [
                'required',
                'string',
                'min:2',
                'max:30',
            ],
            'last_name' => [
                'required',
                'string',
                'min:2',
                'max:30',
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
            'first_name.required' => 'First name is required.',
            'first_name.min' => 'First name must be at least 2 characters.',
            'first_name.max' => 'First name may not be greater than 30 characters.',
            'last_name.required' => 'Last name is required.',
            'last_name.min' => 'Last name must be at least 2 characters.',
            'last_name.max' => 'Last name may not be greater than 30 characters.',
            'country_id.required' => 'Country is required.',
            'country_id.exists' => 'The selected country is invalid.',
        ];
    }
}
