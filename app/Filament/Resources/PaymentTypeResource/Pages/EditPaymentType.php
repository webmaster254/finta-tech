<?php

namespace App\Filament\Resources\PaymentTypeResource\Pages;

use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\PaymentTypeResource;

class EditPaymentType extends EditRecord
{
    protected static string $resource = PaymentTypeResource::class;


    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Payment Type updated')
            ->body('The payment type has been saved successfully.');
    }  
    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
