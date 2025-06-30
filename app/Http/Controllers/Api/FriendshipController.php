<?php

namespace App\Http\Controllers\Api;

use App\Enums\FriendshipStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\SendFriendRequestRequest;
use App\Models\Friendship;
use Illuminate\Http\JsonResponse;

class FriendshipController extends Controller
{
    public function sendRequest(SendFriendRequestRequest $request): JsonResponse
    {
        $friendship = Friendship::query()->create([
            'user_id' => auth()->id(),
            'friend_id' => $request->validated('friend_id'),
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Friend request sent successfully.',
            'friendship' => $friendship->id,
        ], 201);
    }

    public function acceptRequest(Friendship $friendship): JsonResponse
    {
        if ($friendship->friend_id !== auth()->id()) {
            return response()->json([
                'message' => 'You can only accept friend requests sent to you.'
            ], 403);
        }

        if ($friendship->status !== FriendshipStatusEnum::Pending) {
            return response()->json([
                'message' => 'Friend request is not pending.'
            ], 400);
        }

        $friendship->update(['status' => FriendshipStatusEnum::Accepted]);

        return response()->json([
            'message' => 'Friend request accepted.',
            'friendship' => $friendship->id,
        ]);
    }

    public function rejectRequest(Friendship $friendship): JsonResponse
    {
        if ($friendship->friend_id !== auth()->id()) {
            return response()->json([
                'message' => 'You can only reject friend requests sent to you.'
            ], 403);
        }

        if ($friendship->status !== FriendshipStatusEnum::Pending) {
            return response()->json([
                'message' => 'Friend request is not pending.'
            ], 400);
        }

        $friendship->update(['status' => FriendshipStatusEnum::Rejected]);

        return response()->json([
            'message' => 'Friend request rejected.',
            'friendship' => $friendship->id,
        ]);
    }

    public function friends(): JsonResponse
    {
        $user = auth()->user();
        $friends = $user->allFriends();

        return response()->json([
            'friends' => $friends,
        ]);
    }

    public function pendingRequests(): JsonResponse
    {
        $pendingRequests = auth()->user()
            ->pendingFriendRequests()
            ->with('user')
            ->get();

        return response()->json([
            'pending_requests' => $pendingRequests,
        ]);
    }

    public function removeFriend(Friendship $friendship): JsonResponse
    {
        if ($friendship->user_id !== auth()->id() && $friendship->friend_id !== auth()->id()) {
            return response()->json([
                'message' => 'You can only remove friendships you are part of.'
            ], 403);
        }

        if ($friendship->status !== FriendshipStatusEnum::Accepted) {
            return response()->json([
                'message' => 'You can only remove accepted friendships.'
            ], 400);
        }

        $friendship->delete();

        return response()->json([
            'message' => 'Friendship removed successfully.',
        ]);
    }
}
