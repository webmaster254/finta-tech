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

class LoansOverview extends BaseWidget
{

    protected static ?int $sort = 0;

    protected function getStats(): array
    {
        return Cache::remember('loans_overview_stats_' . Filament::getTenant()->id, 300, function () {
            $now = now();

            // Get loan statistics in a single query
            $loanStats = Loan::query()
                ->select([
                    DB::raw('COUNT(DISTINCT loans.id) as total_loans'),
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
                        ) FROM loan_repayment_schedules WHERE loan_id = loans.id)
                    ), 0) as total_repaid'),
                    DB::raw('COALESCE(SUM(
                        (SELECT SUM(total_due) FROM loan_repayment_schedules WHERE loan_id = loans.id)
                    ), 0) as total_outstanding'),
                    DB::raw('COALESCE(SUM(
                        (SELECT SUM(total_due) FROM loan_repayment_schedules 
                        WHERE loan_id = loans.id AND due_date <= ?)
                    ), 0) as total_arrears')
                ])
                ->addBinding($now, 'select')
                ->first();

            $totalClients = Cache::remember('total_active_clients', 300, function () {
                return Client::where('status', 'active')->count();
            });

            $currency = Cache::remember('default_currency', 3600, function () {
                return Currency::where('is_default', 1)->first();
            });

            $formatNumber = function ($number) {
                if ($number < 100000) {
                    return Number::format($number);
                }
                return Number::abbreviate($number, precision: 2);
            };

            return [
                Stat::make('Disbursed', $currency->symbol . ' ' . $formatNumber($loanStats->total_disbursed))
                    ->description('Total Amount Disbursed')
                    ->descriptionIcon('heroicon-m-arrow-trending-up')
                    ->chart([8, 9, 10, 11, 12, 13, 14])
                    ->color('success')
                    ->icon('heroicon-o-banknotes'),
                Stat::make('Repayments', $currency->symbol . ' ' . $formatNumber($loanStats->total_repaid))
                    ->description('Total Amount Repayed')
                    ->descriptionIcon('heroicon-m-arrow-trending-up')
                    ->chart([15, 14, 13, 12, 11, 10, 9])
                    ->color('warning')
                    ->icon('heroicon-o-banknotes'),
                Stat::make('Outstanding', $currency->symbol . ' ' . $formatNumber($loanStats->total_outstanding))
                    ->description('Total Outstanding Amount')
                    ->descriptionIcon('heroicon-m-arrow-trending-up')
                    ->chart([15, 14, 13, 12, 11, 10, 9])
                    ->color('warning')
                    ->icon('heroicon-o-banknotes'),
                Stat::make('Arrears', $currency->symbol . ' ' . $formatNumber($loanStats->total_arrears))
                    ->description('Total Outstanding Arrears')
                    ->descriptionIcon('heroicon-m-arrow-trending-down')
                    ->chart([17, 16, 14, 15, 14, 13, 12])
                    ->color('danger')
                    ->icon('heroicon-o-banknotes'),
                Stat::make('Loans', $formatNumber($loanStats->total_loans))
                    ->description('Total Loans')
                    ->color('info')
                    ->icon('heroicon-o-receipt-percent'),
                Stat::make('Active Clients', $formatNumber($totalClients))
                    ->description('Total Active Clients')
                    ->color('success')
                    ->icon('heroicon-o-users'),
            ];
        });
    }
}
