<?php

namespace App\Filament\Resources\LoanResource\Pages;

use App\Models\Loan\Loan;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Resources\Pages\Page;
use App\Filament\Exports\LoanExporter;
use App\Filament\Imports\LoanImporter;
use App\Filament\Resources\LoanResource;
use Filament\Tables\Actions\ExportAction;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\Summarizers\Summarizer;

class ListPendingLoans extends Page
{
    protected static string $resource = LoanResource::class;

    protected static string $view = 'filament.resources.loan-resource.pages.list-pending-loans';

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending'))
            ->columns([
            Tables\Columns\TextColumn::make('loan_account_number')
                        ->label('Loan Account No')
                        ->sortable(),
                    Tables\Columns\TextColumn::make('loan_officer.full_name')
                        ->sortable(),
                    Tables\Columns\TextColumn::make('client.full_name')
                        ->sortable()
                        ->searchable(['first_name', 'middle_name', 'last_name']),
                    Tables\Columns\TextColumn::make('approved_amount')
                        ->label('Approved Amount')
                        ->money('KES')
                        ->sortable()
                        ->summarize(Sum::make()
                                ->label('Total Approved Amount')
                                ->money('KES'))
                        ->toggleable(isToggledHiddenByDefault: true),
                    Tables\Columns\TextColumn::make('principal_disbursed_derived')
                        ->label('Principal Amount')
                        ->money('KES')
                        ->sortable()
                        ->summarize(Sum::make()
                                ->label('Total Disbursement')
                                ->money('KES')),
                    Tables\Columns\TextColumn::make('interest_disbursed_derived')
                        ->label('Interest Amount')
                        ->money('KES')
                        ->sortable()
                        ->getStateUsing(fn (Loan $record) => $record->getInterestDisbursed($record->id))
                        ->summarize(Sum::make()
                                ->label('Total Interest Disbursed')
                                ->money('KES')),
                    Tables\Columns\TextColumn::make('repayment_schedules.interest_repaid_derived')
                        ->label('Interest Paid')
                        ->money('KES')
                        ->sortable()
                        ->getStateUsing(fn (Loan $record) => $record->getInterestPaid($record->id))
                        ->summarize(Sum::make()
                                ->label('Total Interest')
                                ->money('KES')),
                    Tables\Columns\TextColumn::make('repayment_schedules.total_due')
                        ->label('Balance')
                        ->getStateUsing(fn (Loan $record) => $record->getBalance($record->id))
                        ->money('KES')
                        ->summarize(Sum::make()
                                ->label('Total Balance')
                                ->money('KES'))
                        ->sortable(),
                    Tables\Columns\TextColumn::make('arrears')
                        ->label('Arrears')
                        ->getStateUsing(fn (Loan $record) => $record->getAmountDue($record->id))
                        ->money('KES')
                        ->summarize(Summarizer::make()
                                    ->label('Total Arrears')
                                    ->money('KES')
                                    ->using(function ($query) {
                                        return $query->get()->sum(function ($record) {
                                            // Ensure we're working with a Loan model instance
                                            if ($record instanceof \stdClass) {
                                                $record = Loan::find($record->id);
                                            }
                                            return $record ? $record->getAmountDue($record->id) : 0;
                                        });
                                    })
                                )
                        ->sortable(),
                    Tables\Columns\TextColumn::make('arrears_days')
                        ->label(' Days In Arrears')
                        ->getStateUsing(fn (Loan $record) => $record->getDaysInArrears($record->id)),
                    Tables\Columns\TextColumn::make('loan_product.name')
                        ->label('Loan Product')
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),
                    Tables\Columns\TextColumn::make('loan_product.name')
                        ->label('Loan Product')
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),
                    Tables\Columns\TextColumn::make('disbursed_on_date')
                        ->label('Disbursement Date')
                        ->date()
                        ->sortable(),
                    Tables\Columns\TextColumn::make('client.account_number')
                        ->label('Account Number')
                        ->sortable()
                        ->searchable()
                        ->toggleable(isToggledHiddenByDefault: true),
                    Tables\Columns\TextColumn::make('client.mobile')
                        ->label('Phone Number')
                        ->searchable()
                        ->toggleable(isToggledHiddenByDefault: true),
                    Tables\Columns\TextColumn::make('status')
                        ->badge()
                        ->searchable(),
                    Tables\Columns\TextColumn::make('created_at')
                        ->dateTime()
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),
                    Tables\Columns\TextColumn::make('updated_at')
                        ->dateTime()
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),
                ])->striped()
                ->defaultSort('disbursed_on_date', 'desc')
                ->filters([
                    
    
                ])
            ->headerActions([
                ExportAction::make()
                    ->exporter(LoanExporter::class),
                ImportAction::make()
                    ->importer(LoanImporter::class),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
