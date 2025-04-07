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

class ListApprovedLoans extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $view = 'filament.pages.list-approved-loans';

    protected static ?string $navigationGroup = 'Loans Management';
    protected static ?string $navigationLabel = 'Approve loans';
    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        return  Loan::where('status', 'approved')->count();
    }
    public static function table(Table $table): Table
    {
        return $table
            ->query(Loan::query()->where('status', 'approved'))
            ->columns([
                TextColumn::make('loan_account_number')
                        ->label('Loan Account No')
                        ->sortable(),
                TextColumn::make('loan_officer.full_name')
                        ->sortable(),
                TextColumn::make('client.full_name')
                        ->sortable()
                        ->searchable(['first_name', 'middle_name', 'last_name']),
                TextColumn::make('approved_amount')
                        ->label('Approved Amount')
                        ->money('KES')
                        ->sortable()
                        ->summarize(Sum::make()
                                ->label('Total Approved Amount')
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
                Action::make('disburse')
                    ->label('Disburse')
                    ->icon('heroicon-o-check')
                    ->requiresConfirmation()
                    ->action(function (Loan $record) {
                        $record->status = 'active';
                        $record->save();
                    }),
                Action::make('undisburse')
                    ->label('Undisburse')
                    ->icon('heroicon-o-x-circle')
                    ->requiresConfirmation()
                    ->action(function (Loan $record) {
                        $record->status = 'appproved';
                        $record->save();
                    }),
            ]);
    }
}
