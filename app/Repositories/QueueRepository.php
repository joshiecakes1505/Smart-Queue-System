<?php

namespace App\Repositories;

use App\Models\Queue;

class QueueRepository
{
    public function create(array $data): Queue
    {
        return Queue::create($data);
    }

    public function findNextWaiting(?int $serviceCategoryId = null): ?Queue
    {
        $query = Queue::where('status', Queue::STATUS_WAITING)
            ->orderBy('created_at', 'asc');

        if ($serviceCategoryId) {
            $query->where('service_category_id', $serviceCategoryId);
        }

        return $query->first();
    }

    public function getById(int $id): ?Queue
    {
        return Queue::find($id);
    }

    public function update(Queue $queue, array $attributes): Queue
    {
        $queue->fill($attributes);
        $queue->save();
        return $queue;
    }
}
