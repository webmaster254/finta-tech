<?php

namespace App\Filament\Pages\Reports;

use App\DTO\ReportDTO;
use App\Support\Column;
use Filament\Forms\Form;
use App\Models\ChartOfAccount;
use App\Models\JournalEntries;
use App\Services\ExportService;
use App\Services\ReportService;
use Illuminate\Support\Collection;
use App\Contracts\ExportableReport;
use Filament\Tables\Actions\Action;
use App\Filament\Pages\Transactions;
use Filament\Support\Enums\MaxWidth;
use Filament\Forms\Components\Select;
use Filament\Support\Enums\Alignment;
use Filament\Forms\Components\Actions;
use Guava\FilamentClusters\Forms\Cluster;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Support\Htmlable;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Transformers\AccountTransactionReportTransformer;

class AccountTransactions extends BaseReportPage
{
    protected static string $view = 'filament.pages.reports.account-transactions';

    protected static ?string $slug = 'reports/account-transactions';

    protected static bool $shouldRegisterNavigation = false;

    protected ReportService $reportService;

    protected ExportService $exportService;

    public function boot(ReportService $reportService, ExportService $exportService): void
    {
        $this->reportService = $reportService;
        $this->exportService = $exportService;
    }

    public function company()
    {
        return setting('*');
    }

    public function getMaxContentWidth(): MaxWidth | string | null
    {
        return 'max-w-[90rem]';
    }

    protected function initializeDefaultFilters(): void
    {
        if (empty($this->getFilterState('selectedAccount'))) {
            $this->setFilterState('selectedAccount', 'all');
        }
    }

    /**
     * @return array<Column>
     */
    public function getTable(): array
    {
        return [
            Column::make('date')
                ->label('DATE')
                ->markAsDate()
                ->alignment(Alignment::Left),
            Column::make('description')
                ->label('DESCRIPTION')
                ->alignment(Alignment::Left),
            Column::make('debit')
                ->label('DEBIT')
                ->alignment(Alignment::Right),
            Column::make('credit')
                ->label('CREDIT')
                ->alignment(Alignment::Right),
            Column::make('balance')
                ->label('RUNNING BALANCE')
                ->alignment(Alignment::Right),
        ];
    }

    public function filtersForm(Form $form): Form
    {
        return $form
            ->columns(4)
            ->schema([
                Select::make('selectedAccount')
                    ->label('Account')
                    ->options($this->getAccountOptions())
                    ->selectablePlaceholder(false)
                    ->searchable(),
                $this->getDateRangeFormComponent(),
                Cluster::make([
                    $this->getStartDateFormComponent(),
                    $this->getEndDateFormComponent(),
                ])->extraFieldWrapperAttributes([
                    'class' => 'report-hidden-label',
                ]),
                Actions::make([
                    Actions\Action::make('applyFilters')
                        ->label('Update Report')
                        ->action('applyFilters')
                        ->keyBindings(['mod+s'])
                        ->button(),
                ])->alignEnd()->verticallyAlignEnd(),
            ]);
    }

    protected function getAccountOptions(): array
    {
        $accounts = ChartOfAccount::query()
            ->get()
            ->groupBy(fn (ChartOfAccount $account) => $account->category->getPluralLabel())
            ->map(fn (Collection $accounts) => $accounts->pluck('name', 'id'))
            ->toArray();

        $allAccountsOption = [
            'All Accounts' => ['all' => 'All Accounts'],
        ];

        return $allAccountsOption + $accounts;
    }

    protected function buildReport(array $columns): ReportDTO
    {
        return $this->reportService->buildAccountTransactionsReport($this->getFormattedStartDate(), $this->getFormattedEndDate(), $columns, $this->getFilterState('selectedAccount'));
    }

    protected function getTransformer(ReportDTO $reportDTO): ExportableReport
    {
        return new AccountTransactionReportTransformer($reportDTO);
    }

    public function exportCSV(): StreamedResponse
    {
        return $this->exportService->exportToCsv($this->report, $this->getFilterState('startDate'), $this->getFilterState('endDate'));
    }

    public function exportPDF(): StreamedResponse
    {
        return $this->exportService->exportToPdf( $this->report, $this->getFilterState('startDate'), $this->getFilterState('endDate'));
    }

    public function getEmptyStateHeading(): string | Htmlable
    {
        return 'No Transactions Found';
    }

    public function getEmptyStateDescription(): string | Htmlable | null
    {
        return 'Adjust the account or date range, or start by creating a transaction.';
    }

    public function getEmptyStateIcon(): string
    {
        return 'heroicon-o-x-mark';
    }

    public function getEmptyStateActions(): array
    {
        return [
            Action::make('createTransaction')
                ->label('Create Transaction')
                ->url(Transactions::getUrl()),
        ];
    }

    public function hasNoTransactionsForSelectedAccount(): bool
    {
        $query = JournalEntries::query();
        $selectedAccountId = $this->getFilterState('selectedAccount');

        if ($selectedAccountId !== 'all') {
            $query->where('account_id', $selectedAccountId);
        }

        if ($this->getFilterState('startDate') && $this->getFilterState('endDate')) {
            $query->whereHas('transaction', function (Builder $query) {
                $query->whereBetween('posted_at', [$this->getFormattedStartDate(), $this->getFormattedEndDate()]);
            });
        }

        return $query->doesntExist();
    }

    public function tableHasEmptyState(): bool
    {
        return $this->hasNoTransactionsForSelectedAccount();
    }
}
