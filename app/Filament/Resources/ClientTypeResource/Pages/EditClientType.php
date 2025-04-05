<?php

namespace App\Filament\Resources\ClientTypeResource\Pages;

use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\ClientTypeResource;

class EditClientType extends EditRecord
{
    protected static string $resource = ClientTypeResource::class;



    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Client Type updated')
            ->body('The Client Type has been saved successfully.');
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
