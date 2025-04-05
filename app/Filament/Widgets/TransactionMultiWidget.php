<?php

namespace App\Filament\Widgets;

use Kenepa\MultiWidget\MultiWidget;
use App\Filament\Widgets\ClientArrears;
use App\Filament\Widgets\LoanMonthlyTarget;
use App\Filament\Widgets\RepaymentTransaction;
use App\Filament\Widgets\DailyRepaymentSchedule;
use App\Filament\Widgets\LoanDailyCollectionsChart;

class TransactionMultiWidget extends MultiWidget
{
    public array $widgets = [




    ];

    public function shouldPersistMultiWidgetTabsInSession(): bool
    {
        return true;
    }
}
