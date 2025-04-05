<?php

namespace App\Filament\Resources\MpesaC2BResource\Pages;

use App\Filament\Resources\MpesaC2BResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMpesaC2BS extends ListRecords
{
    protected static string $resource = MpesaC2BResource::class;

    protected function getHeaderActions(): array
    {
        return [
           // Actions\CreateAction::make(),
        ];
    }
}
