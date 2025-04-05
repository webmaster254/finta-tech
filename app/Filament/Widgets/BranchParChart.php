<?php

namespace App\Filament\Widgets;

use Carbon\Carbon;
use App\Models\Loan\Loan;
use Illuminate\Support\Number;
use App\Models\Loan\LoanRepaymentSchedule;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class BranchParChart extends ApexChartWidget
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
        $activeLoans = Loan::with('repayment_schedules')->get();
        $totalBalance = 0;
        $totalOutstandingArrears = 0;

        //Total loan disbursed amount
        $totalLoanDisbursed = $activeLoans->sum(function ($loan) {

            return $loan->principal_disbursed_derived +
                   $loan->interest_disbursed_derived +
                   $loan->fees_disbursed_derived +
                   $loan->penalties_disbursed_derived;

    });
        foreach ($activeLoans as $loan) {
            $totalBalance += $loan->repayment_schedules->sum('total_due');
            // Update to sum total_due for loans exceeding expected maturity date
            $totalOutstandingArrears += $loan->repayment_schedules->sum(function ($schedule) use ($loan) {
                return $loan->expected_maturity_date < now() ? $schedule->total_due : 0; // Changed <= to <
            });
        }

        $totalLoanOutstanding = $totalBalance;


        //calculate the percentage of the portfolio at risk
        $par = 0;
        if ($totalLoanOutstanding > 0) {
            $par = Number::format(($totalLoanOutstanding/$totalLoanDisbursed)*100,2);
        }else{
            $par=0;
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
