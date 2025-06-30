<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendFriendRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'friend_id' => [
                'required',
                'exists:users,id',
                Rule::notIn([auth()->id()]),
                function ($attribute, $value, $fail) {
                    $friend = User::query()->find($value);
                    
                    if ($friend && !$friend->is_active) {
                        $fail('Cannot send friend request to inactive user.');
                    }
                    
                    if ($friend && auth()->user()->hasFriendship($friend->id)) {
                        $fail('Friendship request already exists or you are already friends.');
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'friend_id.not_in' => 'You cannot send friend request to yourself.',
        ];
    }
}
