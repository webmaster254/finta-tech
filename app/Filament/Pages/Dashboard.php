<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\ParChart;
use Filament\Widgets\AccountWidget;
use App\Filament\Widgets\MonthlyPar;
use App\Filament\Widgets\ClientArrears;
use App\Filament\Widgets\LoansOverview;
use App\Filament\Widgets\RepaymentChart;
use App\Filament\Widgets\LoanStatusChart;
use App\Filament\Widgets\LoanMonthlyTarget;
use App\Filament\Widgets\RepaymentTransaction;
use App\Filament\Widgets\LoanDisbursementChart;
use App\Filament\Widgets\DailyRepaymentSchedule;
use App\Filament\Widgets\TransactionMultiWidget;
use App\Filament\Widgets\LoanDailyCollectionsChart;

class Dashboard extends \Filament\Pages\Dashboard
{
   protected static ?int $navigationSort = -3;
    public function getWidgets(): array
    {
       return [
           AccountWidget::class,
           \TomatoPHP\FilamentNotes\Filament\Widgets\NotesWidget::class,
           LoansOverview::class,
           ParChart::class,
           LoanStatusChart::class,
           LoanDailyCollectionsChart::class,
           LoanMonthlyTarget::class,
           LoanDisbursementChart::class,
           MonthlyPar::class,
           DailyRepaymentSchedule::class,
           ClientArrears::class,
           //RepaymentTransaction::class,
           RepaymentChart::class,

       ];
    }
}
