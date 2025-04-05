<?php

namespace App\Filament\App\Widgets;

use Carbon\Carbon;
use App\Models\Loan\Loan;
use Illuminate\View\View;
use Illuminate\Support\Number;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Models\Loan\LoanRepaymentSchedule;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class LoanDailyCollectionsChart extends ApexChartWidget
{
    /**
     * Chart Id
     *
     * @var string
     */
    protected static ?string $chartId = 'loanDailyCollectionsChart';

    /**
     * Widget Title
     *
     * @var string|null
     */
    protected static ?string $heading = 'loan Daily Collections';

    protected static ?int $sort = 4;

    /**
     * Chart options (series, labels, types, size, animations...)
     * https://apexcharts.com/docs/options
     *
     * @return array
     */
    protected function getFooter(): string | View
    {
       $collections = Cache::remember('loan_daily_collections_' . now()->format('Y-m-d'), 300, fn () => Loan::with('repayment_schedules')
                                ->join('loan_repayment_schedules', 'loan_repayment_schedules.loan_id', '=', 'loans.id')
                                ->where('loan_officer_id',Auth::user()->id)
                                ->where('due_date', '=', now()->format('Y-m-d'))
                                ->selectRaw('loan_repayment_schedules.*')
                                ->get());

        $target = $collections->sum('principal') + $collections->sum('interest') + $collections->sum('fees')
                 + $collections->sum('penalties');
        $actual = $collections->sum('principal_repaid_derived') + $collections->sum('interest_repaid_derived')
                 + $collections->sum('fees_repaid_derived') +$collections->sum('penalties_repaid_derived');
        if ($target != 0) {
           $percent = round(($actual / $target) * 100);
        } else {
            // Handle the case where $target is 0
            $percent = 0;
        }
        $data = [
            'target' => Number::currency($target,'KES'), //$target,
            'actual' => Number::currency($actual,'KES'), //$actual,
            'percent' => Number::percentage($percent), //$percent,
        ];

        return view('charts.daily-collection.footer', ['data' => $data]);
    }

    /**
     * Chart options (series, labels, types, size, animations...)
     * https://apexcharts.com/docs/options
     *
     * @return array
     */
    protected function getOptions(): array
    {
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();
        $labels = [];
        $expected = [];
        $actual = [];
        $loanOfficerId = Auth::user()->id;

       $collections = Cache::remember('loan_daily_collections_' . now()->format('Y-m-d'), 300, fn () => Loan::with('repayment_schedules')
                            ->join('loan_repayment_schedules', 'loan_repayment_schedules.loan_id', '=', 'loans.id')
                            ->where('loan_officer_id', $loanOfficerId)
                            ->selectRaw('loan_repayment_schedules.*')
                            ->get());

        // Loop through each day of the week
        for ($date = $startOfWeek; $date->lte($endOfWeek); $date->addDay()) {
            $labels[] = $date->format("d-m-Y");

            $filteredCollections = $collections->where('due_date', '=', $date->format('Y-m-d'));
            $targetCollections = $filteredCollections->sum('principal') + $filteredCollections->sum('interest') + $filteredCollections->sum('fees') + $filteredCollections->sum('penalties');
            $actualCollections = $filteredCollections->sum('principal_repaid_derived') + $filteredCollections->sum('interest_repaid_derived')
            + $filteredCollections->sum('penalties_repaid_derived') + $filteredCollections->sum('fees_repaid_derived');
            $expected[] = $targetCollections;
            $actual[] = $actualCollections;
        }
        return [
            'chart' => [
                'type' => 'line',
                'height' => 300,
            ],
            'series' => [
                [
                    'name' => 'Expected Collections',
                    'data' => $expected,
                ],
                [
                    'name' => 'Actual Collections',
                    'data' => $actual,
                ]
            ],
            'xaxis' => [
                'categories' => $labels,
                'labels' => [
                    'style' => [
                        'fontFamily' => 'inherit',
                    ],
                ],
            ],
            'yaxis' => [
                'labels' => [
                    'style' => [
                        'fontFamily' => 'inherit',
                    ],
                ],
            ],
            'colors' => ['#f59e0b', '#10b981'],
            'stroke' => [
                'curve' => 'straight',
            ],
            'markers' => [
                'size' => 3,
            ]
        ];
    }
}
