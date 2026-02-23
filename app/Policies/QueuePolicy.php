<?php

namespace App\Policies;

use App\Models\Queue;
use App\Models\User;

class QueuePolicy
{
    public function callNext(User $user): bool
    {
        return $user->role?->name === 'cashier';
    }

    public function skip(User $user, Queue $queue): bool
    {
        return $user->role?->name === 'cashier';
    }

    public function recall(User $user, Queue $queue): bool
    {
        return $user->role?->name === 'cashier';
    }

    public function complete(User $user, Queue $queue): bool
    {
        return $user->role?->name === 'cashier';
    }
}
