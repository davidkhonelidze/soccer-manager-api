<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransferListingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'player_id' => 'required|integer|exists:players,id',
            'asking_price' => 'required|numeric|min:1',
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
            'player_id.required' => 'Player ID is required.',
            'player_id.integer' => 'Player ID must be an integer.',
            'player_id.exists' => 'The selected player does not exist.',
            'asking_price.required' => 'Asking price is required.',
            'asking_price.numeric' => 'Asking price must be a number.',
            'asking_price.min' => 'Asking price must be at least 1.',
        ];
    }
}
