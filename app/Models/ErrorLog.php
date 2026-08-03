<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ErrorLog extends Model
{
    protected $fillable = [
        'level',
        'exception_class',
        'message',
        'file',
        'line',
        'trace',
        'url',
        'method',
        'user_id',
        'context',
        'occurrences',
        'last_occurred_at',
        'resolved_at',
        'resolved_by',
    ];

    protected $casts = [
        'context' => 'array',
        'occurrences' => 'integer',
        'last_occurred_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function markResolved(?int $byUserId): void
    {
        $this->forceFill([
            'resolved_at' => now(),
            'resolved_by' => $byUserId,
        ])->save();
    }

    public function markUnresolved(): void
    {
        $this->forceFill([
            'resolved_at' => null,
            'resolved_by' => null,
        ])->save();
    }
}
