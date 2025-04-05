<?php

namespace App\Filament\Resources\LoanResource\RelationManagers;

use Carbon\Carbon;
use Filament\Forms;
use Filament\Tables;
use App\Models\Client;
use Filament\Forms\Set;
use App\Models\Currency;
use Filament\Forms\Form;
use App\Models\Loan\Loan;
use Filament\Tables\Table;
use Forms\Components\Hidden;
use App\Events\LoanRepayment;
use Filament\Tables\Columns\Column;
use App\Models\Loan\LoanTransaction;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Loan\LoanRepaymentSchedule;
use Filament\Infolists\Components\TextEntry;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\RelationManagers\RelationManager;

class TransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactions';
    //protected static string $title = 'Repayment Transactions';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('created_by_id')
                             ->default(Auth::id()),
                        Forms\Components\Hidden::make('created_on')
                             ->default(Carbon::now()),
                        Forms\Components\Hidden::make('credit'),
                        Forms\Components\Hidden::make('debit')
                             ->default(0),
                        Forms\Components\Hidden::make('payment_detail_id'),
                        Forms\Components\Hidden::make('loan_id'),
                        Forms\Components\Hidden::make('name')
                            ->default('Repayment'),
                        Forms\Components\Hidden::make('loan_transaction_type_id')
                            ->default(2),
                        Forms\Components\TextInput::make('amount')
                            ->numeric()
                            ->live()
                            ->afterStateUpdated(function (Set $set, $state) {
                                $set('credit', $state);})
                            ->required(),
                        Forms\Components\DatePicker::make('submitted_on')
                            ->label('Date of Transaction')
                            ->native(false)
                            ->required(),
                        Forms\Components\TextInput::make('account_number')
                        ->default($this->getOwnerRecord()->account_number)
                            ->required(),





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
                      ->label('Created On'),
                Tables\Columns\TextColumn::make('name')
                      ->label('Transaction Type'),
              Tables\Columns\TextColumn::make('reference')
                      ->searchable(),
                Tables\Columns\TextColumn::make('debit')
                      ->money(Currency::where('is_default', 1)->first()->symbol),
                Tables\Columns\TextColumn::make('credit')
                       ->money(Currency::where('is_default', 1)->first()->symbol),
                Tables\Columns\TextColumn::make('balance')
                      ->label('Balance')
                      ->money(Currency::where('is_default', 1)->first()->symbol)
                      ->getStateUsing(function (LoanTransaction $record) {
                        $loan = $record->loan;
                        $balance = $loan->principal_disbursed_derived;
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
                Tables\Actions\CreateAction::make()
                //->model(LoanTransaction::class)
                     ->label('Create Repayment')
                    //  ->fillForm(fn (LoanTransaction $record): array => [
                    //     'loan_id' => $record->id,
                    //     'account_number' => $record->client->account_number,
                    //     'created_by_id' => Auth::id(),
                    //     'created_on' => Carbon::now(),
                    //     'name' => 'Repayment',
                    //     'debit' => 0,
                    // ])
                        ->form([
                            Forms\Components\TextInput::make('amount')
                            ->numeric()
                            ->afterStateUpdated(function (Set $set, $state) {
                                $set('credit', $state);})
                            ->required(),
                            Forms\Components\DatePicker::make('submitted_on')
                                ->label('Date of Transaction')
                                ->native(false)
                                ->required(),
                            Forms\Components\Hidden::make('credit'),
                            Forms\Components\Hidden::make('loan_id')
                                ->default($this->getOwnerRecord()->id)
                                ->required(),
                            Forms\Components\Hidden::make('created_by_id'),
                            Forms\Components\Hidden::make('created_on'),
                            Forms\Components\Hidden::make('debit'),
                            Forms\Components\Hidden::make('name'),
                            Forms\Components\TextInput::make('account_number')
                                ->default($this->getOwnerRecord()->account_number)
                                ->required(),

                                ])
                            ->action(function ( array $data) {

                                    $record = $this->getOwnerRecord();
                                    $transaction = $record->saveTransaction($data,$record);
                                    event(new LoanRepayment($record,$data));
                                    Notification::make()
                                     ->success()
                                     ->title('Transaction Created')
                                     ->body('The Transaction has been created successfully.')
                                     ->send();

                            }),

            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
                // Tables\Actions\DeleteAction::make(),
               // Tables\Actions\ViewAction::make()
                   // ->modalHeading('Loan Repayment Transaction'),
                Tables\Actions\Action::make('reverse')
                    ->visible(fn(Loantransaction $record) => $record->name === 'Repayment')
                    ->action(function (LoanTransaction $record) {
                        $record->update([
                            'amount' => 0,
                            'debit' => $record->credit,
                            'reversed' => 1,
                        ]);
                        $record->save();
                    })
                    ->color('danger')
                    ->requiresConfirmation(),
                ]);
            // ->bulkActions([
            //     Tables\Actions\BulkActionGroup::make([
            //         Tables\Actions\DeleteBulkAction::make(),
            //     ]),
            // ]);
    }
}
