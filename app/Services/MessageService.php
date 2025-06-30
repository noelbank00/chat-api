<?php

namespace App\Services;

use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class MessageService
{
    public function sendMessage(User $sender, User $receiver, string $content): Message
    {
        return Message::create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'content' => $content,
        ]);
    }

    public function getMessagesBetweenUsers(User $user1, User $user2): array
    {
        $messages = Message::query()
            ->where(function (Builder $builder) use ($user1, $user2) {
                $builder->where('sender_id', $user1->id)
                    ->where('receiver_id', $user2->id);
            })
            ->orWhere(function ($query) use ($user1, $user2) {
                $query->where('sender_id', $user2->id)
                    ->where('receiver_id', $user1->id);
            })
            ->with(['sender', 'receiver'])
            ->orderBy('created_at')
            ->get();

        return $messages->toArray();
    }

    public function markMessageAsRead(Message $message): void
    {
        $message->markAsRead();
    }
}
