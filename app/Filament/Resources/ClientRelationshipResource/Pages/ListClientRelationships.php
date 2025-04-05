<?php

namespace App\Filament\Resources\ClientRelationshipResource\Pages;

use Filament\Actions;
use App\Models\ClientRelationship;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\ListRecords\Tab;
use App\Filament\Resources\ClientRelationshipResource;

class ListClientRelationships extends ListRecords
{
    protected static string $resource = ClientRelationshipResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make()
                ->badge(ClientRelationship::query()->count())
                ->badgeColor('success'),

        ];
    }
}
