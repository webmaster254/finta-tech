<?php

namespace App\Filament\Resources\LoanChargeResource\Pages;

use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\LoanChargeResource;

class EditLoanCharge extends EditRecord
{
    protected static string $resource = LoanChargeResource::class;

    protected function getSavedNotification(): ?Notification
{
    return Notification::make()
        ->success()
        ->title('charge updated')
        ->body('The Charges has been saved successfully.');
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
