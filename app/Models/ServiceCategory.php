<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'prefix',
        'description',
        'max_queues_per_day',
        'avg_service_seconds',
        'regulars_per_priority_cycle',
    ];

    protected $casts = [
        'max_queues_per_day' => 'integer',
        'avg_service_seconds' => 'integer',
        'regulars_per_priority_cycle' => 'integer',
    ];

    public function queues()
    {
        return $this->hasMany(Queue::class);
    }
}
