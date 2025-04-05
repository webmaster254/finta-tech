<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Set;
use App\Models\Currency;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\BankAccount;
use App\Models\JournalEntries;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use App\Enums\ChartAccountCategory;
use App\Models\ChartOfAccountSubtype;
use App\Enums\Banking\BankAccountType;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Actions\ActionGroup;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Components\Fieldset;
use Filament\Infolists\Components\TextEntry;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\BankAccountResource\Pages;

use App\Filament\Resources\BankAccountResource\RelationManagers;

class BankAccountResource extends Resource
{
    protected static ?string $model = BankAccount::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    protected static ?string $navigationGroup = 'Account Management';
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                Forms\Components\Select::make('chart_of_account_id')
                    ->relationship('chartOfAccount', 'name'),
                Forms\Components\Select::make('type')
                    ->options(BankAccountType::class)
                    ->searchable()
                    ->columnSpan(1)
                    ->default(BankAccountType::DEFAULT)
                    ->live()
                    ->afterStateUpdated(static function (Forms\Set $set, $state, ?BankAccount $bankAccount, string $operation) {
                        if ($operation === 'create') {
                            $set('chartOfAccount.subtype_id', null);
                        } elseif ($operation === 'edit' && $bankAccount !== null) {
                            if ($state !== $bankAccount->type->value) {
                                $set('chartOfAccount.subtype_id', null);
                            } else {
                                $set('chartOfAccount.subtype_id', $bankAccount->chartOfAccount->subtype_id);

                            }
                        }
                    })
                    ->required(),
                Forms\Components\Group::make()
                            ->columnStart(2)
                            ->relationship('chartOfAccount')
                            ->schema([
                                Forms\Components\Select::make('subtype_id')
                                    ->options(static fn (Forms\Get $get) => static::groupSubtypesBySubtypeType(BankAccountType::parse($get('data.type', true))))

                                    ->searchable()
                                    ->live()
                                    ->required(),
                            ]),
                Forms\Components\TextInput::make('bank_holder_name')
                    ->maxLength(255),
                Forms\Components\Group::make()
                    ->relationship('chartOfAccount')
                    ->columns()
                    ->columnSpanFull()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->maxLength(100)
                            ->required(),
                        Forms\Components\Select::make('currency_code')
                            ->relationship('currency', 'name')
                            ->preload()
                            ->searchable(),
                    ]),
                Forms\Components\TextInput::make('account_number')
                    ->maxLength(255),
                Forms\Components\TextInput::make('branch_name')
                    ->maxLength(255),
                Forms\Components\TextInput::make('mobile')
                    ->label('Contact Number')
                    ->tel()
                    ->telRegex('/^[+]*[(]{0,1}[0-9]{1,4}[)]{0,1}[-\s\.\/0-9]*$/')
                    ->maxLength(25),
                Forms\Components\TextInput::make('opening_balance')
                    ->numeric()
                    ->prefix('KES')

                    ->afterStateUpdated(function (Set $set, $state) {
                        $set('balance', $state);
                            }),
                Forms\Components\Hidden::make('balance'),
                Forms\Components\Toggle::make('enabled'),
                Forms\Components\TextInput::make('address')
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('chartOfAccount.name')
                    ->label('Account Name')
                    ->icon(static fn (BankAccount $record) => $record->isEnabled() ? 'heroicon-o-lock-closed' : null)
                    ->tooltip(static fn (BankAccount $record) => $record->isEnabled() ? 'Default Account' : null)
                    ->iconPosition('after')
                    ->description(static fn (BankAccount $record) => $record->mask ?? null)
                    ->sortable(),
                Tables\Columns\TextColumn::make('bank_holder_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('account_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('branch_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('mobile')
                    ->label('Contact Number'),
                Tables\Columns\TextColumn::make('opening_balance')
                    ->numeric()

                    ->label('Opening Balance')
                    ->money(Currency::where('is_default', 1)->first()->symbol)
                    ->sortable(),
                Tables\Columns\TextColumn::make('balance')
                    ->label('Balance')
                    ->money(Currency::where('is_default', 1)->first()->symbol),
                Tables\Columns\TextColumn::make('address')
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
                //
            ])
            ->actions([
                Tables\Actions\Action::make('funds')
                    ->label('Add Funds')
                    ->modalHeading('Approve Loan')

                    ->form([
                        TextInput::make('amount')
                            ->label('Amount')
                            ->required(),
                    ])
                    ->action(function (BankAccount $record, $data) {
                        $record->addFunds($record,$data);
                        $record->update([
                            'balance' => $record->opening_balance + $data['amount'],
                        ]);
                    })
                    ->color('success')
                    ->requiresConfirmation(),

                ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\DeleteAction::make(),
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

    public static function Infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Fieldset::make('Label')
                    ->schema([
                        TextEntry::make('chartOfAccount.name')
                            ->label('Account Name')
                            ->color('info'),
                        TextEntry::make('account_number')
                            ->color('info'),
                        TextEntry::make('branch_name')
                            ->color('info'),
                        TextEntry::make('bank_holder_name')
                            ->color('info'),
                        TextEntry::make('mobile')
                            ->color('info'),
                        TextEntry::make('opening_balance')
                            ->color('info')
                            ->label('Opening Balance')
                            ->money(Currency::where('is_default', 1)->first()->symbol),
                        TextEntry::make('balance')
                            ->money(Currency::where('is_default', 1)->first()->symbol)
                            ->color('info'),
                        TextEntry::make('address')
                            ->color('info'),

                    ])
                    ->columns(3)
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBankAccounts::route('/'),
            'create' => Pages\CreateBankAccount::route('/create'),
            'edit' => Pages\EditBankAccount::route('/{record}/edit'),
            'view' => Pages\ViewBankAccount::route('/{record}'),
        ];
    }

    public static function groupSubtypesBySubtypeType(BankAccountType $bankAccountType): array
    {
        $category = match ($bankAccountType) {
            BankAccountType::Depository, BankAccountType::Investment => ChartAccountCategory::Asset,
            BankAccountType::Credit, BankAccountType::Loan => ChartAccountCategory::Liability,
            default => null,
        };

        if ($category === null) {
            return [];
        }

        $subtypes = ChartOfAccountSubtype::where('category', $category)->get();

        return $subtypes->groupBy(fn (ChartOfAccountSubtype $subtype) => $subtype->type->getLabel())
            ->map(fn (Collection $subtypes, string $type) => $subtypes->mapWithKeys(static fn (ChartOfAccountSubtype $subtype) => [$subtype->id => $subtype->name]))
            ->toArray();
    }
}
