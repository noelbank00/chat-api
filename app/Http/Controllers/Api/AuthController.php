<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Services\AuthService;
use Exception;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\User;

class AuthController extends Controller
{
    /**
     * @param LoginRequest $request
     * @return JsonResponse
     * @throws Exception
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $request->authenticate();
        
        if ($result === 'email_not_verified') {
            return response()->json([
                'message' => 'Please verify your email address before logging in.'
            ], 403);
        }
        
        if (is_null($result)) {
            return response()->json([
                'message' => 'Invalid credentials.'
            ], 401);
        }

        $token = $result->createToken(
            'chat-api-token',
            ['*']
        )->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $request->user(),
        ]);
    }

    public function register(RegisterRequest $request, AuthService $authService): JsonResponse
    {
        $authService->register(
            $request->validated('name'),
            $request->validated('email'),
            $request->validated('password'),
        );

        return response()->json([
            'message' => 'User registered successfully, please verify your email.',
        ]);
    }

    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    public function verifyEmail(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = User::query()->find($request->route('id'));

        if (!$user || !hash_equals(sha1($user->getEmailForVerification()), (string) $request->route('hash'))) {
            return response()->json(['message' => 'Invalid verification link.'], 400);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified.']);
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return response()->json(['message' => 'Email verified successfully.']);
    }

    public function resendVerificationEmail(Request $request): JsonResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified.']);
        }

        $request->user()->sendEmailVerificationNotification();

        return response()->json(['message' => 'Verification email sent.']);
    }
}
