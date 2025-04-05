<?php

namespace App\Filament\Widgets;

use Carbon\Carbon;
use App\Models\Loan\Loan;
use Illuminate\Support\Number;
use App\Models\Loan\LoanRepaymentSchedule;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class TotalParChart extends ApexChartWidget
{
    protected static ?int $sort = 2;
    /**
     * Chart Id
     *
     * @var string
     */
    protected static ?string $chartId = 'parChart';

    /**
     * Widget Title
     *
     * @var string|null
     */
    protected static ?string $heading = 'Portfolio At Risk Chart';


    protected static ?int $contentHeight = 300; //px

    /**
     * Chart options (series, labels, types, size, animations...)
     * https://apexcharts.com/docs/options
     *
     * @return array
     */
    protected function getOptions(): array
    {
        $now = now();

        $loanStats = Cache::remember('total_par_stats_' . $now->format('Y-m-d'), 300, function () use ($now) {
            return Loan::withoutGlobalScopes()
                ->select([
                    DB::raw('COALESCE(SUM(
                        principal_disbursed_derived + 
                        interest_disbursed_derived + 
                        fees_disbursed_derived + 
                        penalties_disbursed_derived
                    ), 0) as total_disbursed'),
                    DB::raw('COALESCE(SUM(CASE 
                        WHEN expected_maturity_date < ? THEN (
                            SELECT COALESCE(SUM(total_due), 0) 
                            FROM loan_repayment_schedules 
                            WHERE loan_repayment_schedules.loan_id = loans.id
                        )
                        ELSE 0 
                    END), 0) as total_overdue')
                ])
                ->addBinding($now, 'select')
                ->first();
        });

        $totalLoanDisbursed = $loanStats->total_disbursed;
        $totalLoanOutstanding = $loanStats->total_overdue;

        //calculate the percentage of the portfolio at risk
        $par = $totalLoanDisbursed > 0 
            ? Number::format(($totalLoanOutstanding/$totalLoanDisbursed)*100, 2)
            : 0;

        return [
            'chart' => [
                'type' => 'radialBar',
                'height' => 300,
            ],
            'series' => [$par],
            'plotOptions' => [
                'radialBar' => [
                    'hollow' => [
                        'size' => '70%',
                    ],
                    'track' => [
                        'background' => 'transparent',
                        'strokeWidth' => '100%',
                    ],
                    'dataLabels' => [
                        'show' => true,
                        'name' => [
                            'show' => true,
                            'fontFamily' => 'inherit'
                        ],
                        'value' => [
                            'show' => true,
                            'fontFamily' => 'inherit',
                            'fontWeight' => 600,
                            'fontSize' => '20px'
                        ],
                    ],
                ],
            ],
            'fill' => [
                'type' => 'gradient',
                'gradient' => [
                    'shade' => 'dark',
                    'type' => 'horizontal',
                    'shadeIntensity' => 0.5,
                    'gradientToColors' => ['#f59e0b'],
                    'inverseColors' => true,
                    'opacityFrom' => 1,
                    'opacityTo' => 0.6,
                    'stops' => [30, 70, 100],
                ],
            ],
            'stroke' => [
                'dashArray' => 10,
            ],
            'labels' => ['P.A.R Chart'],
            'colors' => ['#16a34a'],
        ];
    }
}
