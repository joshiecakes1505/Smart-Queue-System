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
    ];

    public function queues()
    {
        return $this->hasMany(Queue::class);
    }
}
