<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        'Spatie\Permission\Models\Role' => 'App\Policies\RolePolicy',
        'App\Models\Loan\Loan' => 'App\Policies\Loan\LoanPolicy',
        'App\Models\Loan\LoanCharge' => 'App\Policies\Loan\LoanChargePolicy',
        'App\Models\Loan\LoanProduct' => 'App\Policies\Loan\LoanProductPolicy',
        'App\Models\Loan\LoanCollateralType' => 'App\Policies\Loan\LoanCollateralTypePolicy',
        'Rappasoft\LaravelAuthenticationLog\Models\AuthenticationLog' => 'App\Policies\AuthenticationLogPolicy',
        'Spatie\Activitylog\Models\Activity' => 'App\Policies\ActivityPolicy',
        'BezhanSalleh\FilamentExceptions\Models\Exception' => 'App\Policies\ExceptionPolicy',
        'Awcodes\Curator\Models\Media' => 'App\Policies\MediaPolicy',
      'App\Models\Loan\LoanTransaction' =>'App\Policies\Loan\LoanTransactionPolicy',
        'Croustibat\FilamentJobsMonitor\Models\QueueMonitor' => 'App\Policies\QueueMonitorPolicy',


    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Implicitly grant "Super Admin" role all permissions
        // This works in the app by using gate-related functions like auth()->user->can() and @can()
        Gate::before(function ($user) {
            return $user->isSuperAdmin() ? true : null;
        });
    }
}
