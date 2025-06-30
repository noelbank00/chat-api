<?php

namespace App\Services;

use App\Enums\FriendshipStatusEnum;
use App\Models\Friendship;
use App\Models\User;

class FriendshipService
{
    public function sendFriendRequest(User $sender, User $receiver): Friendship
    {
        return Friendship::create([
            'user_id' => $sender->id,
            'friend_id' => $receiver->id,
            'status' => FriendshipStatusEnum::Pending,
        ]);
    }

    public function acceptFriendRequest(Friendship $friendship): bool
    {
        return $friendship->update(['status' => FriendshipStatusEnum::Accepted]);
    }

    public function rejectFriendRequest(Friendship $friendship): bool
    {
        return $friendship->update(['status' => FriendshipStatusEnum::Rejected]);
    }

    public function removeFriendship(Friendship $friendship): bool
    {
        return $friendship->delete();
    }

    public function getFriends(User $user): array
    {
        return $user->allFriends()->toArray();
    }

    public function getPendingRequests(User $user): array
    {
        return $user->pendingFriendRequests()
            ->with('user')
            ->get()
            ->toArray();
    }

    public function canAcceptRequest(User $user, Friendship $friendship): bool
    {
        return $friendship->friend_id === $user->id 
            && $friendship->status === FriendshipStatusEnum::Pending;
    }

    public function canRejectRequest(User $user, Friendship $friendship): bool
    {
        return $friendship->friend_id === $user->id 
            && $friendship->status === FriendshipStatusEnum::Pending;
    }

    public function canRemoveFriendship(User $user, Friendship $friendship): bool
    {
        return ($friendship->user_id === $user->id || $friendship->friend_id === $user->id)
            && $friendship->status === FriendshipStatusEnum::Accepted;
    }
}
