<?php

namespace App\Filament\Resources;

use Carbon\Carbon;
use Filament\Forms;
use Filament\Tables;
use App\Models\Branch;
use App\Models\Client;
use App\Models\Currency;
use App\Models\MpesaC2B;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Policies\MpesaC2BPolicy;
use Filament\Resources\Resource;
use Filament\Tables\Filters\Filter;
use Illuminate\Support\Facades\Auth;
use App\Filament\Exports\MpesaExporter;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Actions\ExportAction;
use Filament\Tables\Actions\ImportAction;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Imports\MpesaC2BImporter;
use Filament\Tables\Columns\Summarizers\Sum;
use App\Filament\Resources\MpesaC2BResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\MpesaC2BResource\RelationManagers;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Tables\Filters\QueryBuilder\Constraints\DateConstraint;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class MpesaC2BResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = MpesaC2B::class;
    protected static ?string $navigationGroup = 'Front Office';
    protected static ?string $navigationLabel = 'Mpesa Payments';
    protected static ?string $title = 'Mpesa Payments';
    protected static ?string $slug = 'mpesa-payments';
    protected ?string $heading = 'Mpesa Payments';

    protected static ?string $navigationIcon = null;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('FirstName')
                    ->maxLength(255),
                Forms\Components\TextInput::make('Transaction_type')
                    ->maxLength(255),
                Forms\Components\TextInput::make('Transaction_ID')
                    ->maxLength(255),
                Forms\Components\DateTimePicker::make('Transaction_Time')
                    ->native(false),
                Forms\Components\TextInput::make('Amount')
                    ->numeric(),
                Forms\Components\TextInput::make('Business_Shortcode')
                    ->maxLength(255),
                Forms\Components\TextInput::make('Account_Number'),
                Forms\Components\TextInput::make('Invoice_no')
                    ->maxLength(255),
                Forms\Components\TextInput::make('Phonenumber')
                    ->maxLength(255),

                Forms\Components\TextInput::make('MiddleName')
                    ->maxLength(255),
                Forms\Components\TextInput::make('LastName')
                    ->maxLength(255),
                Forms\Components\DateTimePicker::make('created_at')
                    ->native(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
        ->striped()
       ->query(MpesaC2B::whereBetween('created_at', [now()->subMonths(3), now()]))
        ->defaultSort('created_at', 'desc')
        ->headerActions([
            // ImportAction::make()
            //  ->importer(MpesaC2BImporter::class)
            //  ->label('Import Mpesa Payments'),
            ExportAction::make()
            ->exporter(MpesaExporter::class)
            ->label('Export Mpesa Payments'),
        ])
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

                Tables\Columns\TextColumn::make('Invoice_no')
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

            ])
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
                            fn (Builder $query, $date): Builder => $query->whereDate('Transaction_Time', '=', $date),
                        );
                })
                ->indicateUsing(function (array $data): ?string {
                    if (! $data['paid_date']) {
                        return null;
                    }

                    return 'Created at ' . Carbon::parse($data['paid_date'])->toFormattedDateString();
                }),
            SelectFilter::make('Invoice_no')
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
                    
                Tables\Actions\Action::make('fees')
                ->label('Fees')
                ->color('info')
                ->icon('heroicon-s-check-circle')
                ->requiresConfirmation()
                ->modalHeading('Resolve as Processing fees Transaction')
                ->modalDescription('Are you sure you want to resolve this transaction?')
                ->visible(function (MpesaC2B $record){
                    $policy = new MpesaC2BPolicy();
                    return $record->Invoice_no == 'not_resolved' && $policy->update(Auth::user(), $record);
                })
                ->action(function (MpesaC2B $record ) {
                    $record->update(['Invoice_no' => 'processing_fees']);
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
                ->visible(function (MpesaC2B $record){
                    $policy = new MpesaC2BPolicy();
                    return $record->Invoice_no == 'not_resolved' && $policy->update(Auth::user(), $record);
                })
                ->action(function (MpesaC2B $record ) {
                    $record->update(['Invoice_no' => 'refund']);
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
                ->visible(function (MpesaC2B $record){
                    $policy = new MpesaC2BPolicy();
                    return $record->Invoice_no == 'not_resolved' && $policy->update(Auth::user(), $record);
                })
                ->form([
                    TextInput::make('account_number')->label('Account number'),
                ])
                ->action(function (MpesaC2B $record , array $data) {

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
                ])
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
            'index' => Pages\ListMpesaC2BS::route('/'),
            // 'create' => Pages\CreateMpesaC2B::route('/create'),
            // 'edit' => Pages\EditMpesaC2B::route('/{record}/edit'),
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

