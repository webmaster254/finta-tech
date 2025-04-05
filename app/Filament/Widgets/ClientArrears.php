<?php

namespace App\Filament\Widgets;

use Carbon\Carbon;
use App\Models\User;
use Filament\Tables;
use App\Models\Currency;
use App\Models\Loan\Loan;
use Filament\Tables\Table;
use Filament\Tables\Filters\Filter;
use App\Filament\Resources\LoanResource;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Widgets\TableWidget as BaseWidget;

class ClientArrears extends BaseWidget
{
    protected static ?string $pollingInterval = null;
    public function table(Table $table): Table
    {
        return $table
            ->heading('Client With Arrears')
            ->emptyStateHeading('No client with Arrears')
            ->striped()

            ->filters([
                SelectFilter::make('loan_officer_id')
                                ->label('Loan Officer')
                                ->preload(true)
                                ->relationship('loan_officer', 'name')
                                ->searchable(),
                Filter::make('created_at')
                ->form([
                    DatePicker::make('created_from')
                        ->native(false),
                    DatePicker::make('created_until')
                        ->native(false),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when(
                            $data['created_from'],
                            fn (Builder $query, $date): Builder => $query->whereDate('disbursed_on_date', '>=', $date),
                        )
                        ->when(
                            $data['created_until'],
                            fn (Builder $query, $date): Builder => $query->whereDate('disbursed_on_date', '<=', $date),
                        );
                })
                ->indicateUsing(function (array $data): array {
                    $indicators = [];
                    if ($data['created_from'] ?? null) {
                        $indicators['created_from'] = 'Disbursed from ' . Carbon::parse($data['created_from'])->toFormattedDateString();
                    }
                    if ($data['created_until'] ?? null) {
                        $indicators['created_until'] = 'Disbursed until ' . Carbon::parse($data['created_until'])->toFormattedDateString();
                    }

                    return $indicators;
                }),

            ], layout: FiltersLayout::Modal)

            ->query(Loan::query()->join('loan_repayment_schedules', 'loan_repayment_schedules.loan_id', '=', 'loans.id')
            ->whereDate('loan_repayment_schedules.due_date', '<' ,Carbon::today())
            ->where('loan_repayment_schedules.total_due', '>', 0)
            ->join('clients', 'loans.client_id', '=', 'clients.id')
            ->leftJoin("users", "loans.loan_officer_id", "users.id")
            ->where('loans.status', 'active')
            ->selectRaw("CONCAT(clients.first_name, ' ', clients.last_name) AS client, clients.mobile,
             clients.account_number,CONCAT(users.first_name,' ',users.last_name) AS loan_officer,
               loans.client_id, loans.expected_maturity_date,
               loans.disbursed_on_date, loans.id,
               (SELECT submitted_on FROM loan_transactions WHERE loan_id = loans.id
               ORDER BY submitted_on DESC LIMIT 1) AS last_payment_date,
               loans.principal,loans.loan_term,SUM(loan_repayment_schedules.total_due ) AS arrears")
               ->groupBy('loans.id')
               ->orderBy('disbursed_on_date','desc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('loan_officer')
                    ->sortable(),
                Tables\Columns\TextColumn::make('client'),
                Tables\Columns\TextColumn::make('mobile'),
                Tables\Columns\TextColumn::make('account_number'),
                Tables\Columns\TextColumn::make('disbursed_on_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('arrears')
                    ->label('Arrears')
                    ->sortable()
                    ->numeric()
                    ->money('KES')
                    ->summarize(Sum::make()
                                ->money('KES')
                                ->label('Total')),
                Tables\Columns\TextColumn::make('payments')
                    ->label('Days in Arrears')
                    ->badge()
                    ->getStateUsing(function (Loan $record) {
                        $daysInArrears =$record->getDaysInArrears($record->id);
                        return $daysInArrears;
                    }) ->color(fn ( $state)=> $state>5?'danger':'success'),
            ])->actions([
                Tables\Actions\Action::make('view')
                    ->label('View Loan')
                    ->url(fn (Loan $record): string => LoanResource::getUrl('view', ['record' => $record])),

            ]);
    }
}
