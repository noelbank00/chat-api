<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SendMessageRequest;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

class MessageController extends Controller
{
    public function sendMessage(SendMessageRequest $request): JsonResponse
    {
        $message = Message::create([
            'sender_id' => auth()->id(),
            'receiver_id' => $request->validated('receiver_id'),
            'content' => $request->validated('content'),
        ]);

        return response()->json([
            'message' => 'Message sent successfully.',
            'data' => $message->load(['sender', 'receiver']),
        ], 201);
    }

    public function getMessages(User $partner): JsonResponse
    {
        $authUser = auth()->user();

        if (!$authUser->isFriendWith($partner->id)) {
            return response()->json([
                'message' => 'You can only view messages with friends.'
            ], 403);
        }

        $messages = Message::query()
            ->where(function (Builder $builder) use ($authUser, $partner) {
                $builder->where('sender_id', $authUser->id)
                    ->where('receiver_id', $partner->id);
            })
            ->orWhere(function ($query) use ($authUser, $partner) {
                $query->where('sender_id', $partner->id)
                    ->where('receiver_id', $authUser->id);
            })
            ->with(['sender', 'receiver'])
            ->orderBy('created_at')
            ->get();

        Message::query()
            ->where('sender_id', $partner->id)
            ->where('receiver_id', $authUser->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'messages' => $messages,
            'partner' => $partner,
        ]);
    }
}
