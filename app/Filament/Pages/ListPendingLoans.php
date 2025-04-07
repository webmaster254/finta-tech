<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Loan\Loan;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use App\Filament\Exports\LoanExporter;
use App\Filament\Imports\LoanImporter;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Actions\ExportAction;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Columns\Summarizers\Summarizer;

class ListPendingLoans extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $view = 'filament.pages.list-pending-loans';

    protected static ?string $navigationGroup = 'Loans Management';
    protected static ?string $navigationLabel = 'Approve loans';
    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        return  Loan::where('status', 'pending')->count();
    }
    public static function table(Table $table): Table
    {
        return $table
            ->query(Loan::query()->where('status', 'pending'))
            ->columns([
                TextColumn::make('loan_account_number')
                        ->label('Loan Account No')
                        ->sortable(),
                TextColumn::make('loan_officer.full_name')
                        ->sortable(),
                TextColumn::make('client.full_name')
                        ->sortable()
                        ->searchable(['first_name', 'middle_name', 'last_name']),
                TextColumn::make('applied_amount')
                        ->label('Applied Amount')
                        ->money('KES')
                        ->sortable()
                        ->summarize(Sum::make()
                                ->label('Total Applied Amount')
                                ->money('KES'))
                        ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('loan_product.name')
                        ->label('Loan Product')
                        ->sortable(),
                TextColumn::make('client.account_number')
                        ->label('Account Number')
                        ->sortable()
                        ->searchable(),
                TextColumn::make('client.mobile')
                        ->label('Phone Number')
                        ->searchable(),
                TextColumn::make('status')
                        ->badge()
                        ->searchable(),
                TextColumn::make('created_at')
                        ->dateTime()
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),
                ])->striped()
                    ->defaultSort('created_at', 'desc')
                ->filters([
                    
    
                ])
            ->headerActions([])
            ->actions([
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check')
                    ->action(function (Loan $record) {
                        $record->status = 'approved';
                        $record->save();
                    }),
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->action(function (Loan $record) {
                        $record->status = 'rejected';
                        $record->save();
                    }),
            ]);
    }
}
