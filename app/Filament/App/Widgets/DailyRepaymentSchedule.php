<?php

namespace App\Filament\App\Widgets;

use Filament\Tables;
use App\Models\Currency;
use App\Models\Loan\Loan;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Widgets\TableWidget as BaseWidget;

class DailyRepaymentSchedule extends BaseWidget
{
    protected static ?string $pollingInterval = '10s';
    protected static ?int $sort = 5;

    public function table(Table $table): Table
{
    try {
        return $table
            ->striped(true)
            ->poll('10s')
            ->heading('Daily Repayment Schedule')
            ->query(Loan::query()->with('repayment_schedules')
                ->where('loans.loan_officer_id', Auth::user()->id) // Specify the table name or alias for loan_officer_id
                ->join('loan_repayment_schedules', 'loan_repayment_schedules.loan_id', '=', 'loans.id')
                ->where('loan_repayment_schedules.due_date', now()->format('Y-m-d'))
                ->where('loan_repayment_schedules.total_due', '>', 0)
                ->join('clients', 'loans.client_id', '=', 'clients.id')
                ->where('loans.status', 'active')
                ->selectRaw("CONCAT(clients.first_name, ' ', clients.last_name) AS client, clients.mobile,
                    clients.account_number,
                    loans.client_id, loans.expected_maturity_date,
                    loans.disbursed_on_date, loans.id,
                    (SELECT submitted_on FROM loan_transactions WHERE loan_id = loans.id
                    ORDER BY submitted_on DESC LIMIT 1) AS last_payment_date,
                    loans.principal,loans.loan_term,loan_repayment_schedules.total_due")
            )
            ->columns([
                Tables\Columns\TextColumn::make('client'),
                Tables\Columns\TextColumn::make('mobile'),
                Tables\Columns\TextColumn::make('account_number'),
                Tables\Columns\TextColumn::make('total_due')
                    ->numeric()
                    ->money('KES')
                    ->summarize(Sum::make()
                                ->money('KES')
                                ->label('Total')),
            ]);
    } catch (\Exception $e) {
        // Handle any exceptions here
        // Log or report the exception
        // Optionally, throw the exception for higher-level handling
    }
}
}
