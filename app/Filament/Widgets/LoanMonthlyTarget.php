<?php

namespace App\Filament\Widgets;

use Carbon\Carbon;
use App\Models\Loan\Loan;
use Illuminate\View\View;
use Illuminate\Support\Number;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Toggle;
use App\Models\Loan\LoanRepaymentSchedule;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class LoanMonthlyTarget extends ApexChartWidget
{
    /**
     * Chart Id
     *
     * @var string
     */
    protected static ?string $chartId = 'loanMonthlyTarget';

    /**
     * Widget Title
     *
     * @var string|null
     */
    protected static ?string $heading = 'Loan Monthly Target & Actual Collections';
    //protected static ?int $sort = 7;
    protected static ?string $pollingInterval = null;





    protected function getFooter(): string | View
    {
        $now = now();
        $data = Cache::remember('loan_monthly_target_' . $now->format('Y-m'), 300, function () use ($now) {
            $result = DB::query()
                ->select([
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
                ->where(function ($query) use ($now) {
                    $query->whereBetween('loan_repayment_schedules.due_date', [
                        $now->startOfMonth()->format('Y-m-d'),
                        $now->endOfMonth()->format('Y-m-d')
                    ]);
                })
                ->first();

            return [
                'target' => $result->target_amount ?? 0,
                'actual' => $result->actual_amount ?? 0
            ];
        });


        $percent = $data['target'] > 0 ? round(($data['actual'] / $data['target']) * 100) : 0;

        $data = [
            'target' => Number::currency($data['target'],'KES'), //$target,
            'actual' => Number::currency($data['actual'],'KES'), //$actual,
            'percent' => Number::percentage($percent), //$percent,
        ];


        return view('charts.monthly-collection.footer', ['data' => $data]);
    }


    protected function getFormSchema(): array
    {
        return [

            Radio::make('disbursementChartType')
                ->default('bar')
                ->options([
                    'line' => 'Line',
                    'bar' => 'Col',
                    'area' => 'Area',
                ])
                ->inline(true)
                ->label('Type'),

            Grid::make()
                ->schema([
                    Toggle::make('disbursementChartMarkers')
                        ->default(false)
                        ->label('Markers'),

                    Toggle::make('disbursementChartGrid')
                        ->default(false)
                        ->label('Grid'),
                ]),
                // DatePicker::make('year')
                //     ->format('Y')
                //     ->default(Carbon::now()->year)
                //     ->native(false)
                //     ->live()
                //      ->afterStateUpdated(function () {
                //          $this->updateChartOptions();
                //      })
                //     ->label('Year'),

            // TextInput::make('disbursementChartAnnotations')
            //     ->required()
            //     ->numeric()
            //     ->default(7500)
            //     ->label('Annotations'),
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
        $filters = $this->filterFormData;
        $startOfYear = Carbon::now()->startOfYear();
        $endOfYear = Carbon::now()->endOfYear();

        $data = Cache::remember('loan_monthly_target_chart_' . $startOfYear->year, 300, function () use ($startOfYear, $endOfYear) {
            return DB::query()
                ->select([
                    DB::raw('DATE_FORMAT(loan_repayment_schedules.due_date, "%b-%Y") as month_year'),
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
                ->whereBetween('loan_repayment_schedules.due_date', [
                    $startOfYear->format('Y-m-d'),
                    $endOfYear->format('Y-m-d')
                ])
                ->groupBy('month_year')
                ->orderBy('loan_repayment_schedules.due_date')
                ->get()
                ->keyBy('month_year');
        });

        $labels = [];
        $expected = [];
        $actual = [];

        // Ensure we have data for all months, even if zero
        for ($date = $startOfYear->copy(); $date->lte($endOfYear); $date->addMonth()) {
            $monthKey = $date->format('M-Y');
            $labels[] = $monthKey;

            $monthData = $data->get($monthKey);
            $expected[] = $monthData ? $monthData->target_amount : 0;
            $actual[] = $monthData ? $monthData->actual_amount : 0;
        }

        return [
            'chart' => [
                'type' => $filters['disbursementChartType'],
                'height' => 300,
                'toolbar' => [
                    'show' => false,
                ],
            ],
            'series' => [
                [
                    'name' => 'Expected Monthly Collections',
                    'data' => $expected,
                ],
                [
                    'name' => 'Actual Monthly Collections',
                    'data' => $actual,
                ],
            ],
            'plotOptions' => [
                'bar' => [
                    'borderRadius' => 2,
                ],
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
            'grid' => [
                'show' => $filters['disbursementChartGrid'],
            ],
            'markers' => [
                'size' => $filters['disbursementChartMarkers'] ? 3 : 0,
            ],
            'tooltip' => [
                'enabled' => true,
            ],
            'stroke' => [
                'width' => $filters['disbursementChartType'] === 'line' ? 4 : 0,
            ],
            'colors' => ['#f59e0b', '#10b981'],
            'plotOptions' => [
                'bar' => [
                    'borderRadius' => 3,
                    'horizontal' => false,
                ],
            ],
            'dataLabels' => [
                'enabled' => false,
            ],
        ];
    }
}
