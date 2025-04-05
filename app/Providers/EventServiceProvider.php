<?php

namespace App\Providers;

use App\Models\User;

use App\Models\Client;
use App\Events\LoanDisbursed;
use App\Events\LoanRepayment;
use App\Events\InvestmentMade;
use App\Events\LoanChargeWaive;
use App\Events\LoanUndisbursed;
use App\Observers\UserObserver;
use App\Listeners\UndoInvestment;
use App\Observers\ClientObserver;
use App\Events\InvestmentReversed;
use App\Listeners\WaiveLoanCharge;
use App\Listeners\UpdateTransaction;
use App\Listeners\UpdateLoanSchedule;
use Illuminate\Support\Facades\Event;
use App\Events\DefaultAccountsCreated;
use Illuminate\Auth\Events\Registered;
use App\Listeners\CreateDefaultAccounts;
use App\Listeners\CreateLoanLinkedCharge;
use App\Listeners\UndisburseLoanSchedule;
use App\Listeners\GeneratePaymentSchedule;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        LoanDisbursed::class => [
            UpdateLoanSchedule::class
        ],
        LoanUndisbursed::class => [
            UndisburseLoanSchedule::class
        ],
        LoanRepayment::class => [
            UpdateTransaction::class
        ],
        LoanChargeWaive::class => [
            WaiveLoanCharge::class
        ],
        InvestmentMade::class => [
            GeneratePaymentSchedule::class
        ],
        InvestmentReversed::class => [
            UndoInvestment::class
        ],
        DefaultAccountsCreated::class => [
            CreateDefaultAccounts::class,
        ],

    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        User::observe(UserObserver::class);
        Client::observe(ClientObserver::class);
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
