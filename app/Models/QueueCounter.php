<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QueueCounter extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'last_number',
        'service_category_id',
        'last_assigned_window_id',
        'regular_served_in_cycle',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function serviceCategory()
    {
        return $this->belongsTo(ServiceCategory::class);
    }
}
