<?php

namespace Tests\Unit\Services;

use App\Enums\FriendshipStatusEnum;
use App\Models\Friendship;
use App\Models\User;
use App\Services\FriendshipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FriendshipServiceTest extends TestCase
{
    use RefreshDatabase;

    private FriendshipService $friendshipService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->friendshipService = new FriendshipService();
    }

    public function testCanSendFriendRequest(): void
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();

        $friendship = $this->friendshipService->sendFriendRequest($sender, $receiver);

        $this->assertInstanceOf(Friendship::class, $friendship);
        $this->assertEquals($sender->id, $friendship->user_id);
        $this->assertEquals($receiver->id, $friendship->friend_id);
        $this->assertEquals(FriendshipStatusEnum::Pending, $friendship->status);
    }

    public function testCanAcceptFriendRequest(): void
    {
        $friendship = Friendship::factory()->create([
            'status' => FriendshipStatusEnum::Pending
        ]);

        $result = $this->friendshipService->acceptFriendRequest($friendship);

        $this->assertTrue($result);
        $friendship->refresh();
        $this->assertEquals(FriendshipStatusEnum::Accepted, $friendship->status);
    }

    public function testCanRejectFriendRequest(): void
    {
        $friendship = Friendship::factory()->create([
            'status' => FriendshipStatusEnum::Pending
        ]);

        $result = $this->friendshipService->rejectFriendRequest($friendship);

        $this->assertTrue($result);
        $friendship->refresh();
        $this->assertEquals(FriendshipStatusEnum::Rejected, $friendship->status);
    }

    public function testCanRemoveFriendship(): void
    {
        $friendship = Friendship::factory()->create([
            'status' => FriendshipStatusEnum::Accepted
        ]);

        $result = $this->friendshipService->removeFriendship($friendship);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('friendships', ['id' => $friendship->id]);
    }

    public function testCanGetFriends(): void
    {
        $user = User::factory()->create();
        $friend1 = User::factory()->create(['name' => 'Friend 1']);
        $friend2 = User::factory()->create(['name' => 'Friend 2']);
        $notFriend = User::factory()->create(['name' => 'Not Friend']);

        // Create accepted friendships
        Friendship::factory()->create([
            'user_id' => $user->id,
            'friend_id' => $friend1->id,
            'status' => FriendshipStatusEnum::Accepted
        ]);

        Friendship::factory()->create([
            'user_id' => $friend2->id,
            'friend_id' => $user->id,
            'status' => FriendshipStatusEnum::Accepted
        ]);

        // Create pending friendship (should not be included)
        Friendship::factory()->create([
            'user_id' => $user->id,
            'friend_id' => $notFriend->id,
            'status' => FriendshipStatusEnum::Pending
        ]);

        $friends = $this->friendshipService->getFriends($user);

        $this->assertCount(2, $friends);
        $friendNames = array_column($friends, 'name');
        $this->assertContains('Friend 1', $friendNames);
        $this->assertContains('Friend 2', $friendNames);
        $this->assertNotContains('Not Friend', $friendNames);
    }

    public function testCanGetPendingRequests(): void
    {
        $user = User::factory()->create();
        $requester1 = User::factory()->create(['name' => 'Requester 1']);
        $requester2 = User::factory()->create(['name' => 'Requester 2']);

        // Create pending requests to user
        Friendship::factory()->create([
            'user_id' => $requester1->id,
            'friend_id' => $user->id,
            'status' => FriendshipStatusEnum::Pending
        ]);

        Friendship::factory()->create([
            'user_id' => $requester2->id,
            'friend_id' => $user->id,
            'status' => FriendshipStatusEnum::Pending
        ]);

        // Create accepted friendship (should not be included)
        Friendship::factory()->create([
            'user_id' => User::factory()->create()->id,
            'friend_id' => $user->id,
            'status' => FriendshipStatusEnum::Accepted
        ]);

        $pendingRequests = $this->friendshipService->getPendingRequests($user);

        $this->assertCount(2, $pendingRequests);
    }

    public function testCanAcceptRequestValidation(): void
    {
        $user = User::factory()->create();
        $otherUser1 = User::factory()->create();
        $otherUser2 = User::factory()->create();
        $otherUser3 = User::factory()->create();

        $pendingFriendship = Friendship::factory()->create([
            'user_id' => $otherUser1->id,
            'friend_id' => $user->id,
            'status' => FriendshipStatusEnum::Pending
        ]);

        $acceptedFriendship = Friendship::factory()->create([
            'user_id' => $otherUser2->id,
            'friend_id' => $user->id,
            'status' => FriendshipStatusEnum::Accepted
        ]);

        $wrongUserFriendship = Friendship::factory()->create([
            'user_id' => $user->id,
            'friend_id' => $otherUser3->id,
            'status' => FriendshipStatusEnum::Pending
        ]);

        // Can accept pending request sent to user
        $this->assertTrue($this->friendshipService->canAcceptRequest($user, $pendingFriendship));

        // Cannot accept already accepted request
        $this->assertFalse($this->friendshipService->canAcceptRequest($user, $acceptedFriendship));

        // Cannot accept request not sent to user
        $this->assertFalse($this->friendshipService->canAcceptRequest($user, $wrongUserFriendship));
    }

    public function testCanRejectRequestValidation(): void
    {
        $user = User::factory()->create();
        $otherUser1 = User::factory()->create();
        $otherUser2 = User::factory()->create();

        $pendingFriendship = Friendship::factory()->create([
            'user_id' => $otherUser1->id,
            'friend_id' => $user->id,
            'status' => FriendshipStatusEnum::Pending
        ]);

        $acceptedFriendship = Friendship::factory()->create([
            'user_id' => $otherUser2->id,
            'friend_id' => $user->id,
            'status' => FriendshipStatusEnum::Accepted
        ]);

        // Can reject pending request
        $this->assertTrue($this->friendshipService->canRejectRequest($user, $pendingFriendship));

        // Cannot reject accepted request
        $this->assertFalse($this->friendshipService->canRejectRequest($user, $acceptedFriendship));
    }

    public function testCanRemoveFriendshipValidation(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        $acceptedFriendship = Friendship::factory()->create([
            'user_id' => $user1->id,
            'friend_id' => $user2->id,
            'status' => FriendshipStatusEnum::Accepted
        ]);

        $pendingFriendship = Friendship::factory()->create([
            'user_id' => User::factory()->create()->id,
            'friend_id' => User::factory()->create()->id,
            'status' => FriendshipStatusEnum::Pending
        ]);

        $otherUserFriendship = Friendship::factory()->create([
            'user_id' => $user2->id,
            'friend_id' => $user3->id,
            'status' => FriendshipStatusEnum::Accepted
        ]);

        // Can remove accepted friendship where user is involved
        $this->assertTrue($this->friendshipService->canRemoveFriendship($user1, $acceptedFriendship));
        $this->assertTrue($this->friendshipService->canRemoveFriendship($user2, $acceptedFriendship));

        // Cannot remove pending friendship
        $this->assertFalse($this->friendshipService->canRemoveFriendship($user1, $pendingFriendship));

        // Cannot remove friendship user is not part of
        $this->assertFalse($this->friendshipService->canRemoveFriendship($user1, $otherUserFriendship));
    }
}
