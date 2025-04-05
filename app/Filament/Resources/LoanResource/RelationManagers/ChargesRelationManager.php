<?php

namespace App\Filament\Resources\LoanResource\RelationManagers;

use Filament\Forms;
use App\Models\User;
use Filament\Tables;
use Filament\Forms\Set;
use App\Models\Currency;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Events\LoanChargeWaive;
use App\Models\Loan\LoanCharge;
use App\Models\Loan\LoanLinkedCharge;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\RelationManagers\RelationManager;

class ChargesRelationManager extends RelationManager
{
    protected static string $relationship = 'charges';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Charge Name')
                    ->required(),
                Forms\Components\TextInput::make('amount')
                    ->numeric(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('amount')
                    ->numeric()
                    ->money(Currency::where('is_default', 1)->first()->symbol),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                //Tables\Actions\CreateAction::make('Add_charge'),

            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading('Loan Charge'),
                Tables\Actions\Action::make('Waive Charge')
                    ->modalHeading('Waive Charge')
                    ->color('danger')
                    ->visible(fn(LoanLinkedCharge $record): bool => $record->waived == 0)
                    ->action(function (LoanLinkedCharge $record) {
                        event(new LoanChargeWaive($record));

                        Notification::make()
                             ->success()
                             ->title('Charge Waived')
                             ->body('The Loan Charge Waive has been created successfully.')
                             ->send();
                    })->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make(),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
