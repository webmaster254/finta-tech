<?php

namespace App\Filament\Resources\LoanChargeResource\Pages;

use App\Filament\Resources\LoanChargeResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateLoanCharge extends CreateRecord
{
    protected static string $resource = LoanChargeResource::class;
    

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Loan Charge Created successfully';
    }
    protected function getRedirectUrl(): string
{
    return $this->getResource()::getUrl('index');
}
}
