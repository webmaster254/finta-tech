<?php

namespace App\Filament\Resources\BusinessResource\Pages;

use Filament\Actions;
use Filament\Forms\Components\Wizard\Step;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\BusinessResource;

class CreateBusiness extends CreateRecord
{
    //use CreateRecord\Concerns\HasWizard;
    protected static string $resource = BusinessResource::class;

    protected function getRedirectUrl(): string
{
    return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
}

// protected function getSteps(): array
//     {
//         return [
//             Step::make('General Business Information')
//                 ->schema(BusinessResource::getGeneralBusinessInformation()),
//             // Step::make('Business overview Information')
//             //     ->schema(BusinessResource::getBusinessOverviewInformation()),
//         ];
//     }
}
