<?php

namespace App\Filament\Resources\ClientFileResource\Pages;

use App\Filament\Resources\ClientFileResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditClientFile extends EditRecord
{
    protected static string $resource = ClientFileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
