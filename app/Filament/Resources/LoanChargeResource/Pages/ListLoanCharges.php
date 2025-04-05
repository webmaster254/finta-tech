<?php

namespace App\Filament\Resources\LoanChargeResource\Pages;

use App\Filament\Resources\LoanChargeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLoanCharges extends ListRecords
{
    protected static string $resource = LoanChargeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
