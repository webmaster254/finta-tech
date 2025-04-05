<?php

namespace App\Filament\App\Resources\LoanResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use App\Models\Currency;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\Loan\LoanTransaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\RelationManagers\RelationManager;

class TransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactions';

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
            ->striped()
            ->heading('Repayment Transactions')
            ->recordTitleAttribute('loan_id')
            ->columns([
                Tables\Columns\TextColumn::make('loan_id'),
                Tables\Columns\TextColumn::make('submitted_on'),
                Tables\Columns\TextColumn::make('created_on')
                      ->label('Paid On'),
                Tables\Columns\TextColumn::make('name')
                      ->label('Transaction Type'),
                Tables\Columns\TextColumn::make('debit')
                      ->money(Currency::where('is_default', 1)->first()->symbol),
                Tables\Columns\TextColumn::make('credit')
                       ->money(Currency::where('is_default', 1)->first()->symbol),
                Tables\Columns\TextColumn::make('balance')
                      ->label('Balance')
                      ->money(Currency::where('is_default', 1)->first()->symbol)
                      ->getStateUsing(function (LoanTransaction $record) {
                        $loan = $record->loan;
                        $balance = $loan->principal;
                        //dump($loan);
                            foreach ($loan->transactions as $transaction) {

                                        if ( $transaction['loan_transaction_type_id'] == 11 || $transaction['loan_transaction_type_id'] == 10) {
                                            $balance += $transaction->amount;

                                        }else if( $transaction['loan_transaction_type_id'] == 2 || $transaction['loan_transaction_type_id'] == 4
                                        || $transaction['loan_transaction_type_id'] == 6
                                        || $transaction['loan_transaction_type_id'] == 8
                                        || $transaction['loan_transaction_type_id'] == 9) {
                                        $balance -= $transaction->amount;
                                    }
                                    if ($transaction->id == $record->id) {
                                        return $balance;
                                    }

                    }
                      }),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
                // Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([

            ]);
    }
}
