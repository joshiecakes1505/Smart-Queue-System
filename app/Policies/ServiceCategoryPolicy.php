<?php

namespace App\Policies;

use App\Models\ServiceCategory;
use App\Models\User;

class ServiceCategoryPolicy
{
    public function create(User $user): bool
    {
        return $user->role?->name === 'admin';
    }

    public function update(User $user, ServiceCategory $model): bool
    {
        return $user->role?->name === 'admin';
    }

    public function delete(User $user, ServiceCategory $model): bool
    {
        return $user->role?->name === 'admin';
    }
}
