<?php

namespace Tests\Feature\Api;

use App\Enums\FriendshipStatusEnum;
use App\Models\Friendship;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MessageControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testCanSendMessageToFriend(): void
    {
        $sender = User::factory()->create(['email_verified_at' => now()]);
        $receiver = User::factory()->create(['email_verified_at' => now(), 'is_active' => true]);

        // Create friendship
        Friendship::factory()->create([
            'user_id' => $sender->id,
            'friend_id' => $receiver->id,
            'status' => FriendshipStatusEnum::Accepted
        ]);

        Sanctum::actingAs($sender);

        $response = $this->postJson(route('messages.send'), [
            'receiver_id' => $receiver->id,
            'content' => 'Hello friend!'
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Message sent successfully.',
                'data' => [
                    'content' => 'Hello friend!',
                    'sender_id' => $sender->id,
                    'receiver_id' => $receiver->id
                ]
            ]);

        $this->assertDatabaseHas('messages', [
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'content' => 'Hello friend!'
        ]);
    }

    public function testCannotSendMessageToNonFriend(): void
    {
        $sender = User::factory()->create(['email_verified_at' => now()]);
        $receiver = User::factory()->create(['email_verified_at' => now()]);

        Sanctum::actingAs($sender);

        $response = $this->postJson(route('messages.send'), [
            'receiver_id' => $receiver->id,
            'content' => 'Hello stranger!'
        ]);

        $response->assertStatus(422);
    }

    public function testCannotSendMessageToInactiveUser(): void
    {
        $sender = User::factory()->create(['email_verified_at' => now()]);
        $receiver = User::factory()->create([
            'email_verified_at' => now(),
            'is_active' => false
        ]);

        Sanctum::actingAs($sender);

        $response = $this->postJson(route('messages.send'), [
            'receiver_id' => $receiver->id,
            'content' => 'Hello!'
        ]);

        $response->assertStatus(422);
    }

    public function testCannotSendMessageToSelf(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        Sanctum::actingAs($user);

        $response = $this->postJson(route('messages.send'), [
            'receiver_id' => $user->id,
            'content' => 'Hello myself!'
        ]);

        $response->assertStatus(422);
    }

    public function testCanGetMessagesWithFriend(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $friend = User::factory()->create(['email_verified_at' => now(), 'is_active' => true]);

        // Create friendship
        Friendship::factory()->create([
            'user_id' => $user->id,
            'friend_id' => $friend->id,
            'status' => FriendshipStatusEnum::Accepted
        ]);

        // Create messages
        $message1 = Message::factory()->create([
            'sender_id' => $user->id,
            'receiver_id' => $friend->id,
            'content' => 'Hello friend!'
        ]);

        $message2 = Message::factory()->create([
            'sender_id' => $friend->id,
            'receiver_id' => $user->id,
            'content' => 'Hello back!',
            'read_at' => null
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson(route('messages.show', $friend));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'messages' => [
                    '*' => ['id', 'content', 'sender_id', 'receiver_id', 'created_at']
                ],
                'partner' => ['id', 'name', 'email']
            ]);

        $this->assertCount(2, $response->json('messages'));
    }

    public function testCannotGetMessagesWithNonFriend(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $stranger = User::factory()->create(['email_verified_at' => now()]);

        Sanctum::actingAs($user);

        $response = $this->getJson(route('messages.show', $stranger));

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'You can only view messages with friends.'
            ]);
    }

    public function testRequiresAuthenticationForSendingMessages(): void
    {
        $receiver = User::factory()->create();

        $response = $this->postJson(route('messages.send'), [
            'receiver_id' => $receiver->id,
            'content' => 'Hello!'
        ]);

        $response->assertStatus(401);
    }

    public function testRequiresAuthenticationForGettingMessages(): void
    {
        $user = User::factory()->create();

        $response = $this->getJson(route('messages.show', $user));

        $response->assertStatus(401);
    }

    public function testValidatesMessageContent(): void
    {
        $sender = User::factory()->create(['email_verified_at' => now()]);
        $receiver = User::factory()->create(['email_verified_at' => now(), 'is_active' => true]);

        // Create friendship
        Friendship::factory()->create([
            'user_id' => $sender->id,
            'friend_id' => $receiver->id,
            'status' => FriendshipStatusEnum::Accepted
        ]);

        Sanctum::actingAs($sender);

        // Test empty content
        $response = $this->postJson(route('messages.send'), [
            'receiver_id' => $receiver->id,
            'content' => ''
        ]);

        $response->assertStatus(422);

        // Test too long content
        $response = $this->postJson(route('messages.send'), [
            'receiver_id' => $receiver->id,
            'content' => str_repeat('a', 1001)
        ]);

        $response->assertStatus(422);
    }

    public function testValidatesReceiverId(): void
    {
        $sender = User::factory()->create(['email_verified_at' => now()]);

        Sanctum::actingAs($sender);

        // Test missing receiver_id
        $response = $this->postJson(route('messages.send'), [
            'content' => 'Hello!'
        ]);

        $response->assertStatus(422);

        // Test non-existent receiver_id
        $response = $this->postJson(route('messages.send'), [
            'receiver_id' => 99999,
            'content' => 'Hello!'
        ]);

        $response->assertStatus(422);
    }
}
