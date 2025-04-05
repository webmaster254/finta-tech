<?php

namespace App\Filament\Widgets;

use Carbon\Carbon;
use App\Models\Loan\Loan;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class LoanDisbursementChart extends ApexChartWidget
{
    /**
     * Chart Id
     *
     * @var string
     */
    protected static ?string $chartId = 'loanDisbursementChart';

    /**
     * Widget Title
     *
     * @var string|null
     */
    protected static ?string $heading = 'Loan Disbursement';
    protected static ?int $contentHeight = 300; //px

    protected static ?int $sort = 4;
    public ?string $filter = 'year';

     /**
     * Filter Form
     */
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
     */
    protected function getOptions(): array
    {
        $filters = $this->filterFormData;
        $timeFilters = $this->filter;

        match ($timeFilters) {

            'week' => $data = Trend::model(Loan::class)
                    ->dateColumn('disbursed_on_date')
                        ->between(
                            start: now()->subWeek(),
                            end: now(),
                        )
                        ->perDay()
                        ->sum('principal_disbursed_derived'),
            'month' => $data = Trend::model(Loan::class)
                    ->dateColumn('disbursed_on_date')
                        ->between(
                            start: now()->subMonth(),
                            end: now(),
                        )
                        ->perDay()
                        ->sum('principal_disbursed_derived'),
            '3months' => $data = Trend::model(Loan::class)
                    ->dateColumn('disbursed_on_date')
                        ->between(
                            start: now()->subMonths(3),
                            end: now(),
                        )
                        ->perMonth()
                        ->sum('principal_disbursed_derived'),
            'year' => $data = Trend::model(Loan::class)
                    ->dateColumn('disbursed_on_date')
                        ->between(
                            start: now()->startOfYear(),
                            end: now()->endOfYear(),
                        )
                        ->perMonth()
                        ->sum('principal_disbursed_derived'),
        };





        return [
            'chart' => [
                'type' => $filters['disbursementChartType'],
                'height' => 250,
                'toolbar' => [
                    'show' => false,
                ],
            ],
            'series' => [
                [
                    'name' => 'Disbursement',
                    'data' => $data->map(fn (TrendValue $value) => $value->aggregate),
                ],
            ],
            'plotOptions' => [
                'bar' => [
                    'borderRadius' => 2,
                ],
            ],
            'xaxis' => [
                'categories' => $data->map(fn (TrendValue $value) => $value->date),
                'labels' => [
                    'style' => [
                        'fontWeight' => 400,
                        'fontFamily' => 'inherit',

                    ],

                ],
            ],
            'yaxis' => [
                'labels' => [
                    'style' => [
                        'fontWeight' => 400,
                        'fontFamily' => 'inherit',
                    ],
                ],
            ],
            'fill' => [
                'type' => 'gradient',
                'gradient' => [
                    'shade' => 'dark',
                    'type' => 'vertical',
                    'shadeIntensity' => 0.5,
                    'gradientToColors' => ['#fbbf24'],
                    'inverseColors' => true,
                    'opacityFrom' => 1,
                    'opacityTo' => 1,
                    'stops' => [0, 100],
                ],
            ],

            'dataLabels' => [
                'enabled' => false,
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
            'colors' => ['#f59e0b'],
            'annotations' => [
                'yaxis' => [
                    [
                        // 'y' => $filters['disbursementChartAnnotations'],
                        // 'borderColor' => '#ef4444',
                        // 'borderWidth' => 1,
                        // 'label' => [
                        //     'borderColor' => '#ef4444',
                        //     'style' => [
                        //         'color' => '#fffbeb',
                        //         'background' => '#ef4444',
                        //     ],
                        //     'text' => 'Annotation: ' . $filters['disbursementChartAnnotations'],
                        // ],
                    ],
                ],
            ],
        ];
    }
}
