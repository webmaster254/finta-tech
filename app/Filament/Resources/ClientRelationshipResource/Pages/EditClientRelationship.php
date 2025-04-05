<?php

namespace App\Filament\Resources\ClientRelationshipResource\Pages;

use App\Filament\Resources\ClientRelationshipResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditClientRelationship extends EditRecord
{
    protected static string $resource = ClientRelationshipResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
