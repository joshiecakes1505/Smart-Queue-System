<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DisplayQueuesUpdated implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public ?string $reason = null,
        public ?int $queueId = null,
    ) {
    }

    public function broadcastOn(): array
    {
        return [new Channel('display.queues')];
    }

    public function broadcastAs(): string
    {
        return 'queues.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'reason' => $this->reason,
            'queue_id' => $this->queueId,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}