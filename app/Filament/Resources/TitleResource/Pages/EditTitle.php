<?php

namespace App\Filament\Resources\TitleResource\Pages;

use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\TitleResource;

class EditTitle extends EditRecord
{
    protected static string $resource = TitleResource::class;

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Title updated';
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
