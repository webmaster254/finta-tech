<?php

namespace App\Filament\Widgets;

use Carbon\Carbon;
use App\Models\Loan\Loan;
use Illuminate\Support\Number;
use App\Models\Loan\LoanRepaymentSchedule;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class ParChart extends ApexChartWidget
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
        $par = Cache::remember('par_chart_data', 300, function () {
            $now = now();

            $loanStats = Loan::query()
                ->select([
                    DB::raw('SUM(loan_repayment_schedules.total_due) as total_balance'),
                    DB::raw('SUM(CASE WHEN loan_repayment_schedules.due_date <= ? THEN loan_repayment_schedules.total_due ELSE 0 END) as total_arrears'),
                ])
                ->join('loan_repayment_schedules', 'loans.id', '=', 'loan_repayment_schedules.loan_id')
                ->addBinding($now, 'select')
                ->first();

            $totalLoanOutstanding = $loanStats->total_balance ?? 0;
            $totalOutstandingArrears = $loanStats->total_arrears ?? 0;

            return $totalLoanOutstanding > 0
                ? Number::format(($totalOutstandingArrears/$totalLoanOutstanding)*100, 2)
                : 0;
        });

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
