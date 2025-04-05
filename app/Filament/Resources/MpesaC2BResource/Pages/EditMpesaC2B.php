<?php

namespace App\Filament\Resources\MpesaC2BResource\Pages;

use App\Filament\Resources\MpesaC2BResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMpesaC2B extends EditRecord
{
    protected static string $resource = MpesaC2BResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //Actions\DeleteAction::make(),
        ];
    }
}
