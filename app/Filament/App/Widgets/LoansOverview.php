<?php

namespace App\Filament\App\Widgets;

use Carbon\Carbon;
use App\Models\Client;
use App\Models\Currency;
use App\Models\Loan\Loan;
use Illuminate\Support\Number;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Models\Loan\LoanRepaymentSchedule;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class LoansOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $user = Auth::user();
       $totalClients = Cache::remember('total_clients', 300, fn () => (int) Client::where('status', 'active')
                                    ->where('loan_officer_id', $user->id)->count());

        $loans =Cache::remember('total_loans', 300, fn () => Loan::where('loan_officer_id', $user->id)
                       ->with(['repayment_schedules' => function ($query) {
                           $query->select('loan_repayment_schedules.*');
                       }])
                       ->whereIn('status', ['active', 'closed'])
                       ->get(['id',
                       'approved_amount',
                       'principal',
                       'interest_disbursed_derived',
                       'principal_disbursed_derived',
                       'fees_repaid_derived',
                       'penalties_repaid_derived'])
                       ->keyBy('id')
        );
        $totalLoans = $loans->count();
        $principalDisbursed = $loans->sum('approved_amount');
        $interestDisbursed = $loans->sum('interest_disbursed_derived');
        $totalLoanDisbursed = $principalDisbursed + $interestDisbursed ;

        $loansRepayment = $loans->flatMap(function ($loan) {
            return $loan->repayment_schedules;
        });

        $principalRepayment = $loansRepayment->sum('principal_repaid_derived');
        $interestRepayment = $loansRepayment->sum('interest_repaid_derived');
        $feeRepayment = $loansRepayment->sum('fees_repaid_derived');
        $penaltyRepayment = $loansRepayment->sum('penalties_repaid_derived');

        $totalLoanRepayment = $principalRepayment + $interestRepayment + $feeRepayment + $penaltyRepayment;

        $totalLoanOutstanding = $loansRepayment->sum('total_due');
        $totalOutstandingArrears = $loansRepayment->where('total_due', '>', 0)
                                                   ->where('due_date', '<', Carbon::now())
                                                   ->sum('total_due');
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

        $par = $totalLoanOutstanding == 0 ? 0 : Number::percentage(($totalOutstandingArrears/$totalLoanOutstanding) * 100, precision: 2);

        return [
            Stat::make('Loans Disbursed','KES'.' '. $formatNumber($totalLoanDisbursed))
                ->description('Total Loans Disbursed')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->color('success')
                ->icon('heroicon-o-banknotes'),
            Stat::make('Loans Repayments','KES'.' '.$formatNumber($totalLoanRepayment))
                ->description('Total Loans Repaid')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->color('info')
                ->icon('heroicon-o-banknotes'),
            Stat::make('  Outstanding Balance','KES'.' '.$formatNumber($totalLoanOutstanding))
                ->description('Total Loans Outstanding')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->chart([17, 16, 14, 15, 14, 13, 12])
                ->color('warning')
                ->icon('heroicon-o-banknotes'),
            Stat::make('Outstanding Arrears','KES'.' '.$formatNumber($totalOutstandingArrears))
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
