<?php

namespace App\Filament\Resources\LoanTransactionResource\Pages;

use App\Filament\Resources\LoanTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLoanTransactions extends ListRecords
{
    protected static string $resource = LoanTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //Actions\CreateAction::make(),
        ];
    }
}
