<?php

namespace App\Filament\Pages;

use App\Models\Loan\Loan;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Pages\Actions\CreateAction;
use App\Filament\Resources\LoanResource;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;

class ListSubmittedLoans extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $view = 'filament.pages.list-submitted-loans';

    protected static ?string $navigationLabel = 'Loan Maintenance';
    protected static ?string $navigationGroup = 'Loans Management';
    protected  ?string $heading = 'Submitted Loans';
    protected static ?int $navigationSort = 1; 

    public static function getNavigationBadge(): ?string
    {
        return Loan::where('status', 'submitted')->count();
    }

    protected function getHeaderActions(): array
    {
        return [
            
            CreateAction::make()
                ->icon("heroicon-o-plus")
                ->label('Create Loan')
                ->url(fn (): string => LoanResource::getUrl('create')),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Loan::query()->where('status', 'submitted'))
            ->columns([
                TextColumn::make('loan_account_number')
                        ->label('Loan Account No')
                        ->sortable(),
                TextColumn::make('loan_officer.full_name')
                        ->label('Relationship Officer'),
                TextColumn::make('client.full_name')
                        ->sortable()
                        ->label('Client Name'),
                TextColumn::make('status')
                        ->badge()
                        ->label('Status'),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('edit')
                    ->label('Edit Client')
                    ->icon('heroicon-o-pencil')
                     ->url(fn (Loan $record): string => LoanResource::getUrl('edit', ['record' => $record]))
                    
                    ->color('success')   
                    ]),
            ]);
    }

}
