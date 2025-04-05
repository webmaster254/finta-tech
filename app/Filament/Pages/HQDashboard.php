<?php

namespace App\Filament\Pages;


use Filament\Widgets\AccountWidget;
use App\Filament\Widgets\MonthlyPar;
use App\Filament\Widgets\ClientArrears;

use App\Filament\Widgets\TotalParChart;
use App\Filament\Widgets\RepaymentChart;
use App\Filament\Widgets\LoanStatusChart;
use App\Filament\Widgets\AllLoansOverview;
use App\Filament\Widgets\LoanMonthlyTarget;
use App\Filament\Widgets\RepaymentTransaction;
use App\Filament\Widgets\LoanDisbursementChart;
use App\Filament\Widgets\DailyRepaymentSchedule;
use App\Filament\Widgets\TransactionMultiWidget;
use App\Filament\Widgets\LoanDailyCollectionsChart;



class HQDashboard extends \Filament\Pages\Dashboard
{
    protected static string $routePath = 'all';
    protected static ?string $title = 'HQ dashboard';
    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?int $navigationSort = -1;
    protected static bool $shouldRegisterNavigation = false;
    public function getWidgets(): array
    {
       return [
           AccountWidget::class,
           AllLoansOverview::class,
           TotalParChart::class,
           LoanStatusChart::class,
          // LoanDailyCollectionsChart::class,
           //LoanMonthlyTarget::class,
           //LoanDisbursementChart::class,
        //    MonthlyPar::class,
        //    DailyRepaymentSchedule::class,
        //    ClientArrears::class,

        //    RepaymentChart::class,

       ];
    }

    public static function canAccess(): bool
        {
            return  auth()->user()->isAdmin();
        }
}
