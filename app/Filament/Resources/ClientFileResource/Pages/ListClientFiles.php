<?php

namespace App\Filament\Resources\ClientFileResource\Pages;

use App\Filament\Resources\ClientFileResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListClientFiles extends ListRecords
{
    protected static string $resource = ClientFileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
