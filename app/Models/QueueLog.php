<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QueueLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'queue_id',
        'action',
        'performed_by',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function queue()
    {
        return $this->belongsTo(Queue::class);
    }

    public function performer()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
