<?php

namespace App\Filament\Resources\LoanProductResource\Pages;

use Filament\Actions;
use App\Models\Loan\LoanProduct;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\ListRecords\Tab;
use App\Filament\Resources\LoanProductResource;

class ListLoanProducts extends ListRecords
{
    protected static string $resource = LoanProductResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make()
                ->badge(LoanProduct::query()->count())
                ->badgeColor('success'),
            'Active' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('active', 1))
                ->badge(LoanProduct::query()->where('active', 1)->count())
                ->badgeColor('success'),

            'Inactive' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('active', 0))
                ->badge(LoanProduct::query()->where('active', 0)->count())
                ->badgeColor('gray'),


        ];

    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
