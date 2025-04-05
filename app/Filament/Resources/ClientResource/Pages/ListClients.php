<?php

namespace App\Filament\Resources\ClientResource\Pages;

use Filament\Actions;
use App\Models\Client;
use Filament\Actions\ExportAction;
use App\Filament\Exports\ClientExporter;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\ClientResource;
use Filament\Resources\Pages\ListRecords\Tab;

class ListClients extends ListRecords
{
    protected static string $resource = ClientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            
            Actions\CreateAction::make()
                ->icon("heroicon-o-plus"),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make()
                ->badge(Client::query()->count()),

            'Active' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'active'))
                ->badge(Client::query()->where('status', 'active')->count())
                ->badgeColor('success'),
            'Pending' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending'))
                ->badge(Client::query()->where('status', 'pending')->count())
                ->badgeColor('info'),

            'Inactive' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'inactive'))
                ->badge(Client::query()->where('status', 'inactive')->count())
                ->badgeColor('gray'),

            'Closed' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'closed'))
                ->badge(Client::query()->where('status', 'closed')->count())
                ->badgeColor('danger'),

            'Suspended' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'suspended'))
                ->badge(Client::query()->where('status', 'suspended')->count())
                ->badgeColor('danger'),

            'Deceased' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'deceased'))
                ->badge(Client::query()->where('status', 'deceased')->count())
                ->badgeColor('gray')

        ];
    }
}
