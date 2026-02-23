<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use App\Models\Queue;
use App\Models\User;
use App\Models\ServiceCategory;
use App\Policies\QueuePolicy;
use App\Policies\UserPolicy;
use App\Policies\ServiceCategoryPolicy;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Queue::class => QueuePolicy::class,
        User::class => UserPolicy::class,
        ServiceCategory::class => ServiceCategoryPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
