<?php

namespace App\Filament\Resources\EmploymentResource\Pages;

use App\Filament\Resources\EmploymentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEmployment extends EditRecord
{
    protected static string $resource = EmploymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
