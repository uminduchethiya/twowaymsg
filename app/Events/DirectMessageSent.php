<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DirectMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Message $message)
    {
        $this->message->loadMissing('user');
    }

    public function broadcastOn(): array
    {
        // Broadcast to BOTH participants so each receives it in real-time
        return [
            new PrivateChannel('dm.' . $this->dmChannelKey($this->message->user_id, $this->message->receiver_id)),
        ];
    }

    public function broadcastAs(): string
    {
        return 'dm.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'id'          => $this->message->id,
            'body'        => $this->message->body,
            'sender_id'   => $this->message->user_id,
            'receiver_id' => $this->message->receiver_id,
            'created_at'  => $this->message->created_at->toISOString(),
            'user'        => [
                'id'   => $this->message->user->id,
                'name' => $this->message->user->name,
            ],
        ];
    }

    /**
     * Canonical channel key: always smaller ID first so both users share one channel.
     */
    public static function dmChannelKey(int $a, int $b): string
    {
        return min($a, $b) . '.' . max($a, $b);
    }
}
