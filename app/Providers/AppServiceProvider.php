<?php

namespace App\Providers;

use App\Domain\Tenancy\WorkspaceContext;
use App\Models\Workspace;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;
use Laravel\Fortify\Fortify;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(WorkspaceContext::class);
        Cashier::ignoreRoutes();
        Fortify::ignoreRoutes();
    }

    public function boot(): void
    {
        Cashier::useCustomerModel(Workspace::class);
    }
}
