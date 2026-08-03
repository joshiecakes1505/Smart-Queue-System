<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Sanctum\HasApiTokens;

use App\Models\Role;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'disabled_at' => 'datetime',
            'archived_at' => 'datetime',
            'last_login_at' => 'datetime',
            'deleted_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function disable(): void
    {
        $this->forceFill([
            'disabled_at' => now(),
        ])->save();
    }

    public function enable(): void
    {
        $this->forceFill([
            'disabled_at' => null,
            'archived_at' => null,
        ])->save();
    }

    public function archive(): void
    {
        $this->forceFill([
            'archived_at' => now(),
        ])->save();
    }

    public function unarchive(): void
    {
        $this->forceFill([
            'archived_at' => null,
        ])->save();
    }

    public function recordLogin(): void
    {
        $this->forceFill([
            'last_login_at' => now(),
        ])->save();
    }

    /**
     * Number of admin-role accounts that are currently active (not disabled).
     */
    public static function activeAdminCount(?int $excludingUserId = null): int
    {
        return static::query()
            ->whereHas('role', fn ($q) => $q->where('name', 'admin'))
            ->whereNull('disabled_at')
            ->when($excludingUserId, fn ($q) => $q->where('id', '!=', $excludingUserId))
            ->count();
    }

    /**
     * Whether this account is the last remaining active admin, so disabling
     * (or archiving/deleting) it would lock the system out of its own admin panel.
     */
    public function isSoleActiveAdmin(): bool
    {
        if (optional($this->role)->name !== 'admin' || $this->disabled_at !== null) {
            return false;
        }

        return static::activeAdminCount(excludingUserId: $this->id) === 0;
    }
}
