<?php

namespace App\Filament\Resources\BusinessResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\BusinessOverview;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Fieldset;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Placeholder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\RelationManagers\RelationManager;
use Joaopaulolndev\FilamentPdfViewer\Forms\Components\PdfViewerField;

class BusinessRelationManager extends RelationManager
{
    protected static string $relationship = 'business_overview';

    public function form(Form $form): Form
    {
        return $form
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
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set, ?int $state) => 
                            self::updateGrossProfit($get, $set))
                            ->numeric()
                            ->required(),
                        Forms\Components\TextInput::make('gross_profit')
                            ->prefix('KES')
                            ->label('Weekly Gross Profit')
                            ->numeric()
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set, ?int $state) => 
                            self::updateNetProfit($get, $set))
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
                            ->live(onBlur: true)
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
                            ->live(onBlur: true)
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
                     ->prefix('KES')
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
                      }),
                    ])
                    ->columns(2),

                Fieldset::make('Mpesa Statement')
                    ->schema([
                        PdfViewerField::make('mpesa_statement')
                            ->label('Mpesa Statement')
                            ->minHeight('40svh'),
                        Forms\Components\TextInput::make('mpesa_code')
                            ->label('Mpesa Code')
                            ->required(),
                        PdfViewerField::make('mpesa_summary')
                        ->label('Mpesa Summary')
                        ->required(),
                    ])
                    ->columns(2),

            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('business.name')
                    ->label('Business Name'),
                Tables\Columns\TextColumn::make('current_stock')
                    ->label('Current Stock')
                    ->prefix('KES ')
                    ->numeric(),
                Tables\Columns\TextColumn::make('cost_of_sales')
                    ->label('Cost of Sales')
                    ->prefix('KES ')
                    ->numeric(),
                Tables\Columns\TextColumn::make('hs_total')
                    ->label('Household Expense Total')
                    ->prefix('KES ')
                    ->numeric(),
                Tables\Columns\TextColumn::make('bs_total')
                    ->label('Business Expense Total')
                    ->prefix('KES ')
                    ->numeric(),
                Tables\Columns\TextColumn::make('gross_profit')
                    ->label('Gross Profit')
                    ->prefix('KES ')
                    ->numeric(),
                Tables\Columns\TextColumn::make('net_profit')
                    ->label('Net Profit')
                    ->prefix('KES ')
                    ->numeric(),
                Tables\Columns\TextColumn::make('monthly_affordability')
                    ->label('Loan Affordability')
                    ->prefix('KES ')
                    ->getStateUsing(function (BusinessOverview $record) {
                         $p= $record->net_profit * 0.75;
                        $affordability = ($p/7)*30;
                        return $affordability;
                    })
                    ->numeric(),
                Tables\Columns\TextColumn::make('affordability')
                    ->label('Loan Limit')
                    ->prefix('KES ')
                    ->numeric(),
                // Tables\Columns\TextColumn::make('mpesa_code')
                //     ->label('Mpesa Code'),
                // Tables\Columns\TextColumn::make('mpesa_statement')
                //     ->label('Mpesa Statement')
                //     ->url(function (BusinessOverview $record) {
                //         return $record->mpesa_statement;
                //     }),
                // Tables\Columns\TextColumn::make('mpesa_summary')
                //     ->label('Mpesa Summary')
                //     ->url(function (BusinessOverview $record) {
                //         return $record->mpesa_summary;
                //     }),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // Tables\Actions\CreateAction::make()
                // ->createAnother(false)
                // ->successNotificationTitle('Business Overview added')
                // ->label('Add Business Overview')
                // ->icon('heroicon-o-document-text')
                // ->modalHeading('Add Business Overview'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                // Tables\Actions\EditAction::make(),
                // Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    //Tables\Actions\DeleteBulkAction::make(),
                ]),
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
        $set('bs_total', $get('bs_rent') + $get('bs_electricity') + $get('bs_license') + $get('bs_transport') + $get('bs_wages') + $get('bs_contributions') + $get('bs_loan_repayment') + $get('bs_other_drawings') + $get('bs_spoilts_goods') + $get('owner_salary'));
    }

    private static function updateCostOfSales(Forms\Get $get, Forms\Set $set):void
    {
        $set('cost_of_sales',$get('current_stock') + $get('average_weekly_purchase') - $get('average_weekly_stock_balance')  );
    }
    private static function updateNetProfit(Forms\Get $get, Forms\Set $set):void
    {
        // Calculate net profit
        $netProfit = $get('gross_profit') - $get('bs_total') - $get('hs_total');
        $set('net_profit', $netProfit);
        
        // Calculate loan affordability based on net profit
        if ($netProfit > 0) {
            // Weekly net profit
            $weeklyNetProfit = $netProfit;
            
            // Calculate 75% of weekly net profit (affordable weekly installment)
            $affordableWeeklyInstallment = $weeklyNetProfit * 0.75;
            
            // Calculate affordable daily installment
            $affordableDailyInstallment = $affordableWeeklyInstallment / 7;
            
            // Calculate monthly payable (P+I)
            $monthlyPayable = $affordableDailyInstallment * 30;
            
            // Calculate principal (P = monthly payable - 30% interest)
            $interestAmount = $monthlyPayable * 0.30;
            $principal = $monthlyPayable - $interestAmount;
            
            // Round to nearest 100
            $suggestedLoanLimit = round($principal, -2);
            
            // Update affordability field
            $set('affordability', $suggestedLoanLimit);
        } else {
            // If net profit is zero or negative, set affordability to zero
            $set('affordability', 0);
        }
    }
}
