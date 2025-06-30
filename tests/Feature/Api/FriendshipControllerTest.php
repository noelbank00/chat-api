<?php

namespace Tests\Feature\Api;

use App\Enums\FriendshipStatusEnum;
use App\Models\Friendship;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FriendshipControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testCanSendFriendRequest(): void
    {
        $sender = User::factory()->create(['email_verified_at' => now()]);
        $receiver = User::factory()->create(['email_verified_at' => now(), 'is_active' => true]);

        Sanctum::actingAs($sender);

        $response = $this->postJson(route('friendships.send'), [
            'friend_id' => $receiver->id
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Friend request sent successfully.'
            ]);

        $this->assertDatabaseHas('friendships', [
            'user_id' => $sender->id,
            'friend_id' => $receiver->id,
            'status' => FriendshipStatusEnum::Pending
        ]);
    }

    public function testCannotSendFriendRequestToInactiveUser(): void
    {
        $sender = User::factory()->create(['email_verified_at' => now()]);
        $receiver = User::factory()->create(['email_verified_at' => now(), 'is_active' => false]);

        Sanctum::actingAs($sender);

        $response = $this->postJson(route('friendships.send'), [
            'friend_id' => $receiver->id
        ]);

        $response->assertStatus(422);
    }

    public function testCannotSendFriendRequestToSelf(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        Sanctum::actingAs($user);

        $response = $this->postJson(route('friendships.send'), [
            'friend_id' => $user->id
        ]);

        $response->assertStatus(422);
    }

    public function testCannotSendDuplicateFriendRequest(): void
    {
        $sender = User::factory()->create(['email_verified_at' => now()]);
        $receiver = User::factory()->create(['email_verified_at' => now(), 'is_active' => true]);

        // Create existing friendship
        Friendship::factory()->create([
            'user_id' => $sender->id,
            'friend_id' => $receiver->id,
            'status' => FriendshipStatusEnum::Pending
        ]);

        Sanctum::actingAs($sender);

        $response = $this->postJson(route('friendships.send'), [
            'friend_id' => $receiver->id
        ]);

        $response->assertStatus(422);
    }

    public function testCanAcceptFriendRequest(): void
    {
        $requester = User::factory()->create(['email_verified_at' => now()]);
        $receiver = User::factory()->create(['email_verified_at' => now()]);

        $friendship = Friendship::factory()->create([
            'user_id' => $requester->id,
            'friend_id' => $receiver->id,
            'status' => FriendshipStatusEnum::Pending
        ]);

        Sanctum::actingAs($receiver);

        $response = $this->postJson(route('friendships.accept', $friendship));

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Friend request accepted.'
            ]);

        $friendship->refresh();
        $this->assertEquals(FriendshipStatusEnum::Accepted, $friendship->status);
    }

    public function testCannotAcceptOthersFriendRequest(): void
    {
        $requester = User::factory()->create(['email_verified_at' => now()]);
        $receiver = User::factory()->create(['email_verified_at' => now()]);
        $other = User::factory()->create(['email_verified_at' => now()]);

        $friendship = Friendship::factory()->create([
            'user_id' => $requester->id,
            'friend_id' => $receiver->id,
            'status' => FriendshipStatusEnum::Pending
        ]);

        Sanctum::actingAs($other);

        $response = $this->postJson(route('friendships.accept', $friendship));

        $response->assertStatus(403);
    }

    public function testCannotAcceptAlreadyAcceptedRequest(): void
    {
        $requester = User::factory()->create(['email_verified_at' => now()]);
        $receiver = User::factory()->create(['email_verified_at' => now()]);

        $friendship = Friendship::factory()->create([
            'user_id' => $requester->id,
            'friend_id' => $receiver->id,
            'status' => FriendshipStatusEnum::Accepted
        ]);

        Sanctum::actingAs($receiver);

        $response = $this->postJson(route('friendships.accept', $friendship));

        $response->assertStatus(403);
    }

    public function testCanRejectFriendRequest(): void
    {
        $requester = User::factory()->create(['email_verified_at' => now()]);
        $receiver = User::factory()->create(['email_verified_at' => now()]);

        $friendship = Friendship::factory()->create([
            'user_id' => $requester->id,
            'friend_id' => $receiver->id,
            'status' => FriendshipStatusEnum::Pending
        ]);

        Sanctum::actingAs($receiver);

        $response = $this->postJson(route('friendships.reject', $friendship));

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Friend request rejected.'
            ]);

        $friendship->refresh();
        $this->assertEquals(FriendshipStatusEnum::Rejected, $friendship->status);
    }

    public function testCanRemoveFriendship(): void
    {
        $user1 = User::factory()->create(['email_verified_at' => now()]);
        $user2 = User::factory()->create(['email_verified_at' => now()]);

        $friendship = Friendship::factory()->create([
            'user_id' => $user1->id,
            'friend_id' => $user2->id,
            'status' => FriendshipStatusEnum::Accepted
        ]);

        Sanctum::actingAs($user1);

        $response = $this->deleteJson(route('friendships.remove', $friendship));

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Friendship removed successfully.'
            ]);

        $this->assertDatabaseMissing('friendships', ['id' => $friendship->id]);
    }

    public function testCannotRemovePendingFriendship(): void
    {
        $user1 = User::factory()->create(['email_verified_at' => now()]);
        $user2 = User::factory()->create(['email_verified_at' => now()]);

        $friendship = Friendship::factory()->create([
            'user_id' => $user1->id,
            'friend_id' => $user2->id,
            'status' => FriendshipStatusEnum::Pending
        ]);

        Sanctum::actingAs($user1);

        $response = $this->deleteJson(route('friendships.remove', $friendship));

        $response->assertStatus(403);
    }

    public function testCannotRemoveOthersFriendship(): void
    {
        $user1 = User::factory()->create(['email_verified_at' => now()]);
        $user2 = User::factory()->create(['email_verified_at' => now()]);
        $other = User::factory()->create(['email_verified_at' => now()]);

        $friendship = Friendship::factory()->create([
            'user_id' => $user1->id,
            'friend_id' => $user2->id,
            'status' => FriendshipStatusEnum::Accepted
        ]);

        Sanctum::actingAs($other);

        $response = $this->deleteJson(route('friendships.remove', $friendship));

        $response->assertStatus(403);
    }

    public function testRequiresAuthentication(): void
    {
        $user = User::factory()->create();

        // Test all endpoints require authentication
        $this->postJson(route('friendships.send'), ['friend_id' => $user->id])
            ->assertStatus(401);

        $this->postJson(route('friendships.accept', 1))
            ->assertStatus(401);

        $this->postJson(route('friendships.reject', 1))
            ->assertStatus(401);

        $this->deleteJson(route('friendships.remove', 1))
            ->assertStatus(401);
    }

    public function testValidatesFriendRequestData(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        Sanctum::actingAs($user);

        // Test missing friend_id
        $response = $this->postJson(route('friendships.send'), []);
        $response->assertStatus(422);

        // Test non-existent friend_id
        $response = $this->postJson(route('friendships.send'), ['friend_id' => 99999]);
        $response->assertStatus(422);
    }
}
