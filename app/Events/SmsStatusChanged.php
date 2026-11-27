<?php

declare(strict_types=1);

namespace App\Events;

use App\Sms\Enums\MessageStatus;
use Carbon\CarbonImmutable;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class SmsStatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public readonly string $id;

    public readonly CarbonImmutable $occurredAt;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly int $messageId,
        public readonly MessageStatus $status,
    ) {
        //
        $this->id = (string) Str::uuid();
        $this->occurredAt = CarbonImmutable::now();
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}
