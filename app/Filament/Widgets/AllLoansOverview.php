<?php

namespace App\Filament\Widgets;

use Carbon\Carbon;
use App\Models\Client;
use App\Models\Currency;
use App\Models\Loan\Loan;
use Filament\Facades\Filament;
use Illuminate\Support\Number;
use App\Models\Loan\LoanRepaymentSchedule;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AllLoansOverview extends BaseWidget
{

    protected static ?int $sort = 0;
    protected function getStats(): array
    {
        return Cache::remember('all_loans_overview_stats', 300, function () {
            // Get default currency
            $currency = Currency::where('is_default', 1)->first();

            // Get total active clients
            $totalClients = Client::withoutGlobalScopes()
                ->where('status', 'active')
                ->count();

            // Get total loans
            $totalLoans = Loan::withoutGlobalScopes()->count();

            // Get loan amounts using a single optimized query
            $loanStats = DB::query()
                ->select([
                    DB::raw('COALESCE(SUM(
                        principal_disbursed_derived +
                        interest_disbursed_derived +
                        fees_disbursed_derived +
                        penalties_disbursed_derived
                    ), 0) as total_disbursed'),
                    DB::raw('COALESCE(SUM(
                        (SELECT SUM(
                            principal_repaid_derived +
                            interest_repaid_derived +
                            fees_repaid_derived +
                            penalties_repaid_derived
                        ) FROM loan_repayment_schedules
                        WHERE loan_repayment_schedules.loan_id = loans.id
                        )
                    ), 0) as total_repaid'),
                    DB::raw('COALESCE(SUM(
                        (SELECT SUM(total_due)
                        FROM loan_repayment_schedules
                        WHERE loan_repayment_schedules.loan_id = loans.id
                        )
                    ), 0) as total_outstanding'),
                    DB::raw('COALESCE(SUM(
                        CASE
                            WHEN expected_maturity_date < CURRENT_DATE
                            THEN (
                                SELECT SUM(total_due)
                                FROM loan_repayment_schedules
                                WHERE loan_repayment_schedules.loan_id = loans.id
                            )
                            ELSE 0
                        END
                    ), 0) as total_arrears')
                ])
                ->from('loans')
                ->first();

            $formatNumber = function (int $number): string {
                if ($number < 100000) {
                    return (string) Number::format($number);
                } elseif ($number < 1000000) {
                    return  Number::abbreviate($number, precision: 2);
                } else {
                    return  Number::abbreviate($number, precision: 2) ;
                }
            };

            return [
                Stat::make('Loans Disbursed', 'KES' . ' ' . ($loanStats->total_disbursed !== null ? $formatNumber($loanStats->total_disbursed) : '0'))
                    ->description('Total Loans Disbursed')
                    ->descriptionIcon('heroicon-m-arrow-trending-up')
                    ->chart([7, 2, 10, 3, 15, 4, 17])
                    ->color('success')
                    ->icon('heroicon-o-banknotes'),
                Stat::make('Loans Repayments','KES'.' '.($loanStats->total_repaid !== null ? $formatNumber($loanStats->total_repaid) : '0'))
                    ->description('Total Loans Repaid')
                    ->descriptionIcon('heroicon-m-arrow-trending-up')
                    ->chart([7, 2, 10, 3, 15, 4, 17])
                    ->color('info')
                    ->icon('heroicon-o-banknotes'),
                Stat::make(' Total Outstanding Balance','KES'.' '.($loanStats->total_outstanding !== null ? $formatNumber($loanStats->total_outstanding) : '0'))
                    ->description('Total Loans Outstanding')
                    ->descriptionIcon('heroicon-m-arrow-trending-down')
                    ->chart([17, 16, 14, 15, 14, 13, 12])
                    ->color('warning')
                    ->icon('heroicon-o-banknotes'),
                Stat::make('Total Outstanding Arrears','KES'.' '.($loanStats->total_arrears !== null ? $formatNumber($loanStats->total_arrears) : '0'))
                    ->description('Total Outstanding Arrears')
                    ->descriptionIcon('heroicon-m-arrow-trending-down')
                    ->chart([17, 16, 14, 15, 14, 13, 12])
                    ->color('danger')
                    ->icon('heroicon-o-banknotes'),
                Stat::make('Loans',Number::format($totalLoans))
                    ->description('Total Loans')
                    ->color('info')
                    ->icon('heroicon-o-receipt-percent'),
                Stat::make('Active Clients',Number::format($totalClients))
                    ->description('Total Active Clients')
                    ->color('success')
                    ->icon('heroicon-o-user-group'),
            ];
        });
    }
}
