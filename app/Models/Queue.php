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

    public const CLIENT_TYPE_STUDENT = 'student';
    public const CLIENT_TYPE_PARENT = 'parent';
    public const CLIENT_TYPE_VISITOR = 'visitor';
    public const CLIENT_TYPE_SENIOR_CITIZEN = 'senior_citizen';
    public const CLIENT_TYPE_HIGH_PRIORITY = 'high_priority';

    public const PRIORITY_CLIENT_TYPES = [
        self::CLIENT_TYPE_SENIOR_CITIZEN,
        self::CLIENT_TYPE_HIGH_PRIORITY,
    ];

    protected $fillable = [
        'queue_number',
        'service_category_id',
        'status',
        'skip_count',
        'is_reinstated',
        'cashier_window_id',
        'start_time',
        'end_time',
        'client_name',
        'client_type',
        'phone',
        'note',
    ];

    protected $casts = [
        'skip_count' => 'integer',
        'is_reinstated' => 'boolean',
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

    public function isPriorityClientType(): bool
    {
        return in_array($this->client_type, self::PRIORITY_CLIENT_TYPES, true);
    }
}
