<?php

namespace App\Filament\Resources;

use Carbon\Carbon;
use Filament\Forms;
use Filament\Tables;
use App\Models\Branch;
use App\Models\Client;
use App\Models\MpesaC2B;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Tables\Actions\EditAction;
use App\Policies\MpesaC2BPolicy;
use Filament\Resources\Resource;
use App\Models\MpesaTransactions;
use Filament\Tables\Actions\Action;
use Filament\Tables\Filters\Filter;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Enums\FiltersLayout;
use App\Policies\MpesaTransactionsPolicy;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Forms\Components\DateTimePicker;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\MpesaTransactionsResource\Pages;
use App\Filament\Resources\MpesaTransactionsResource\RelationManagers;

class MpesaTransactionsResource extends Resource
{
    protected static ?string $model = MpesaTransactions::class;

    protected static ?string $navigationGroup = 'Front Office';
    protected static ?string $navigationLabel = 'Mpesa Reconciliation';
    protected static ?string $title = 'Mpesa Reconciliation';
    protected static ?string $slug = 'mpesa-recon';
    protected ?string $heading = 'Mpesa Reconciliation';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('transaction_id')
                    ->required()
                    ->maxLength(255),
                Forms\Components\DateTimePicker::make('transaction_date')
                    ->required(),
                Forms\Components\TextInput::make('msisdn')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('sender')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('transaction_type')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('bill_reference')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('amount')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('organization_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('response_ref_id')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('response_code')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('response_message')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('status')
                    ->required()
                    ->maxLength(255)
                    ->default('not_resolved'),
                Forms\Components\Textarea::make('raw_response')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
        ->defaultSort('transaction_date', 'desc')
        ->headerActions([
            Action::make('reconcile')
                ->label('Reconcile')
                ->form([
                    DateTimePicker::make('start_date')
                        ->label('Start Date')
                        ->native(false),
                    DateTimePicker::make('end_date')
                        ->label('End Date')
                        ->native(false),
                ])
                ->action(function (array $data) {
                    $mpesaController = new \App\Http\Controllers\MpesaController();
                    $mpesaController->pullTransactions($data['start_date'], $data['end_date']);
                    Notification::make()
                        ->success()
                        ->title('Missed Transactions Pulled')
                        ->body('The transactions have been Pulled successfully.')
                        ->send();
                })
                ->color('success')
                ->icon('heroicon-s-check-circle'),
        ])
            ->columns([
                Tables\Columns\TextColumn::make('transaction_id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('transaction_date')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('msisdn')
                    ->label('Phone Number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('sender')
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount')
                    ->numeric()
                    ->summarize(Sum::make()
                                ->money('KES')
                                ->label('Total'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('bill_reference')
                    ->label('Account Number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->label('Status')
                    ->color(fn (string $state): string => match ($state){
                        'resolved' => 'success',
                        'not_resolved' => 'danger',
                        'processing_fees' => 'success',
                        'refund' => 'warning',
                    })
                    ->icon(fn (string $state): string => match ($state){
                        'resolved' => 'heroicon-s-check-circle',
                        'not_resolved' => 'heroicon-s-x-circle',
                        'processing_fees' => 'heroicon-s-check-circle',
                        'refund' => 'heroicon-s-check-circle',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('transaction_date')
                ->form([
                    DatePicker::make('paid_date')
                        ->label('Payment Date')
                        ->native(false),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when(
                            $data['paid_date'],
                            fn (Builder $query, $date): Builder => $query->whereDate('transaction_date', '=', $date),
                        );
                })
                ->indicateUsing(function (array $data): ?string {
                    if (! $data['paid_date']) {
                        return null;
                    }

                    return 'Created at ' . Carbon::parse($data['paid_date'])->toFormattedDateString();
                }),
            SelectFilter::make('status')
                ->label('Status')
                ->options([
                    'resolved' => 'Resolved',
                    'not_resolved' => 'Not Resolved',
                    'processing_fees' => 'Processing Fees',
                    'refund' => 'Refund',

                ])
        ], layout: FiltersLayout::AboveContent)
        ->actions([
            ActionGroup::make([
                // EditAction::make()
                // ->visible(function (MpesaC2B $record){
                //     $policy = new MpesaC2BPolicy();
                //     return $policy->update(Auth::user(), $record);
                //
            Tables\Actions\Action::make('fees')
            ->label('Fees')
            ->color('info')
            ->icon('heroicon-s-check-circle')
            ->requiresConfirmation()
            ->modalHeading('Resolve as Processing fees Transaction')
            ->modalDescription('Are you sure you want to resolve this transaction?')
            ->visible(function (MpesaTransactions $record){
                $policy = new MpesaTransactionsPolicy();
                return $record->status == 'not_resolved' && $policy->update(Auth::user(), $record);
            })
            ->action(function (MpesaTransactions $record ) {
                $record->update(['status' => 'processing_fees']);
                $record->save();
                Notification::make()
                             ->success()
                             ->title('Transaction Created')
                             ->body('The Transaction has been resolved successfully for account  '.$record->Account_Number.'!!' )
                             ->send();
            }),

            Tables\Actions\Action::make('refund')
            ->label('Refund')
            ->color('warning')
            ->icon('heroicon-s-check-circle')
            ->requiresConfirmation()
            ->modalHeading('Resolve as Refund Transaction')
            ->modalDescription('Are you sure you want to resolve this transaction?')
            ->visible(function (MpesaTransactions $record){
                $policy = new MpesaTransactionsPolicy();
                return $record->status == 'not_resolved' && $policy->update(Auth::user(), $record);
            })
            ->action(function (MpesaTransactions $record ) {
                $record->update(['status' => 'refund']);
                $record->save();
                Notification::make()
                             ->success()
                             ->title('Transaction Created')
                             ->body('The Transaction has been resolved successfully for account  '.$record->Account_Number.'!!' )
                             ->send();
            }),
        Tables\Actions\Action::make('Resolve')
            ->label('Resolve payment')
            ->color('success')
            ->icon('heroicon-s-check-circle')
            ->visible(function (MpesaTransactions $record){
                $policy = new MpesaTransactionsPolicy();
                return $record->status == 'not_resolved' && $policy->update(Auth::user(), $record);
            })
            ->form([
                TextInput::make('account_number')->label('Account number'),
            ])
            ->action(function (MpesaTransactions $record , array $data) {

                $loan = Client::where('account_number', $data['account_number'])->whereHas('branch', function ($query) {
                    $query->whereIn('id', Branch::pluck('id'));
                })->first()?->loans()->where('status', 'active')->first();

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
             Tables\Actions\DeleteAction::make(),
            ]
            )

        ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMpesaTransactions::route('/'),
            // 'create' => Pages\CreateMpesaTransactions::route('/create'),
            // 'edit' => Pages\EditMpesaTransactions::route('/{record}/edit'),
        ];
    }

    public static function getPermissionPrefixes(): array
    {
        return [
            'view',
            'view_any',
            'create',
            'update',
            'delete',
            'delete_any',
            'approve',
            'reject',
            'resolve',
        ];
    }
}
