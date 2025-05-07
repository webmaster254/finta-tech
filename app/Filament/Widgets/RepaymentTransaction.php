<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use App\Models\Client;
use App\Models\Currency;
use App\Models\MpesaC2B;
use App\Models\Loan\Loan;
use Filament\Tables\Table;
use App\Policies\MpesaC2BPolicy;
use Filament\Tables\Filters\Filter;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\QueryBuilder;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\MpesaC2BResource;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables\Filters\QueryBuilder\Constraints\DateConstraint;

class RepaymentTransaction extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';
    protected static ?string $pollingInterval = '10s';


    protected static ?int $sort = 7;
    public function table(Table $table): Table
    {
        return $table
        //->modifyQueryUsing((fn (Builder $query) => $query->where('created_at', '=', now()->format('Y-m-d'))))
        ->poll('10s')
        ->heading('Repayment Transactions')
        ->description('All Mpesa Transaction')
        ->striped()
        ->filters([
            Filter::make('created_at')
                ->form([
                    DatePicker::make('paid_date')
                        ->label('Payment Date')
                        ->native(false),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when(
                            $data['paid_date'],
                            fn (Builder $query, $date): Builder => $query->whereDate('created_at', '=', $date),
                        );
                }),
            SelectFilter::make('status')
                ->options([
                    'resolved' => 'Resolved',
                    'not_resolved' => 'Not Resolved',
                    'processing_fees' => 'Processing Fees',

                ])
        ], layout: FiltersLayout::AboveContent)
        ->query(MpesaC2B::whereBetween('created_at', [now()->subMonths(3), now()]))
        ->defaultSort('created_at', 'desc')
        ->columns([
            Tables\Columns\TextColumn::make('FirstName')
                    ->searchable(),
            Tables\Columns\TextColumn::make('Transaction_ID')
                    ->searchable(),
                Tables\Columns\TextColumn::make('Amount')
                    ->numeric()
                    ->money('KES')
                    ->summarize(Sum::make()
                                ->money('KES')
                                ->label('Total')),
                Tables\Columns\TextColumn::make('Account_Number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('Transaction_Time')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('Phonenumber')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->label('Status')
                    ->color(fn (string $state): string => match ($state){
                        'resolved' => 'success',
                        'not_resolved' => 'danger',
                        'processing_fees' => 'success',
                    })
                    ->icon(fn (string $state): string => match ($state){
                        'resolved' => 'heroicon-s-check-circle',
                        'not_resolved' => 'heroicon-s-x-circle',
                        'processing_fees' => 'heroicon-s-check-circle',
                    }),
                Tables\Columns\TextColumn::make('Organization_Account_Balance')
                    ->label('Paybill Account Balance')
                    ->searchable()
                    ->numeric()
                    ->money(Currency::where('is_default', 1)->first()->symbol)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('MiddleName')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('LastName')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('Transaction_type')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

        ])->actions([
            Tables\Actions\Action::make('fees')
                ->label('Fees')
                ->button()
                ->requiresConfirmation()
                ->modalHeading('Resolve as Processing fees Transaction')
                ->modalDescription('Are you sure you want to resolve this transaction?')
                ->visible(function (MpesaC2B $record){
                    $policy = new MpesaC2BPolicy();
                    return $record->status == 'not_resolved' && $policy->update(Auth::user(), $record);
                })
                ->action(function (MpesaC2B $record ) {
                    $record->update(['status' => 'processing_fees']);
                    $record->save();
                    Notification::make()
                                 ->success()
                                 ->title('Transaction Created')
                                 ->body('The Transaction has been resolved successfully for account  '.$record->Account_Number.'!!' )
                                 ->send();
                }),
            Tables\Actions\Action::make('Resolve')
                ->label('Resolve payment')
                ->button()
                ->color('success')
                ->visible(function (MpesaC2B $record){
                    $policy = new MpesaC2BPolicy();
                    return $record->status == 'not_resolved' && $policy->update(Auth::user(), $record);
                })
                ->form([
                    TextInput::make('account_number')->label('Account number'),
                ])
                ->action(function (MpesaC2B $record , array $data) {

                    $loan = Client::where('account_number', $data['account_number'])->first()?->loans()->where('status', 'active')->first();

                    if($loan){
                        $updatedTransaction= $record->updateTransactions($loan,$record,$data);

                        Notification::make()
                                 ->success()
                                 ->title('Transaction Created')
                                 ->body('The Transaction has been created successfully for account  '.$data['account_number'].'!!' )
                                 ->send();


                        }else {
                       Notification::make()
                                 ->danger()
                                 ->title('Loan Not Found')
                                 ->body('The Loan with the account number '.$data['account_number'].' was not found.')
                                 ->send();

                            return;}
                }),
        ]);


    }
}
