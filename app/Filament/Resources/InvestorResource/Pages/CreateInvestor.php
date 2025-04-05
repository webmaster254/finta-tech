<?php

namespace App\Filament\Resources\InvestorResource\Pages;

use App\Filament\Resources\InvestorResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateInvestor extends CreateRecord
{
    protected static string $resource = InvestorResource::class;

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Investor Created successfully';
    }


    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
