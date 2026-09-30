<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // 'App\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Gate::before(function ($user, string $ability, array $arguments = []) {
            if (! $user instanceof \App\Models\User) {
                return null;
            }

            $permissionService = app(\App\Services\PermissionService::class);

            if (! $permissionService->permissionExists($ability, 'web')) {
                return null;
            }

            return $permissionService->userHasPermission($user, $ability)
                ? true
                : null;
        });
    }
}
