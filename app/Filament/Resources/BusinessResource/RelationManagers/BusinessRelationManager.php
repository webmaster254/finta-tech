<?php

namespace App\Filament\Resources\BusinessResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Fieldset;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\RelationManagers\RelationManager;

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
                            ->prefix('KES')
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set, ?int $state) => 
                            self::updateCostOfSales($get, $set))
                            ->required(),
                        Forms\Components\TextInput::make('operating_capital')
                            ->prefix('KES')
                            ->label('Operating Capital')
                            ->numeric()
                            ->required(),
                        Forms\Components\TextInput::make('average_weekly_sales')
                            ->prefix('KES')
                            ->label('Average Weekly Sales')
                            ->numeric()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set, ?int $state) => 
                            self::updateGrossProfit($get, $set))
                            ->required(),
                        Forms\Components\TextInput::make('average_weekly_purchase')
                            ->prefix('KES')
                            ->label('Average Weekly Purchase')
                            ->numeric()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set, ?int $state) => 
                            self::updateCostOfSales($get, $set))
                            ->required(),
                        Forms\Components\TextInput::make('average_weekly_stock_balance')
                            ->prefix('KES')
                            ->label('Avg Weekly Stock Bal.')
                            ->numeric()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set, ?int $state) => 
                            self::updateCostOfSales($get, $set))
                            ->required(),
                        Forms\Components\TextInput::make('cost_of_sales')
                            ->prefix('KES')
                            ->label('Cost of Sales')
                            ->readOnly()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set, ?int $state) => 
                            self::updateGrossProfit($get, $set))
                            ->numeric()
                            ->required(),
                        Forms\Components\TextInput::make('gross_profit')
                            ->prefix('KES')
                            ->label('Gross Profit')
                            ->numeric()
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
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set, ?int $state) => 
                            self::updateHouseholdExpense($get, $set))
                            ->required(),
                        Forms\Components\TextInput::make('hs_electricity')
                            ->prefix('KES')
                            ->label('House Electricity')
                            ->numeric()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set, ?int $state) => 
                            self::updateHouseholdExpense($get, $set))
                            ->required(),
                        Forms\Components\TextInput::make('hs_food')
                            ->prefix('KES')
                            ->label('House Food')
                            ->numeric()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set, ?int $state) => 
                            self::updateHouseholdExpense($get, $set))
                            ->required(),
                        Forms\Components\TextInput::make('hs_transport')
                            ->prefix('KES')
                            ->label('House Transport')
                            ->numeric()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set, ?int $state) => 
                            self::updateHouseholdExpense($get, $set))
                            ->required(),
                        Forms\Components\TextInput::make('clothings')
                            ->prefix('KES')
                            ->label('Clothings')
                            ->numeric()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set, ?int $state) => 
                            self::updateHouseholdExpense($get, $set))
                            ->required(),
                        Forms\Components\TextInput::make('school_fees')
                            ->prefix('KES')
                            ->label('School Fees')
                            ->numeric()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set, ?int $state) => 
                            self::updateHouseholdExpense($get, $set))
                            ->required(),
                        Forms\Components\TextInput::make('hs_total')
                            ->numeric()
                            ->readOnly()
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
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set, ?int $state) => 
                            self::updateBusinessExpense($get, $set))
                            ->required(),
                        Forms\Components\TextInput::make('bs_electricity')
                            ->prefix('KES')
                            ->label('Business Electricity')
                            ->numeric()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set, ?int $state) => 
                            self::updateBusinessExpense($get, $set))
                            ->required(),
                        Forms\Components\TextInput::make('bs_license')
                            ->prefix('KES')
                            ->label('Business License')
                            ->numeric()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set, ?int $state) => 
                            self::updateBusinessExpense($get, $set))
                            ->required(),
                        Forms\Components\TextInput::make('bs_transport')
                            ->prefix('KES')
                            ->label('Business Transport')
                            ->numeric()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set, ?int $state) => 
                            self::updateBusinessExpense($get, $set))
                            ->required(),
                        Forms\Components\TextInput::make('bs_wages')
                            ->prefix('KES')
                            ->label('Business Wages')
                            ->numeric()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set, ?int $state) => 
                            self::updateBusinessExpense($get, $set))
                            ->required(),
                        Forms\Components\TextInput::make('bs_contributions')
                            ->prefix('KES')
                            ->label('Business Contributions')
                            ->numeric()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set, ?int $state) => 
                            self::updateBusinessExpense($get, $set))
                            ->required(),
                        Forms\Components\TextInput::make('bs_loan_repayment')
                            ->prefix('KES')
                            ->label('Loan Repayment')
                            ->numeric()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set, ?int $state) => 
                            self::updateBusinessExpense($get, $set))
                            ->required(),
                        Forms\Components\TextInput::make('bs_other_drawings')
                            ->prefix('KES')
                            ->label('Business Other Drawings')
                            ->numeric()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set, ?int $state) => 
                            self::updateBusinessExpense($get, $set))
                            ->required(),
                        Forms\Components\TextInput::make('bs_spoilts_goods')
                            ->prefix('KES')
                            ->label('Business Spoilts Goods')
                            ->numeric()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set, ?int $state) => 
                            self::updateBusinessExpense($get, $set))
                            ->required(),
                        Forms\Components\TextInput::make('owner_salary')
                            ->prefix('KES')
                            ->label('Owner Salary')
                            ->numeric()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set, ?int $state) => 
                            self::updateBusinessExpense($get, $set))
                            ->required(),
                        Forms\Components\TextInput::make('bs_total')
                            ->prefix('KES')
                            ->label('Business Total')
                            ->numeric()
                            ->readOnly()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set, ?int $state) => 
                            self::updateNetProfit($get, $set, $state))
                            ->required(),
                    ])
                    ->columns(4),
                Section::make('Net Profit')
                    ->schema([
                        Forms\Components\TextInput::make('net_profit')
                        ->prefix('KES')
                        ->label('Net Profit')
                        ->placeholder(function (Forms\Get $get, Forms\Set $set, ?int $state) {
                            $get('gross_profit');
                            $hsTotal= $get('hs_total');
                            $bsTotal= $get('bs_total');
                            $set('net_profit', $get('gross_profit') - $hsTotal - $bsTotal);
                            return $get('gross_profit') - $hsTotal - $bsTotal;
                        })
                        ->numeric()
                        ->readOnly()
                        ->required(),
                    ])
                    ->columns(2)
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('business.name')
                    ->label('Business')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('current_stock')
                    ->label('Current Stock')
                    ->prefix('KES')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cost_of_sales')
                    ->label('Cost of Sales')
                    ->prefix('KES')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('hs_total')
                    ->label('Household Expense Total')
                    ->prefix('KES')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('bs_total')
                    ->label('Business Expense Total')
                    ->prefix('KES')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('gross_profit')
                    ->label('Gross Profit')
                    ->prefix('KES')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('net_profit')
                    ->label('Net Profit')
                    ->prefix('KES')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                ->label('Add Business Overview')
                ->icon('heroicon-o-document-text')
                ->modalHeading('Add Business Overview'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
        $set('net_profit', $get('gross_profit') - $get('bs_total') - $get('hs_total'));
    }
}
