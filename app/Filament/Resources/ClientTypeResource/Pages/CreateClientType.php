<?php

namespace App\Filament\Resources\ClientTypeResource\Pages;

use App\Filament\Resources\ClientTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateClientType extends CreateRecord
{
    protected static string $resource = ClientTypeResource::class;

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Client Type Created successfully';
    }

    protected function getRedirectUrl(): string
{
    return $this->getResource()::getUrl('index');
}
}
