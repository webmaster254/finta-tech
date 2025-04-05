<?php

namespace App\Filament\Resources\InvestorResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\RelationManagers\RelationManager;

class InvestmentRelationManager extends RelationManager
{
    protected static string $relationship = 'investment';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('investment_id')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('installment'),
                Tables\Columns\TextColumn::make('amount')
                    ->numeric(),
                Tables\Columns\TextColumn::make('paid_amount')
                    ->numeric(),
                Tables\Columns\TextColumn::make('payment_date'),
                Tables\Columns\TextColumn::make('Amount Due')
                    ->numeric()
                    ->getStateUsing(function ($record) {
                        return $record->amount - $record->paid_amount;
                    }),
            ])
            ->filters([
                //
            ])
            // ->headerActions([
            //     Tables\Actions\CreateAction::make(),
            // ])
            ->actions([
                Tables\Actions\Action::make('Make Payment')
                    ->label('Pay')
                    ->button()
                    ->visible(fn ($record) => $record->amount - $record->paid_amount > 0)
                    ->color('success')
                    ->icon('heroicon-o-credit-card')
                    ->fillForm(fn ($record) => [
                        'amount' => $record->amount - $record->paid_amount,
                    ])
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'paid_amount' => $record->paid_amount + $data['amount'],
                        ]);
                        Notification::make()
                            ->title('Payment made successfully')
                            ->success()
                            ->body('The payment has been made successfully.')
                            ->send();
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);


    }

}
