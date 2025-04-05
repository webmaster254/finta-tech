<?php

namespace App\Filament\App\Resources\ClientResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use App\Models\Loan\Loan;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\App\Resources\LoanResource;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\RelationManagers\RelationManager;

class LoansRelationManager extends RelationManager
{
    protected static string $relationship = 'loans';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('loan_id')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
        ->recordTitleAttribute('id')
        ->columns([
            Tables\Columns\TextColumn::make('id')
                ->label('Loan ID'),
            Tables\Columns\TextColumn::make('approved_amount')
                ->label('Principal Amount')
                ->money('KES'),
            Tables\Columns\TextColumn::make('balance')
                ->label('Balance')
                ->money('KES')
                ->getStateUsing(fn (Loan $record) => $record->getBalance($record->id)),
            Tables\Columns\TextColumn::make('disbursed_on_date')
                ->label('Disbursement Date'),
            Tables\Columns\TextColumn::make('status')
                ->label('Status')
                ->badge(),
        ])
            ->filters([
                //
            ])
            ->headerActions([
                //Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn (Loan $record) => LoanResource::getUrl('view', ['record' => $record->id])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
