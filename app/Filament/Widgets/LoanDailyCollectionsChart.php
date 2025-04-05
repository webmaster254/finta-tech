<?php

namespace App\Filament\Widgets;

use Carbon\Carbon;
use App\Models\Loan\Loan;
use Illuminate\View\View;
use Illuminate\Support\Number;
use App\Models\Loan\LoanRepaymentSchedule;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

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
    protected static ?string $heading = 'Loan Daily Target & Actual Collections';

    protected static ?int $sort = 6;



    protected function getFooter(): string | View
    {
        $today = today();

        $collections = Cache::remember('loan_daily_collections_' . $today->toDateString(), 300, function () use ($today) {
            return DB::query()
                ->select([
                    DB::raw('COALESCE(SUM(
                        CASE 
                            WHEN loans.status = "active" THEN (
                                loan_repayment_schedules.principal +
                                loan_repayment_schedules.interest +
                                loan_repayment_schedules.fees +
                                loan_repayment_schedules.penalties
                            )
                            ELSE 0
                        END
                    ), 0) as target_amount'),
                    DB::raw('COALESCE(SUM(
                        CASE 
                            WHEN loans.status = "active" THEN (
                                loan_repayment_schedules.principal_repaid_derived +
                                loan_repayment_schedules.interest_repaid_derived +
                                loan_repayment_schedules.fees_repaid_derived +
                                loan_repayment_schedules.penalties_repaid_derived
                            )
                            ELSE 0
                        END
                    ), 0) as actual_amount')
                ])
                ->from('loan_repayment_schedules')
                ->join('loans', 'loans.id', '=', 'loan_repayment_schedules.loan_id')
                ->whereDate('loan_repayment_schedules.due_date', $today)
                ->first();
        });

        $target = $collections->target_amount;
        $actual = $collections->actual_amount;

        if ($target != 0) {
            $percent = round(($actual / $target) * 100);
        } else {
            $percent = 0;
        }

        $data = [
            'target' => Number::currency($target,'KES'),
            'actual' => Number::currency($actual,'KES'),
            'percent' => Number::percentage($percent),
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

        $data = Cache::remember('loan_weekly_collections_' . $startOfWeek->toDateString(), 300, function () use ($startOfWeek, $endOfWeek) {
            // Get all weekly data in a single query
            $weeklyData = DB::query()
                ->select([
                    DB::raw('DATE(loan_repayment_schedules.due_date) as due_date'),
                    DB::raw('COALESCE(SUM(
                        loan_repayment_schedules.principal +
                        loan_repayment_schedules.interest +
                        loan_repayment_schedules.fees +
                        loan_repayment_schedules.penalties
                    ), 0) as target_amount'),
                    DB::raw('COALESCE(SUM(
                        loan_repayment_schedules.principal_repaid_derived +
                        loan_repayment_schedules.interest_repaid_derived +
                        loan_repayment_schedules.fees_repaid_derived +
                        loan_repayment_schedules.penalties_repaid_derived
                    ), 0) as actual_amount')
                ])
                ->from('loan_repayment_schedules')
                ->join('loans', 'loans.id', '=', 'loan_repayment_schedules.loan_id')
                ->where('loans.status', 'active')
                ->whereBetween('loan_repayment_schedules.due_date', [$startOfWeek, $endOfWeek])
                ->groupBy('due_date')
                ->get()
                ->keyBy(function ($item) {
                    return Carbon::parse($item->due_date)->format('d-m-y');
                });

            $labels = [];
            $expected = [];
            $actual = [];

            // Fill in data for each day of the week
            for ($date = $startOfWeek->copy(); $date->lte($endOfWeek); $date->addDay()) {
                $dateKey = $date->format('d-m-y');
                $labels[] = $dateKey;

                $dayData = $weeklyData->get($dateKey);
                $expected[] = $dayData ? $dayData->target_amount : 0;
                $actual[] = $dayData ? $dayData->actual_amount : 0;
            }

            return [
                'labels' => $labels,
                'expected' => $expected,
                'actual' => $actual,
            ];
        });

        return [
            'chart' => [
                'type' => 'line',
                'height' => 300,
            ],
            'series' => [
                [
                    'name' => 'Expected Collections',
                    'data' => $data['expected'],
                ],
                [
                    'name' => 'Actual Collections',
                    'data' => $data['actual'],
                ]
            ],
            'xaxis' => [
                'categories' => $data['labels'],
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
