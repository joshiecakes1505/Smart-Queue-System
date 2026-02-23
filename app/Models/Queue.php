<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Queue extends Model
{
    use HasFactory;

    public const STATUS_WAITING = 'waiting';
    public const STATUS_CALLED = 'called';
    public const STATUS_SKIPPED = 'skipped';
    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'queue_number',
        'service_category_id',
        'status',
        'cashier_window_id',
        'start_time',
        'end_time',
        'client_name',
        'phone',
        'note',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    public function serviceCategory(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class);
    }

    public function cashierWindow(): BelongsTo
    {
        return $this->belongsTo(CashierWindow::class);
    }

    public function logs()
    {
        return $this->hasMany(QueueLog::class);
    }
}
