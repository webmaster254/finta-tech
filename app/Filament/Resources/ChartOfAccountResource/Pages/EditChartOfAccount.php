<?php

namespace App\Filament\Resources\ChartOfAccountResource\Pages;

use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\ChartOfAccountResource;

class EditChartOfAccount extends EditRecord
{
    protected static string $resource = ChartOfAccountResource::class;
    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Chart of Account updated')
            ->body('The Account has been saved successfully.');
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
