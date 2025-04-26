<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\Fund;
use Filament\Tables;
use App\Models\Product;
use Filament\Forms\Get;
use App\Models\Currency;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Enums\AccountingRule;
use App\Models\ChartOfAccount;
use App\Enums\InterestRateType;
use App\Models\Loan\LoanProduct;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use App\Enums\AmortizationMethod;
use App\Enums\InterestMethodology;
use Filament\Forms\Components\Grid;
use App\Enums\RepaymentFrequencyType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use App\Filament\Clusters\Configuration;
use Filament\Tables\Actions\ActionGroup;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Components\Fieldset;
use Filament\Infolists\Components\TextEntry;
use App\Models\LoanTransactionProcessingStrategy;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\LoanProductResource\Pages;
use Filament\Infolists\Components\Section as InfolistSection;
use App\Filament\Resources\LoanProductResource\RelationManagers;
use App\Filament\Resources\LoanProductResource\RelationManagers\LoanChargesRelationManager;

class LoanProductResource extends Resource
{
    protected static ?string $model = LoanProduct::class;

    protected static ?string $navigationIcon = null;
    protected static ?string $navigationGroup = 'Loans Management';
    protected static ?int $navigationSort = 3;
    protected static bool $isScopedToTenant = false;
    protected static ?string $cluster = Configuration::class;

    public static function getNavigationBadge(): ?string
{
    return static::getModel()::count();
}

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->maxLength(65535),
                Forms\Components\TextInput::make('decimals')
                    ->label('Decimals')
                    ->default(0)
                    ->numeric(),
                // Forms\Components\Select::make('fund_id')
                //         ->options(ChartOfAccount::where('category', 'asset')->get()->pluck('name', 'id'))
                //         ->preload()
                //         ->searchable(),
                Forms\Components\Select::make('currency_id')
                        ->relationship('currency', 'name')
                        ->preload()
                        ->searchable(),
                Forms\Components\Select::make('loan_transaction_processing_strategy_id')
                        ->relationship('loanTransactionProcessingStrategy', 'name')
                        ->preload()
                        ->searchable(),

                Forms\Components\TextInput::make('default_principal')
                    ->label('Default Principal')
                    ->required(),

                Forms\Components\TextInput::make('maximum_principal')
                    ->label('Maximum Principal')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('minimum_principal')
                    ->label('Minimum Principal')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('default_loan_term')
                    ->label('Default Loan Term')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('maximum_loan_term')
                    ->label('Maximum Loan Term')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('minimum_loan_term')
                    ->label('Minimum Loan Term')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('default_interest_rate')
                    ->label('Default Interest Rate')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('maximum_interest_rate')
                    ->label('Maximum Interest Rate')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('minimum_interest_rate')
                    ->label('Minimum Interest Rate')
                    ->required()
                    ->numeric(),
                Forms\Components\Select::make('interest_rate_type')
                    ->label('Interest Rate Type')
                    ->options(InterestRateType::class)
                    ->required(),
                // Forms\Components\TextInput::make('installment_multiple_of')
                //     ->label('Installment Multiple Of')
                //     ->numeric()
                //     ->default(1),
                Forms\Components\TextInput::make('repayment_frequency')
                    ->label('Repayment Frequency')
                    ->required()
                    ->numeric(),
                Forms\Components\Select::make('repayment_frequency_type')
                    ->label('Repayment Frequency Type')
                    ->options(RepaymentFrequencyType::class)
                    ->required(),


                Forms\Components\TextInput::make('grace_on_principal_paid')
                    ->label('Grace On Principal Payment')
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('grace_on_interest_paid')
                    ->label('Grace On Interest Payment')
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('grace_on_interest_charged')
                    ->label('Grace On Interest Charged')
                    ->numeric()
                    ->default(0),
                Forms\Components\Select::make('interest_methodology')
                    ->label('Interest Methodology')
                    ->options(InterestMethodology::class)
                    ->required(),
                Forms\Components\Select::make('amortization_method')
                    ->label('Amortization Method')
                    ->options(AmortizationMethod::class)
                    ->required(),
                Forms\Components\Select::make('repayment_account_id')
                    ->label('Repayment Account')
                    ->options(Product::all()->pluck('name', 'id'))
                    ->required(),


                Section::make('Accounting')
                    ->schema([
                            Select::make('accounting_rule')
                                ->label('Accounting Rule')
                                ->options(AccountingRule::class)
                                ->default('none')
                                ->live()
                                ->afterStateUpdated(fn (Select $component) => $component
                                                ->getContainer()
                                                ->getComponent('dynamicClientTypeFields')
                                                ->getChildComponentContainer()
                                                ->fill()),
                            Grid::make(1)
                                ->schema(fn (Get $get): array => match ($get('accounting_rule')) {
                                'cash' => [
                                    Forms\Components\Select::make('fund_source_chart_of_account_id')
                                        ->label('Fund Source')
                                        ->options(ChartOfAccount::where('category', 'asset')->get()->pluck('name', 'id'))
                                        ->required(),
                                    Forms\Components\Select::make('loan_portfolio_chart_of_account_id')
                                        ->label('Loan Portfolio')
                                        ->options(ChartOfAccount::where('category', 'asset')->get()->pluck('name', 'id'))
                                        ->required(),
                                    Forms\Components\Select::make('administration_fees_chart_of_account_id')
                                        ->label('Administration Fees')
                                        ->options(ChartOfAccount::where('category', 'revenue')->get()->pluck('name', 'id'))
                                        ->required(),
                                    Forms\Components\Select::make('insurance_chart_of_account_id')
                                        ->label('Insurance')
                                        ->options(ChartOfAccount::where('category', 'liability')->get()->pluck('name', 'id'))
                                        ->required(),
                                    Forms\Components\Select::make('interest_due_chart_of_account_id')
                                        ->label('Interest Due')
                                        ->options(ChartOfAccount::where('category', 'liability')->get()->pluck('name', 'id'))
                                        ->required(),
                                    Forms\Components\Select::make('interest_paid_chart_of_account_id')
                                        ->label('Interest Paid')
                                        ->options(ChartOfAccount::where('category', 'revenue')->get()->pluck('name', 'id'))
                                        ->required(),
                                    Forms\Components\Select::make('sms_charges_chart_of_account_id')
                                        ->label('SMS Charges')
                                        ->options(ChartOfAccount::where('category', 'revenue')->get()->pluck('name', 'id'))
                                        ->required(),
                                    Forms\Components\Select::make('loan_extension_fees_chart_of_account_id')
                                        ->label('Loan Extension Fees')
                                        ->options(ChartOfAccount::where('category', 'revenue')->get()->pluck('name', 'id'))
                                        ->required(),
                                    Forms\Components\Select::make('penalties_chart_of_account_id')
                                        ->label('Penalties')
                                        ->options(ChartOfAccount::where('category', 'revenue')->get()->pluck('name', 'id'))
                                        ->required(),
                                    Forms\Components\Select::make('bad_debts_provision_chart_of_account_id')
                                        ->label('Bad Debts Provision')
                                        ->options(ChartOfAccount::where('category', 'expense')->get()->pluck('name', 'id'))
                                        ->required(),
                                    Forms\Components\Select::make('write_offs_chart_of_account_id')
                                        ->label('Write offs')
                                        ->options(ChartOfAccount::where('category', 'expense')->get()->pluck('name', 'id'))
                                        ->required(),
                                    Forms\Components\Select::make('recovered_written_off_chart_of_account_id')
                                        ->label('Recovered Written Off')
                                        ->options(ChartOfAccount::where('category', 'revenue')->get()->pluck('name', 'id'))
                                        ->required(),
                                    Forms\Components\Toggle::make('auto_disburse')
                                        ->label('Auto Disburse')
                                        ->required(),


                        ],
                        default => [],

                    })
                    ->key('dynamicClientTypeFields')->columns(2),

                    Forms\Components\Toggle::make('active')
                                                ->required(),

            ]),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product_code')
                    ->label('Product Code')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label(' Product Name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('default_principal')
                    ->label('Principal')
                    ->Money(Currency::where('is_default', 1)->first()->symbol)
                    ->sortable(),
                Tables\Columns\TextColumn::make('default_loan_term')
                    ->label('Loan Term')
                    ->suffix(' Days')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('default_interest_rate')
                    ->label('Interest Rate')
                    ->suffix('%')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('active')
                    ->boolean(),
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

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([

                    Fieldset::make('Loan Product Details')
                    ->schema([
                        TextEntry::make('name')
                            ->label('Product Name')
                            ->color('primary'),
                        TextEntry::make('default_principal')
                            ->label('Principal')
                            ->badge()
                            ->color('success')
                            ->money(Currency::where('is_default', 1)->first()->symbol),
                        TextEntry::make('default_loan_term')
                            ->label('Loan Term')
                            ->suffix(' Days')
                            ->color('primary'),
                        TextEntry::make('default_interest_rate')
                            ->label('Interest Rate')
                            ->suffix('%')
                            ->color('success'),
                        TextEntry::make('description')
                            ->color('primary'),
                        TextEntry::make('repayment_frequency')
                            ->label('Repayment Frequency')
                            ->suffix(' Days')
                            ->color('primary'),
                        TextEntry::make('interest_methodology')
                            ->color('primary'),
                        TextEntry::make('amortization_method')
                            ->color('primary'),
                        TextEntry::make('interest_rate_type')
                            ->color('primary'),


                    ])

            ]);

    }

    public static function getRelations(): array
    {
        return [
            LoanChargesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLoanProducts::route('/'),
            'create' => Pages\CreateLoanProduct::route('/create'),
            'edit' => Pages\EditLoanProduct::route('/{record}/edit'),
            'view' => Pages\ViewLoanProduct::route('/{record}'),
        ];
    }
}
