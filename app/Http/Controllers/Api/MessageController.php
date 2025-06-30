<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SendMessageRequest;
use App\Models\User;
use App\Services\MessageService;
use Illuminate\Http\JsonResponse;

class MessageController extends Controller
{
    public function __construct(
        private readonly MessageService $messageService
    ) {
    }

    public function sendMessage(SendMessageRequest $request): JsonResponse
    {
        /** @var User $sender */
        $sender = auth()->user();
        $receiver = User::query()->findOrFail($request->validated('receiver_id'));

        $message = $this->messageService->sendMessage(
            $sender,
            $receiver,
            $request->validated('content')
        );

        return response()->json([
            'message' => 'Message sent successfully.',
            'data' => $message->load(['sender', 'receiver']),
        ], 201);
    }

    public function getMessages(User $partner): JsonResponse
    {
        /** @var User $authUser */
        $authUser = auth()->user();

        if (!$authUser->isFriendWith($partner->id)) {
            return response()->json([
                'message' => 'You can only view messages with friends.'
            ], 403);
        }

        $messages = $this->messageService->getMessagesBetweenUsers($authUser, $partner);
        $this->messageService->markMessagesAsRead($partner, $authUser);

        return response()->json([
            'messages' => $messages,
            'partner' => $partner,
        ]);
    }
}
