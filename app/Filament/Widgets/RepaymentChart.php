<?php

namespace App\Filament\Widgets;

use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use App\Models\Loan\LoanTransaction;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class RepaymentChart extends ApexChartWidget
{
    /**
     * Chart Id
     *
     * @var string
     */
    protected static ?string $chartId = 'repaymentChart';

    /**
     * Widget Title
     *
     * @var string|null
     */
    protected static ?string $heading = 'Repayment Overview';

    protected static ?int $sort = 5;

    protected static ?int $contentHeight = 300; //px

    public ?string $filter = 'week';

    protected function getFilters(): ?array
    {
        return [
            'week' => 'Last Week',
            'month' => 'Last Month',
            '3months' => 'Last 3 Months',
            'year' => 'This Year',
        ];
    }



    /**
     * Chart options (series, labels, types, size, animations...)
     * https://apexcharts.com/docs/options
     *
     * @return array
     */
    protected function getOptions(): array
    {
        $filter = $this->filter;

        match ($filter) {

            'week' => $data = Trend::query(LoanTransaction::where('loan_transaction_type_id', 2))
                    ->dateColumn('submitted_on')
                        ->between(
                            start: now()->subWeek(),
                            end: now(),
                        )
                        ->perDay()
                        ->sum('amount'),
            'month' => $data = Trend::query(LoanTransaction::where('loan_transaction_type_id', 2))
                    ->dateColumn('submitted_on')
                        ->between(
                            start: now()->subMonth(),
                            end: now(),
                        )
                        ->perDay()
                        ->sum('amount'),
            '3months' => $data = Trend::query(LoanTransaction::where('loan_transaction_type_id', 2))
                    ->dateColumn('submitted_on')
                        ->between(
                            start: now()->subMonths(3),
                            end: now(),
                        )
                        ->perMonth()
                        ->sum('amount'),
            'year' => $data = Trend::query(LoanTransaction::where('loan_transaction_type_id', 2))
                    ->dateColumn('submitted_on')
                        ->between(
                            start: now()->startOfYear(),
                            end: now()->endOfYear(),
                        )
                        ->perMonth()
                        ->sum('amount'),
        };
        return [
            'chart' => [
                'type' => 'bar',
                'height' => 300,
            ],
            'series' => [
                [
                    'name' => 'Amount',
                    'data' => $data->map(fn (TrendValue $value) => $value->aggregate),
                ],
            ],
            'xaxis' => [
                'categories' => $data->map(fn (TrendValue $value) => $value->date),
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
            'dataLabels' => [
                'enabled' => false,
                ],
            'colors' => ['#f59e0b'],
            'stroke' => [
                'curve' => 'smooth',
            ],
        ];
    }
}
