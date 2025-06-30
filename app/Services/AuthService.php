<?php

namespace App\Services;

use App\Models\User;

class AuthService
{

    public function register(string $name, string $email, string $password): User
    {
        $user = User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ]);

        $user->sendEmailVerificationNotification();

        return $user;
    }
}