<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    protected function getCreatedNotificationTitle(): ?string
    {
        return 'staff updated successfully';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
           $data['name'] = $data['first_name'] . ' ' . $data['middle_name'] . ' ' . $data['last_name'];

        return $data;
    }
}
