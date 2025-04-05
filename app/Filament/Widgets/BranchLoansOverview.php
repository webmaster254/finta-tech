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



class BranchLoansOverview extends BaseWidget
{

    protected static ?int $sort = 0;
    protected function getStats(): array
    {
        $totalClients = (int) Client::where('status', 'active')->count(); // Get all clients
        $totalLoans = Loan::count('id'); // Get all loans
        $activeLoans = Loan::with('repayment_schedules')->get(); // Get all loans with repayment schedules

        //Total loan disbursed amount
        $totalLoanDisbursed = $activeLoans->sum(function ($loan) {

                return $loan->principal_disbursed_derived +
                       $loan->interest_disbursed_derived +
                       $loan->fees_disbursed_derived +
                       $loan->penalties_disbursed_derived;

        });

        //Total Amount Repayed
        $totalLoanRepayment = $activeLoans->sum(function ($loan) {
                return $loan->repayment_schedules->sum(function ($schedule) {
                        return $schedule->principal_repaid_derived +
                                $schedule->interest_repaid_derived +
                                $schedule->fees_repaid_derived +
                                $schedule->penalties_repaid_derived;
                                    });
                                });

        //Total outstanding amount
        $totalLoanOutstanding = $activeLoans->sum(function ($loan) {
            return $loan->repayment_schedules->sum('total_due');
        });

        //Total loan arrears amount
        $totalOutstandingArrears = 0;
        foreach ($activeLoans as $loan) {
            $totalOutstandingArrears += $loan->repayment_schedules->sum(function ($schedule) use ($loan) {
                return $loan->expected_maturity_date < now() ? $schedule->total_due : 0;
            });
        }
        $currency = Currency::where('is_default', 1)->first();

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
            Stat::make('Loans Disbursed', 'KES' . ' ' . ($totalLoanDisbursed !== null ? $formatNumber($totalLoanDisbursed) : '0'))
                ->description('Total Loans Disbursed')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->color('success')
                ->icon('heroicon-o-banknotes'),
            Stat::make('Loans Repayments','KES'.' '.($totalLoanRepayment !== null ? $formatNumber($totalLoanRepayment) : '0'))
                ->description('Total Loans Repaid')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->color('info')
                ->icon('heroicon-o-banknotes'),
            Stat::make(' Total Outstanding Balance','KES'.' '.($totalLoanOutstanding !== null ? $formatNumber($totalLoanOutstanding) : '0')) // $formatNumber($totalLoanOutstanding))
                ->description('Total Loans Outstanding')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->chart([17, 16, 14, 15, 14, 13, 12])
                ->color('warning')
                ->icon('heroicon-o-banknotes'),
            Stat::make('Total Outstanding Arrears','KES'.' '.($totalOutstandingArrears !== null ? $formatNumber($totalOutstandingArrears) : '0')) //$formatNumber($totalOutstandingArrears))
                ->description('Total Outstanding Arrears')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->chart([17, 16, 14, 15, 14, 13, 12])
                ->color('danger')
                ->icon('heroicon-o-banknotes'),
            Stat::make('Loans',$totalLoans)
                ->description('Total Loans')
                ->color('info')
                ->icon('heroicon-o-receipt-percent'),
            Stat::make('Active Clients',Number::format($totalClients))
                ->description('Total Active Clients')
                ->color('info')
                ->icon('heroicon-o-user-group'),

        ];
    }
}
