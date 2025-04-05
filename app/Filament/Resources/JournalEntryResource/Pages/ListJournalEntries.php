<?php

namespace App\Filament\Resources\JournalEntryResource\Pages;

use Filament\Actions;
use App\Models\JournalEntry;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Filament\Resources\Pages\ListRecords\Tab;
use App\Filament\Resources\JournalEntryResource;

class ListJournalEntries extends ListRecords
{
    protected static string $resource = JournalEntryResource::class;

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
                ->badge(JournalEntry::query()->count())
                ->badgeColor('success'),
            'Asset' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('chart_of_account_id', 1))
                ->badge(JournalEntry::query()->where('chart_of_account_id', 1)->count())
                ->badgeColor('info'),
            'Expense' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('chart_of_account_id', 2))
                ->badge(JournalEntry::query()->where('chart_of_account_id', 2)->count())
                ->badgeColor('success'),
            'Income' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('chart_of_account_id', 3))
                ->badge(JournalEntry::query()->where('chart_of_account_id', 3)->count())
                ->badgeColor('success'),
            'Liability' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('chart_of_account_id', 4))
                ->badge(JournalEntry::query()->where('chart_of_account_id', 4)->count())
                ->badgeColor('warning'),
            'Equity' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('chart_of_account_id', 5))
                ->badge(JournalEntry::query()->where('chart_of_account_id', 5)->count())
                ->badgeColor('danger'),

        ];
    }
}
