<?php

namespace App\Filament\Resources\LoanCollateralTypeResource\Pages;

use App\Filament\Resources\LoanCollateralTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLoanCollateralTypes extends ListRecords
{
    protected static string $resource = LoanCollateralTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
