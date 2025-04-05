<?php

namespace App\Filament\Resources\LoanResource\Pages;

use Filament\Actions;
use App\Models\Loan\Loan;
use App\Filament\Resources\LoanResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Filament\Resources\Pages\ListRecords\Tab;

class ListLoans extends ListRecords
{
    protected static string $resource = LoanResource::class;

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
                ->badge(Loan::query()->count())
                ->badgeColor('success'),
            'Active' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'active'))
                ->badge(Loan::query()->where('status', 'active')->count())
                ->badgeColor('success'),
            'Pending' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending'))
                ->badge(Loan::query()->where('status', 'pending')->count())
                ->badgeColor('info'),
            'Approved' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'approved'))
                ->badge(Loan::query()->where('status', 'approved')->count())
                ->badgeColor('success'),


            'Inactive' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'inactive'))
                ->badge(Loan::query()->where('status', 'inactive')->count())
                ->badgeColor('gray'),

            'Closed' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'closed'))
                ->badge(Loan::query()->where('status', 'closed')->count())
                ->badgeColor('danger'),

            'Rejected' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'rejected'))
                ->badge(Loan::query()->where('status', 'rejected')->count())
                ->badgeColor('danger'),

            'Withdrawn' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'withdrawn'))
                ->badge(Loan::query()->where('status', 'withdrawn')->count())
                ->badgeColor('gray'),

            'Resheduled' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'rescheduled'))
                ->badge(Loan::query()->where('status', 'rescheduled')->count())
                ->badgeColor('gray'),

            'Written-Off' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'written_off'))
                ->badge(Loan::query()->where('status', 'written_off')->count())
                ->badgeColor('danger'),

        ];
    }
}
