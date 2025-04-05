<?php

namespace App\Filament\Widgets;

use App\Models\Loan\Loan;
use Filament\Support\RawJs;
use Illuminate\Support\Facades\Cache;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class LoanStatusChart extends ApexChartWidget
{
    protected static ?int $sort = 3;
    /**
     * Chart Id
     *
     * @var string
     */
    protected static ?string $chartId = 'loanStatusChart';

    /**
     * Widget Title
     *
     * @var string|null
     */
    protected static ?string $heading = 'Loan Status Overview';

    protected static ?int $contentHeight = 300; //px

    /**
     * Chart options (series, labels, types, size, animations...)
     * https://apexcharts.com/docs/options
     *
     * @return array
     */
    protected function getOptions(): array
    {
        $loanStatusData = Cache::remember('loan_status_chart', 300, function () {
            return Loan::query()
                ->select(['status'])
                ->selectRaw('COUNT(*) as total')
                ->groupBy('status')
                ->orderBy('total', 'desc')
                ->get();
        });
        return [
            'chart' => [
                'type' => 'donut',
                'height' => 300,
            ],
            'series' => $loanStatusData->pluck('total')->toArray(),
            'labels' => $loanStatusData->pluck('status')->toArray(),
            'total' =>[
                'show' => true,
                'fontSize' => '14px',
                'color' => '#373d3f',
                'label' => 'Total',

            ],
            'legend' => [
                'labels' => [
                    'fontFamily' => 'inherit',
                ],
            'total' => [
                'show' => true,
                'fontSize' => '14px',
                'color' => '#373d3f',
                'label' => 'Total',
            ],

            ],

        ];
    }

    protected function extraJsOptions(): ?RawJs
{
    return RawJs::make(<<<'JS'
    {
        labels: {
            show:true,
        },


        dataLabels: {
            enabled: false,
            formatter: function (val,opts) {
                return  val
            },
            dropShadow: {
                enabled: true
            },
        }
    }

    JS);
}
}
