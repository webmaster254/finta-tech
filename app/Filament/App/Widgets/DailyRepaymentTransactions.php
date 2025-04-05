<?php

namespace App\Filament\App\Widgets;

use Filament\Tables;
use App\Models\Currency;
use Filament\Tables\Table;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Query\Builder;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\QueryBuilder;
use App\Filament\Resources\MpesaC2BResource;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables\Filters\QueryBuilder\Constraints\DateConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\TextConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\NumberConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\SelectConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\BooleanConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\RelationshipConstraint;

class DailyRepaymentTransactions extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';
    protected static ?string $pollingInterval = '10s';


    protected static ?int $sort = 6;
    public function table(Table $table): Table
    {
        return $table
        ->striped()
        ->filters([
            QueryBuilder::make()
                ->constraints([
                    DateConstraint::make('created_at')
                                ->label('Date'),
                ]),
                    ], layout: FiltersLayout::AboveContent)
        ->query(MpesaC2BResource::getEloquentQuery())
        ->defaultSort('created_at', 'desc')
        ->columns([
            Tables\Columns\TextColumn::make('Transaction_ID')
                    ->searchable(),
                Tables\Columns\TextColumn::make('Amount')
                    ->numeric()
                    ->money(Currency::where('is_default', 1)->first()->symbol)
                    ->summarize(Sum::make()
                                ->money(Currency::where('is_default', 1)->first()->symbol)
                                ->label('Total')),
                Tables\Columns\TextColumn::make('Account_Number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('Transaction_Time')
                    ->dateTime()
                    ->searchable(),
                Tables\Columns\TextColumn::make('Phonenumber')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('FirstName')
                    ->searchable(),
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

        ]);


    }
}
