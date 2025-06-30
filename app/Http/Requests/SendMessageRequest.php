<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendMessageRequest extends FormRequest
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
            'receiver_id' => [
                'required',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    $receiver = User::query()->find($value);
                    
                    if ($receiver && !$receiver->is_active) {
                        $fail('Cannot send message to inactive user.');
                    }
                    
                    if ($receiver && !auth()->user()->isFriendWith($receiver->id)) {
                        $fail('You can only send messages to friends.');
                    }
                },
            ],
            'content' => [
                'required',
                'string',
                'min:1',
                'max:1000',
            ],
        ];
    }
}
