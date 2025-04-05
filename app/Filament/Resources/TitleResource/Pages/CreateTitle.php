<?php

namespace App\Filament\Resources\TitleResource\Pages;

use Filament\Actions;
use Filament\Notifications\Notification;
use App\Filament\Resources\TitleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTitle extends CreateRecord
{
    protected static string $resource = TitleResource::class;
    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Title Created successfully';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

