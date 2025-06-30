<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SendFriendRequestRequest;
use App\Models\Friendship;
use App\Models\User;
use App\Services\FriendshipService;
use Illuminate\Http\JsonResponse;

class FriendshipController extends Controller
{
    public function __construct(
        private readonly FriendshipService $friendshipService
    ) {
    }

    public function sendRequest(SendFriendRequestRequest $request): JsonResponse
    {
        /** @var User $sender */
        $sender = auth()->user();
        $receiver = User::query()->findOrFail($request->validated('friend_id'));

        $friendship = $this->friendshipService->sendFriendRequest($sender, $receiver);

        return response()->json([
            'message' => 'Friend request sent successfully.',
            'friendship' => $friendship->id,
        ], 201);
    }

    public function acceptRequest(Friendship $friendship): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        if (!$this->friendshipService->canAcceptRequest($user, $friendship)) {
            return response()->json([
                'message' => 'You can only accept pending friend requests sent to you.'
            ], 403);
        }

        $this->friendshipService->acceptFriendRequest($friendship);

        return response()->json([
            'message' => 'Friend request accepted.',
            'friendship' => $friendship->id,
        ]);
    }

    public function rejectRequest(Friendship $friendship): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        if (!$this->friendshipService->canRejectRequest($user, $friendship)) {
            return response()->json([
                'message' => 'You can only reject pending friend requests sent to you.'
            ], 403);
        }

        $this->friendshipService->rejectFriendRequest($friendship);

        return response()->json([
            'message' => 'Friend request rejected.',
            'friendship' => $friendship->id,
        ]);
    }

    public function friends(): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();
        $friends = $this->friendshipService->getFriends($user);

        return response()->json([
            'friends' => $friends,
        ]);
    }

    public function pendingRequests(): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();
        $pendingRequests = $this->friendshipService->getPendingRequests($user);

        return response()->json([
            'pending_requests' => $pendingRequests,
        ]);
    }

    public function removeFriend(Friendship $friendship): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        if (!$this->friendshipService->canRemoveFriendship($user, $friendship)) {
            return response()->json([
                'message' => 'You can only remove accepted friendships you are part of.'
            ], 403);
        }

        $this->friendshipService->removeFriendship($friendship);

        return response()->json([
            'message' => 'Friendship removed successfully.',
        ]);
    }
}
