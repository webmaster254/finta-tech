<?php

namespace App\Transformers;

use App\Contracts\ExportableReport;
use App\DTO\ReportDTO;
use App\Support\Column;
use Filament\Support\Enums\Alignment;

abstract class BaseReportTransformer implements ExportableReport
{
    protected ReportDTO $report;
    protected array $headers = [];
    private ?array $columns = null;
    private array $alignmentCache = [];

    public function __construct(ReportDTO $report)
    {
        $this->report = $report;
    }

    /**
     * @var Column[]|null
     */


    /**
     * @return Column[]
     */
    public function getColumns(): array
    {
        if ($this->columns === null) {
            $this->columns = $this->report->fields;
        }

        return $this->columns;
    }



    public function getHeaders(): array
    {
        if (empty($this->headers)) {
            $this->headers = [];

            foreach ($this->getColumns() as $column) {
                $this->headers[$column->getName()] = $column->getLabel();
            }
        }

        return $this->headers;
    }

    public function getPdfView(): string
    {
        return 'components.reports.report-pdf';
    }

    public function getAlignment(int $index): string
    {
        $column = $this->getColumns()[$index];

        if ($column->getAlignment() === Alignment::Right) {
            return 'right';
        }

        if ($column->getAlignment() === Alignment::Center) {
            return 'center';
        }

        return 'left';
    }



    public function getAlignmentClass(string $columnName): string
    {
        if (!isset($this->alignmentCache[$columnName])) {
            /** @var Column|null $column */
            $column = collect($this->getColumns())->first(fn (Column $column) => $column->getName() === $columnName);

            if ($column?->getAlignment() === Alignment::Right) {
                $this->alignmentCache[$columnName] = 'text-right';
            } elseif ($column?->getAlignment() === Alignment::Center) {
                $this->alignmentCache[$columnName] = 'text-center';
            } else {
                $this->alignmentCache[$columnName] = 'text-left';
            }
        }

        return $this->alignmentCache[$columnName];
    }

    public function getStartDate(): ?string
    {
        return $this->report->startDate?->toDefaultDateFormat();
    }

    public function getEndDate(): ?string
    {
        return $this->report->endDate?->toDefaultDateFormat();
    }
}
