<?php

namespace App\Http\Requests;

use App\Models\User;
use Exception;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return !auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:8',
        ];
    }

    /**
     * @return Authenticatable|null
     * @throws Exception
     */
    public function authenticate(): Authenticatable|null|string
    {
        $credentials = $this->only('email', 'password');

        if (!auth()->attempt($credentials)) {
            return null;
        }

        $user = auth()->user();

        if (!$user->hasVerifiedEmail()) {
            auth()->logout();
            return 'email_not_verified';
        }

        return $user;
    }
}
