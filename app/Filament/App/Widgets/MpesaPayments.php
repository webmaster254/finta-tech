<?php

namespace App\Filament\App\Widgets;

use Filament\Tables;
use App\Models\Currency;
use App\Models\MpesaC2B;
use App\Models\Loan\Loan;
use Filament\Tables\Table;
use Filament\Tables\Filters\Filter;
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

class MpesaPayments extends BaseWidget
{

    protected int | string | array $columnSpan = 'full';
    public function table(Table $table): Table
    {
        return $table
        ->poll('10s')
        ->heading('Repayment Transactions')
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
        ->query(MpesaC2B::whereBetween('created_at', [now()->subMonths(2), now()]))
        ->defaultSort('created_at', 'desc')
        ->columns([
            Tables\Columns\TextColumn::make('Transaction_ID')
                    ->searchable(),
                Tables\Columns\TextColumn::make('Amount')
                    ->searchable()
                    ->numeric()
                    ->money('KES'),
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

                Tables\Columns\TextColumn::make('MiddleName')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('LastName')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

        ])->actions([

        ]);


    }
}
