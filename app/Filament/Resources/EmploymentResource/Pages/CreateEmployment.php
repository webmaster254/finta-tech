<?php

namespace App\Filament\Resources\EmploymentResource\Pages;

use App\Filament\Resources\EmploymentResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateEmployment extends CreateRecord
{
    protected static string $resource = EmploymentResource::class;

    protected function getRedirectUrl(): string
{
    return $this->getResource()::getUrl('index');
}
}
