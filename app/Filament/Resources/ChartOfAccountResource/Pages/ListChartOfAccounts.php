<?php

namespace App\Filament\Resources\ChartOfAccountResource\Pages;

use Filament\Actions;
use App\Models\ChartOfAccount;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\ListRecords\Tab;
use App\Filament\Resources\ChartOfAccountResource;

class ListChartOfAccounts extends ListRecords
{
    protected static string $resource = ChartOfAccountResource::class;

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
                ->badge(ChartOfAccount::query()->count())
                ->badgeColor('success'),
        ];
    }
}
