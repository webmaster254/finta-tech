<?php

namespace App\Filament\Resources\LoanCollateralTypeResource\Pages;

use App\Filament\Resources\LoanCollateralTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLoanCollateralType extends EditRecord
{
    protected static string $resource = LoanCollateralTypeResource::class;

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Collateral Type updated';
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
