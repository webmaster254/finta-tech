<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use App\Models\Currency;
use App\Models\Loan\Loan;
use Filament\Tables\Table;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Widgets\TableWidget as BaseWidget;

class MonthlyPar extends BaseWidget
{

    protected static ?string $pollingInterval = '10s';
    public function table(Table $table): Table
    {
        return $table
        ->emptyStateHeading('No Monthly Portfolio At Risk Data')
        ->defaultPaginationPageOption(5)
        ->poll('10s')
        ->heading('Monthly PAR Report')
        ->description('Portfolio At Risk For The Month per Loan Officer')
        ->striped()
        ->query(Loan::query()->join('loan_repayment_schedules', 'loan_repayment_schedules.loan_id', '=', 'loans.id')
        ->whereMonth('loan_repayment_schedules.due_date', now()->month)
        ->whereYear('loan_repayment_schedules.due_date', now()->year)
        ->where('loan_repayment_schedules.total_due', '>', 0)
        ->join('users', 'loans.loan_officer_id', '=', 'users.id')
        ->where('loans.status', 'active')
        ->selectRaw("CONCAT(users.first_name, ' ', users.last_name) AS loan_officer,loan_repayment_schedules.total_due,
        SUM(CASE WHEN loan_repayment_schedules.due_date < CURDATE() THEN loan_repayment_schedules.total_due ELSE 0 END) AS outstanding_arrears,
                                SUM(loan_repayment_schedules.principal + loan_repayment_schedules.interest + loan_repayment_schedules.fees
                                + loan_repayment_schedules.penalties) AS outstanding_balance,
                                ROUND(
                                    SUM(CASE WHEN loan_repayment_schedules.due_date < CURDATE()  THEN loan_repayment_schedules.total_due ELSE 0 END)
                                    / SUM(loan_repayment_schedules.principal + loan_repayment_schedules.interest + loan_repayment_schedules.fees + loan_repayment_schedules.penalties) * 100, 2
                                ) AS par_ratio")
        ->selectRaw('ROW_NUMBER() OVER (ORDER BY users.id) AS id')
        ->groupBy('loan_officer')
         )
            ->columns([
                Tables\Columns\TextColumn::make('loan_officer'),
                Tables\Columns\TextColumn::make('outstanding_arrears')
                    ->label('Outstanding Arrears')
                    ->sortable()
                    ->money(Currency::where('is_default', 1)->first()->symbol)
                    ->summarize(Sum::make()
                         ->money(Currency::where('is_default', 1)->first()->symbol)
                         ->label('Total Arrears')),
                Tables\Columns\TextColumn::make('outstanding_balance')
                    ->label('Outstanding Balance')
                    ->sortable()
                    ->money(Currency::where('is_default', 1)->first()->symbol)
                    ->summarize(Sum::make()
                          ->money(Currency::where('is_default', 1)->first()->symbol)
                        ->label('Total Balance')),
                Tables\Columns\TextColumn::make('par_ratio')
                    ->label('PAR')
                    ->sortable()
                    ->suffix('%'),
            ]);
    }
}
