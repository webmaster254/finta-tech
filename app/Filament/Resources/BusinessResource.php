<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Client;
use App\Enums\Industry;
use App\Enums\Ownership;
use App\Models\Business;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Enums\BusinessType;
use App\Enums\BusinessStatus;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\Split;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Placeholder;
use Filament\Infolists\Components\TextEntry;
use App\Filament\Resources\BusinessResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\BusinessResource\RelationManagers;
use Filament\Infolists\Components\Fieldset as infolistfieldset;
use Joaopaulolndev\FilamentPdfViewer\Infolists\Components\PdfViewerEntry;
use App\Filament\Resources\BusinessResource\RelationManagers\BusinessRelationManager;

class BusinessResource extends Resource
{
    protected static ?string $model = Business::class;

    protected static ?string $navigationLabel = 'Business Information';
    protected static ?string $navigationGroup = 'Clients Management';
    protected static ?int $navigationSort = 3;


   
    public static function form(Form $form): Form
{
    return $form
        
        ->schema([
            Wizard::make()
            ->columnSpanFull()
            ->schema([
                Wizard\Step::make('General Business Information')
                    ->description('Enter business information')
                    ->columns(2)
                    ->schema(self::getGeneralBusinessInformation()),
                Wizard\Step::make('Business Overview')
                    ->description('Enter business overview information')
                    ->columns(2)
                    ->schema(self::getBusinessOverviewInformation()),
            ])
           
        ]);
    }

    public static function getGeneralBusinessInformation(): array
    {
        return [
            Card::make()
            ->columns(3)
            ->schema([
                Forms\Components\Hidden::make('status')
                ->default('pending'),
            Forms\Components\Select::make('client_id')
            ->live(onBlur: true)
            ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, ?int $state) {
                $client = Client::find($state);
                $set('client_name', $client->full_name);
            })
            ->options(Client::where('source_of_income', 'Business')->get()->mapWithKeys(function ($client) {
                return [$client->id => $client->account_number];
                }))
                ->label('Client')
                ->required(),
            Forms\Components\TextInput::make('client_name')
                ->label('Client Name')
                ->disabled(),

                
            Forms\Components\TextInput::make('name')
                ->label('Business Name')
                ->required()
                ->maxLength(255),
            Forms\Components\Select::make('business_type')
                ->options(BusinessType::class)
                ->required(),
            Forms\Components\TextInput::make('description')
                ->maxLength(255),
            Forms\Components\Select::make('industry')
                ->options(Industry::class)
                ->required(),
            Forms\Components\DatePicker::make('establishment_date')
                ->native(false)
                ->required()
                ->before(now())
                ->maxDate(now()->subMonths(6))
                ->rule(function () {
                    return function (string $attribute, $value, \Closure $fail) {
                        $date = \Carbon\Carbon::parse($value);
                        if ($date->isAfter(now()->subMonths(6))) {
                            $fail("The business must be at least 6 months old.");
                        }
                    };
                })
                ->helperText('Business must be at least 6 months old'),
            Forms\Components\TextInput::make('location')
                ->maxLength(255),
            Forms\Components\Select::make('ownership')
                ->options(Ownership::class)
                ->required(),
            Forms\Components\Select::make('premise_ownership')
                ->options([
                    'owned' => 'Owned',
                    'rented' => 'Rented',
                    'leased' => 'Leased',
                ])
                ->required(),
            Forms\Components\TextInput::make('employees')
                ->numeric()
                ->required(),
            Forms\Components\Select::make('sector')
                ->options([
                    'msme' => 'MSME',
                    'sme' => 'SME',
                ])
                ->required(),
            Forms\Components\TextInput::make('major_products')
                ->maxLength(255),
            Forms\Components\TextInput::make('major_suppliers')
                ->maxLength(255),
            Forms\Components\TextInput::make('major_customers')
                ->maxLength(255),
            Forms\Components\TextInput::make('major_competitors')
                ->maxLength(255),
            Forms\Components\TextInput::make('strengths')
                ->maxLength(255),
            Forms\Components\TextInput::make('weaknesses')
                ->maxLength(255),
            Forms\Components\TextInput::make('opportunities')
                ->maxLength(255),
            Forms\Components\TextInput::make('threats')
                ->maxLength(255),
            Forms\Components\TextInput::make('mitigations')
                ->maxLength(255),
            Forms\Components\Select::make('insurance_service')
               ->label('Is Business Insured')
                ->live(onBlur: true)
                ->options([
                    '1' => 'Yes',
                    '0' => 'No',
                ])
                ->required(),
            Forms\Components\TextInput::make('insurance')
                ->label('Insurance Ref Number')
                ->visible(function (Forms\Get $get) {
                    return $get('insurance_service') === '1';
                })
                ->required(),
            Forms\Components\FileUpload::make('insurance_document')
                ->label('Insurance Document')
                ->visible(function (Forms\Get $get) {
                    return $get('insurance_service') === '1';
                })
                ->required(),
            Forms\Components\FileUpload::make('trading_license')
                ->label('Trading License'),
            Forms\Components\FileUpload::make('business_permit')
                ->label('Business Permit'),
            Forms\Components\FileUpload::make('certificate_of_incorporation')
                ->label('Certificate of Incorporation'),
            Forms\Components\FileUpload::make('health_certificate')
                ->label('Health Certificate'),
            Forms\Components\TextInput::make('registration_number')
                ->label('Registration Number')
                ->maxLength(255),
            Forms\Components\Select::make('record_maintained')
                ->options([
                    'none' => 'None',
                    'audited_books' => 'Audited Books',
                    'black_book' => 'Black Book',
                    'digital_book' => 'Digital Book',
                ])
                ->required(),
            ]),
        ];
    }

    public static function getBusinessOverviewInformation(): array
    {
        return [
            Card::make()
            ->relationship('business_overview')
            ->schema([
            Fieldset::make('Business Overview')
                    ->schema([
                        Forms\Components\TextInput::make('current_stock')
                            ->numeric()
                            ->default(0)
                            ->prefix('KES')
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set, ?int $state) => 
                            self::updateCostOfSales($get, $set))
                            ->required(),
                        Forms\Components\TextInput::make('operating_capital')
                            ->prefix('KES')
                            ->label('Operating Capital')
                            ->numeric()
                            ->default(0)
                            ->required(),
                        Forms\Components\TextInput::make('average_weekly_sales')
                            ->prefix('KES')
                            ->label('Average Weekly Sales')
                            ->numeric()
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set, ?int $state) => 
                            self::updateGrossProfit($get, $set))
                            ->required(),
                        Forms\Components\TextInput::make('average_weekly_purchase')
                            ->prefix('KES')
                            ->label('Average Weekly Purchase')
                            ->numeric() 
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set, ?int $state) => 
                            self::updateCostOfSales($get, $set))
                            ->required(),
                        Forms\Components\TextInput::make('average_weekly_stock_balance')
                            ->prefix('KES')
                            ->label('Avg Weekly Stock Bal.')
                            ->numeric()
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set, ?int $state) => 
                            self::updateCostOfSales($get, $set))
                            ->required(),
                        Forms\Components\TextInput::make('cost_of_sales')
                            ->prefix('KES')
                            ->label('Cost of Sales')
                            ->readOnly()
                            ->default(0)
                            ->live()
                            ->placeholder(function (Forms\Get $get, Forms\Set $set, ?int $state) {
                                return $get('current_stock') + $get('average_weekly_purchase') - $get('average_weekly_stock_balance');
                            })
                            ->numeric()
                            ->required(),
                        Forms\Components\TextInput::make('gross_profit')
                            ->prefix('KES')
                            ->label('Weekly Gross Profit')
                            ->numeric()
                            ->default(0)
                            ->placeholder(function (Forms\Get $get, Forms\Set $set, ?int $state) {
                                return $get('average_weekly_sales') - $get('cost_of_sales');
                            })
                            ->readOnly()
                            ->required(),
                        
                    ])
                    ->columns(4),
                Fieldset::make('Average Weekly Household Expenses')
                    ->schema([
                        Forms\Components\TextInput::make('house_rent')
                            ->prefix('KES')
                            ->label('House Rent')
                            ->numeric()
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set, ?int $state) => 
                            self::updateHouseholdExpense($get, $set))
                            ->required(),
                        Forms\Components\TextInput::make('hs_electricity')
                            ->prefix('KES')
                            ->label('House Electricity')
                            ->numeric()
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set, ?int $state) => 
                            self::updateHouseholdExpense($get, $set))
                            ->required(),
                        Forms\Components\TextInput::make('hs_food')
                            ->prefix('KES')
                            ->label('House Food')
                            ->numeric()
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set, ?int $state) => 
                            self::updateHouseholdExpense($get, $set))
                            ->required(),
                        Forms\Components\TextInput::make('hs_transport')
                            ->prefix('KES')
                            ->label('House Transport')
                            ->numeric()
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set, ?int $state) => 
                            self::updateHouseholdExpense($get, $set))
                            ->required(),
                        Forms\Components\TextInput::make('clothings')
                            ->prefix('KES')
                            ->label('Clothings')
                            ->numeric()
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set, ?int $state) => 
                            self::updateHouseholdExpense($get, $set))
                            ->required(),
                        Forms\Components\TextInput::make('school_fees')
                            ->prefix('KES')
                            ->label('School Fees')
                            ->numeric()
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set, ?int $state) => 
                            self::updateHouseholdExpense($get, $set))
                            ->required(),
                        Forms\Components\TextInput::make('hs_total')
                            ->numeric()
                            ->readOnly()
                            ->default(0)
                            ->label('Household Total')
                            ->live()
                            ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set, ?int $state) => 
                            self::updateNetProfit($get, $set, $state))
                            ->prefix('KES')
                            ->required(),
                    ])
                    ->columns(4),
                Fieldset::make('Average Weekly Business Expenses')
                    ->schema([
                        Forms\Components\TextInput::make('bs_rent')
                            ->prefix('KES')
                            ->label('Business Rent')
                            ->numeric()
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set, ?int $state) => 
                            self::updateBusinessExpense($get, $set))
                            ->required(),
                        Forms\Components\TextInput::make('bs_electricity')
                            ->prefix('KES')
                            ->label('Business Electricity')
                            ->numeric()
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set, ?int $state) => 
                            self::updateBusinessExpense($get, $set))
                            ->required(),
                        Forms\Components\TextInput::make('bs_license')
                            ->prefix('KES')
                            ->label('Business License')
                            ->numeric()
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set, ?int $state) => 
                            self::updateBusinessExpense($get, $set))
                            ->required(),
                        Forms\Components\TextInput::make('bs_transport')
                            ->prefix('KES')
                            ->label('Business Transport')
                            ->numeric()
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set, ?int $state) => 
                            self::updateBusinessExpense($get, $set))
                            ->required(),
                        Forms\Components\TextInput::make('bs_wages')
                            ->prefix('KES')
                            ->label('Business Wages')
                            ->numeric()
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set, ?int $state) => 
                            self::updateBusinessExpense($get, $set))
                            ->required(),
                        Forms\Components\TextInput::make('bs_contributions')
                            ->prefix('KES')
                            ->label('Business Contributions')
                            ->numeric()
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set, ?int $state) => 
                            self::updateBusinessExpense($get, $set))
                            ->required(),
                        Forms\Components\TextInput::make('bs_loan_repayment')
                            ->prefix('KES')
                            ->label('Loan Repayment')
                            ->numeric()
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set, ?int $state) => 
                            self::updateBusinessExpense($get, $set))
                            ->required(),
                        Forms\Components\TextInput::make('bs_other_drawings')
                            ->prefix('KES')
                            ->label('Business Other Drawings')
                            ->numeric()
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set, ?int $state) => 
                            self::updateBusinessExpense($get, $set))
                            ->required(),
                        Forms\Components\TextInput::make('bs_spoilts_goods')
                            ->prefix('KES')
                            ->label('Business Spoilts Goods')
                            ->numeric()
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set, ?int $state) => 
                            self::updateBusinessExpense($get, $set))
                            ->required(),
                        Forms\Components\TextInput::make('owner_salary')
                            ->prefix('KES')
                            ->label('Owner Salary')
                            ->numeric()
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set, ?int $state) => 
                            self::updateBusinessExpense($get, $set))
                            ->required(),
                        Forms\Components\TextInput::make('bs_total')
                            ->prefix('KES')
                            ->label('Business Total')
                            ->default(0)
                            ->numeric()
                            ->readOnly()
                            ->live()
                            ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set, ?int $state) => 
                            self::updateNetProfit($get, $set, $state))
                            ->required(),
                    ])
                    ->columns(4),
                Fieldset::make('Net Profit')
                    ->schema([
                        Forms\Components\TextInput::make('net_profit')
                        ->prefix('KES')
                        ->live()
                        ->label('Weekly Net Profit')
                        ->placeholder(function (Forms\Get $get, Forms\Set $set, ?int $state) {
                            $get('gross_profit');
                            $hsTotal= $get('hs_total');
                            $bsTotal= $get('bs_total');
                            $profit = $get('gross_profit') - $hsTotal - $bsTotal;
                            $set('net_profit', $profit);
                            // Calculate loan affordability based on net profit
                              if ($profit > 0) {
                                  // Weekly net profit
                                  $weeklyNetProfit = $profit;
                                  
                                  // Calculate 75% of weekly net profit (affordable weekly installment)
                                  $affordableWeeklyInstallment = $weeklyNetProfit * 0.75;
                                  
                                  // Calculate affordable daily installment
                                  $affordableDailyInstallment = $affordableWeeklyInstallment / 7;
                                  
                                  // Calculate monthly payable (P+I)
                                  $monthlyPayable = $affordableDailyInstallment * 30;
                                  
                                  // Calculate principal (P = monthly payable - 30% interest)
                                  //$interestAmount = $monthlyPayable * 0.30;
                                  $principal = $monthlyPayable * (100/130);
                                  
                                  // Round to nearest 100
                                  $suggestedLoanLimit = round($principal, -2);
                                  
                                  // Update affordability field
                                  $set('affordability', $suggestedLoanLimit);
                              } else {
                                  // If net profit is zero or negative, set affordability to zero
                                  $set('affordability', 0);
                              }
                          return $profit;
                      })
                        ->numeric()
                        ->readOnly()
                        ->required(),
                    Forms\Components\TextInput::make('affordability')
                      ->label('Monthly Affordability')
                      ->numeric()
                      ->readOnly()
                      ->placeholder(function (Forms\Get $get, Forms\Set $set, ?int $state) {
                        $netProfit = $get('net_profit');
                        $affordableWeeklyInstallment = $netProfit * 0.75;
                        $affordableDailyInstallment = $affordableWeeklyInstallment / 7;
                        $monthlyPayable = $affordableDailyInstallment * 30;
                        $principal = $monthlyPayable * (100/130);
                        $suggestedLoanLimit = round($principal, -2);
                        return $suggestedLoanLimit;
                      })
                      ->prefix('KES'),
                    ])
                    ->columns(2),

                Fieldset::make('Mpesa Statement')
                    ->schema([
                        Forms\Components\FileUpload::make('mpesa_statement')
                            ->label('Mpesa Statement')
                            ->acceptedFileTypes(['application/pdf'])
                            ->required(),
                        Forms\Components\TextInput::make('mpesa_code')
                            ->label('Mpesa Code')
                            ->required(),
                    ])
                    ->columns(2),
                Fieldset::make('Mpesa Summary')
                    ->schema([
                        Forms\Components\FileUpload::make('mpesa_summary')
                            ->label('Mpesa Summary')
                            ->acceptedFileTypes(['application/pdf'])
                            ->required(),
                    ])
                    ->columns(2),
            ])

        ];
    }
    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('No businesses yet')
            ->emptyStateDescription('You have not created any businesses yet.')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('business_type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('industry')
                    ->searchable(),
                Tables\Columns\TextColumn::make('location')
                    ->searchable(),
                Tables\Columns\TextColumn::make('ownership')
                    ->searchable(),
                Tables\Columns\TextColumn::make('premise_ownership')
                    ->searchable(),
                Tables\Columns\TextColumn::make('employees')
                    ->searchable(),
                Tables\Columns\TextColumn::make('sector')
                    ->searchable(),
                // Tables\Columns\TextColumn::make('status')
                //     ->badge(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading('View Business')
                    ->modalDescription('View business information')
                    ->modalIcon('heroicon-o-building-office-2')
                    ->modalIconColor('success'),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
                Split::make([
                infolistfieldset::make('Business Information')
                    ->schema([
                        TextEntry::make('client.account_number')
                            ->label('Client Account Number')
                            ->color('info'),
                        TextEntry::make('name')
                            ->label('Business Name')
                            ->color('info'),
                        TextEntry::make('business_type')
                            ->label('Business Type')
                            ->color('info'),
                        TextEntry::make('industry')
                            ->label('Industry')
                            ->color('info'),
                        TextEntry::make('location')
                            ->label('Location')
                            ->color('info'),
                        TextEntry::make('ownership')
                            ->label('Ownership')
                            ->color('info'),
                        TextEntry::make('premise_ownership')
                            ->label('Premise Ownership')
                            ->color('info'),
                        TextEntry::make('employees')
                            ->label('Employees')
                            ->color('info'),
                        TextEntry::make('sector')
                            ->label('Sector')
                            ->color('info'),
                        // TextEntry::make('status')
                        //     ->label('Status')
                        //     ->color('info'),
                        TextEntry::make('registration_number')
                            ->label('Registration Number')
                            ->color('info'),
                        
                        TextEntry::make('insurance')
                            ->label('Insurance Ref Number')
                            ->color('info'),
                        
                        TextEntry::make('establishment_date')
                            ->label('Establishment Date')
                            ->color('info'),
                        TextEntry::make('establishment_date')
                            ->label('Business Age')
                            ->formatStateUsing(function ($state): string {
                                if (is_string($state)) {
                                    $date = \Carbon\Carbon::parse($state);
                        } else {
                            $date = $state;
                        }
                        return $date->age . ' years';
                    })
                            ->color('info'),
                        TextEntry::make('record_maintained')
                            ->label('Record Maintained')
                            ->color('info'),
                        TextEntry::make('assessed_by.full_name')
                            ->label('Assessed By')
                            ->color('info'),
                        TextEntry::make('assessed_date')
                            ->label('Assessed Date')
                            ->color('info'),
                        TextEntry::make('status')
                            ->label('Status')
                            ->color('info'),
                    ])->columns(3),
                ]),
                Split::make([
                    infolistfieldset::make('More Business Information')
                    ->schema([
                        
                        TextEntry::make('major_products')
                            ->label('Major Products')
                            ->color('info'),
                        TextEntry::make('major_suppliers')
                            ->label('Major Suppliers')
                            ->color('info'),
                        TextEntry::make('major_customers')
                            ->label('Major Customers')
                            ->color('info'),
                        TextEntry::make('major_competitors')
                            ->label('Major Competitors')
                            ->color('info'),
                        TextEntry::make('strengths')
                            ->label('Strengths')
                            ->color('info'),
                        TextEntry::make('weaknesses')
                            ->label('Weaknesses')
                            ->color('info'),
                        TextEntry::make('opportunities')
                            ->label('Opportunities')
                            ->color('info'),
                        TextEntry::make('threats')
                            ->label('Threats')
                            ->color('info'),
                        TextEntry::make('mitigations')
                            ->label('Mitigations')
                            ->color('info'),
                        
                    ])->columns(3),
                ]),
                infolistfieldset::make('Business Documents')
                    ->schema([
                        PdfViewerEntry::make('insurance_document')
                            ->label('Insurance Document')
                            ->minHeight('40svh'),
                        PdfViewerEntry::make('trading_license')
                            ->label('Trading License')
                            ->minHeight('40svh'),
                        PdfViewerEntry::make('business_permit')
                            ->label('Business Permit')
                            ->minHeight('40svh'),
                        PdfViewerEntry::make('certificate_of_incorporation')
                            ->label('Certificate of Incorporation')
                            ->minHeight('40svh'),
                        PdfViewerEntry::make('health_certificate')
                            ->label('Health Certificate')
                            ->minHeight('40svh'),
                    ])->columnSpanFull(),
               
            ]);
    }
    private static function updateGrossProfit(Forms\Get $get, Forms\Set $set):void
    {
        $set('gross_profit', $get('average_weekly_sales') - $get('cost_of_sales'));
    }

    private static function updateHouseholdExpense(Forms\Get $get, Forms\Set $set):void
    {
        $set('hs_total', $get('house_rent') + $get('hs_electricity') + $get('hs_food') + $get('hs_transport') + $get('clothings') + $get('school_fees'));
    }

    private static function updateBusinessExpense(Forms\Get $get, Forms\Set $set):void
    {
        $set('bs_total', 
            floatval($get('bs_rent')) + 
            floatval($get('bs_electricity')) + 
            floatval($get('bs_license')) + 
            floatval($get('bs_transport')) + 
            floatval($get('bs_wages')) + 
            floatval($get('bs_contributions')) + 
            floatval($get('bs_loan_repayment')) + 
            floatval($get('bs_other_drawings')) + 
            floatval($get('bs_spoilts_goods')) + 
            floatval($get('owner_salary'))
        );
    }

    private static function updateCostOfSales(Forms\Get $get, Forms\Set $set):void
    {
        $set('cost_of_sales',$get('current_stock') + $get('average_weekly_purchase') - $get('average_weekly_stock_balance')  );
    }
   private static function updateNetProfit(Forms\Get $get, Forms\Set $set):void
    {
        $set('net_profit', $get('gross_profit') - $get('bs_total') - $get('hs_total'));
    }

    public static function getRelations(): array
    {
        return [
            //BusinessRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBusinesses::route('/'),
            'create' => Pages\CreateBusiness::route('/create'),
            'edit' => Pages\EditBusiness::route('/{record}/edit'),
            'view' => Pages\ViewBusiness::route('/{record}'),
        ];
    }
}
