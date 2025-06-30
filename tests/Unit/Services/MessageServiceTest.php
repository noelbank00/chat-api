<?php

namespace Tests\Unit\Services;

use App\Models\Message;
use App\Models\User;
use App\Services\MessageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessageServiceTest extends TestCase
{
    use RefreshDatabase;

    private MessageService $messageService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->messageService = new MessageService();
    }

    public function testCanSendMessage(): void
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();
        $content = 'Test message content';

        $message = $this->messageService->sendMessage($sender, $receiver, $content);

        $this->assertInstanceOf(Message::class, $message);
        $this->assertEquals($sender->id, $message->sender_id);
        $this->assertEquals($receiver->id, $message->receiver_id);
        $this->assertEquals($content, $message->content);
        $this->assertNull($message->read_at);
    }

    public function testCanGetMessagesBetweenUsers(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        // Create messages between user1 and user2
        $message1 = Message::factory()->create([
            'sender_id' => $user1->id,
            'receiver_id' => $user2->id,
            'content' => 'Message 1'
        ]);

        $message2 = Message::factory()->create([
            'sender_id' => $user2->id,
            'receiver_id' => $user1->id,
            'content' => 'Message 2'
        ]);

        // Create message from user3 (should not be included)
        Message::factory()->create([
            'sender_id' => $user3->id,
            'receiver_id' => $user1->id,
            'content' => 'Message from user3'
        ]);

        $messages = $this->messageService->getMessagesBetweenUsers($user1, $user2);

        $this->assertCount(2, $messages);
        $this->assertEquals('Message 1', $messages[0]['content']);
        $this->assertEquals('Message 2', $messages[1]['content']);
    }


    public function testCanMarkMessageAsRead(): void
    {
        $message = Message::factory()->create(['read_at' => null]);

        $this->messageService->markMessageAsRead($message);

        $message->refresh();
        $this->assertNotNull($message->read_at);
    }
}
