<?php

namespace App\Filament\Resources\ClientResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use App\Models\Currency;
use Filament\Forms\Form;
use App\Models\Loan\Loan;
use Filament\Tables\Table;
use App\Filament\Resources\LoanResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\RelationManagers\RelationManager;

class LoansRelationManager extends RelationManager
{
    protected static string $relationship = 'loans';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('id')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('loan_account_number')
                    ->label('Loan Account No'),
                Tables\Columns\TextColumn::make('approved_amount')
                    ->label('Principal Amount')
                    ->money(Currency::where('is_default', 1)->first()->symbol),
                Tables\Columns\TextColumn::make('balance')
                    ->label('Balance')
                    ->money(Currency::where('is_default', 1)->first()->symbol)
                    ->getStateUsing(fn (Loan $record) => $record->getBalance($record->id)),
                Tables\Columns\TextColumn::make('disbursed_on_date')
                    ->label('Disbursement Date'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge(),
            ])->defaultSort('disbursed_on_date', 'desc')
            ->filters([
                //
            ])
            ->headerActions([
                //Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
                // Tables\Actions\DeleteAction::make(),
                Tables\Actions\ViewAction::make()
                    ->url(fn (Loan $record) => LoanResource::getUrl('view', ['record' => $record->id])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public function isReadOnly(): bool
    {
        return false;
    }
}
