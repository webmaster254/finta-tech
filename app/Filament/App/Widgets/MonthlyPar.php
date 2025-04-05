<?php

namespace App\Filament\App\Widgets;

use Carbon\Carbon;
use App\Models\Loan\Loan;
use Illuminate\Support\Number;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class MonthlyPar extends ApexChartWidget
{
    /**
     * Chart Id
     *
     * @var string
     */
    protected static ?string $chartId = 'MonthlyPar';

    /**
     * Widget Title
     *
     * @var string|null
     */
    protected static ?string $heading = 'Current Month Portfolio At Risk (%)';


    protected static ?int $sort = 2;

    /**
     * Chart options (series, labels, types, size, animations...)
     * https://apexcharts.com/docs/options
     *
     * @return array
     */
    protected function getOptions(): array
    {
        $loansRepayment = Cache::remember('monthly_par_chart_data', 300, function () {
            return Loan::query()
                ->join('loan_repayment_schedules', 'loan_repayment_schedules.loan_id', '=', 'loans.id')
                ->where('loan_officer_id', Auth::user()->id)
                ->whereMonth('due_date', Carbon::now()->month)
                ->get(['loans.id', 'total_due', 'due_date']);
        });

        $totalLoanOutstanding = $loansRepayment->sum('total_due');

        $totaloutstandingArrears = $loansRepayment->where('total_due', '>', 0)
                                ->where('due_date', '<', Carbon::now())
                                ->sum('total_due');


        
            if ($totalLoanOutstanding > 0) {
            $par = Number::format(($totaloutstandingArrears / $totalLoanOutstanding) * 100, 2);
        } else {
            // Handle division by zero or other potential issues
            $par = 0;
        }
        
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
