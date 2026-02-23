<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashierWindow extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'assigned_user_id',
        'active',
    ];

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function queues()
    {
        return $this->hasMany(Queue::class);
    }
}
