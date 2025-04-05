<?php

namespace App\Filament\Pages;


use Filament\Widgets\AccountWidget;
use App\Filament\Widgets\MonthlyPar;
use App\Filament\Widgets\ClientArrears;

use App\Filament\Widgets\TotalParChart;
use App\Filament\Widgets\BranchParChart;
use App\Filament\Widgets\RepaymentChart;
use App\Filament\Widgets\LoanStatusChart;
use App\Filament\Widgets\AllLoansOverview;
use App\Filament\Widgets\LoanMonthlyTarget;
use App\Filament\Widgets\BranchLoansOverview;
use App\Filament\Widgets\RepaymentTransaction;
use App\Filament\Widgets\LoanDisbursementChart;
use App\Filament\Widgets\DailyRepaymentSchedule;
use App\Filament\Widgets\TransactionMultiWidget;
use App\Filament\Widgets\LoanDailyCollectionsChart;



class BranchDashboard extends \Filament\Pages\Dashboard
{
    protected static string $routePath = 'branch';
    protected static ?string $title = 'Branch dashboard';
    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static ?int $navigationSort = -2;
    protected static bool $shouldRegisterNavigation = false;
    public function getWidgets(): array
    {
       return [
           //AccountWidget::class,
           BranchLoansOverview::class,
           BranchParChart::class,
           LoanStatusChart::class,
           LoanDailyCollectionsChart::class,
           LoanMonthlyTarget::class,
           LoanDisbursementChart::class,
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
