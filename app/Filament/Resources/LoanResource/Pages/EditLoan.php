<?php

namespace App\Filament\Resources\LoanResource\Pages;

use Filament\Actions;
use App\Filament\Resources\LoanResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditLoan extends EditRecord
{
    protected static string $resource = LoanResource::class;
    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Loan Updated')
            ->body('The Loan has been saved successfully.');
    } 

    protected function mutateFormDataBeforeSave(array $data): array
    {
        //check if status is submitted and update to pending
        if($this->getRecord()->status == 'submitted') {
            $data['status'] = 'pending';
        }
        return $data;
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
