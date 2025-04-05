<?php

namespace App\Transformers;

use App\Contracts\HasSummaryReport;
use App\Support\Column;

abstract class SummaryReportTransformer extends BaseReportTransformer implements HasSummaryReport
{
    private array $summaryHeaders = [];
    private ?array $summaryColumns = null;
    /**
     * @return Column[]
     */


    public function getSummaryColumns(): array
    {
        if ($this->summaryColumns === null) {
            $this->summaryColumns = collect($this->getColumns())
                ->reject(fn (Column $column) => $column->getName() === 'account_code')
                ->toArray();
        }
        return $this->summaryColumns;
    }



    public function getSummaryHeaders(): array
    {
        if (empty($this->summaryHeaders)) {
            $this->summaryHeaders = [];

            foreach ($this->getSummaryColumns() as $column) {
                $this->summaryHeaders[$column->getName()] = $column->getLabel();
            }
        }

        return $this->summaryHeaders;
    }
}
