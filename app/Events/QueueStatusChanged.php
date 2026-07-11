<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QueueStatusChanged implements ShouldBroadcastNow
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public int $queueId,
        public string $status,
    ) {
    }

    public function broadcastOn(): array
    {
        return [new Channel('queues')];
    }

    public function broadcastAs(): string
    {
        return 'queue.status.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'queue_id' => $this->queueId,
            'status' => $this->status,
        ];
    }
}