<?php

namespace App\Filament\Resources\LoanCollateralTypeResource\Pages;

use App\Filament\Resources\LoanCollateralTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateLoanCollateralType extends CreateRecord
{
    protected static string $resource = LoanCollateralTypeResource::class;

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Collateral Type Created successfully';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
