<?php

namespace App\Filament\App\Pages;

use Filament\Widgets\AccountWidget;
use App\Filament\App\Widgets\ParChart;
use App\Filament\App\Widgets\MonthlyPar;
use App\Filament\App\Widgets\LoansOverview;
use App\Filament\App\Widgets\MpesaPayments;
use App\Filament\App\Widgets\ClientOfficersArrears;
use App\Filament\App\Widgets\DailyRepaymentSchedule;


class Dashboard extends \Filament\Pages\Dashboard
{
    public function getWidgets(): array
    {
       return [
        AccountWidget::class,
        LoansOverview::class,
        ParChart::class,
        MonthlyPar::class,
        DailyRepaymentSchedule::class,
        ClientOfficersArrears::class,
        MpesaPayments::class

       ];
    }
}
