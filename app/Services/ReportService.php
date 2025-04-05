<?php

namespace App\Services;

use App\DTO\ReportDTO;
use App\DTO\AccountDTO;
use App\Support\Column;
use App\DTO\AccountTypeDTO;
use App\Models\BankAccount;
use App\ValueObjects\Money;
use App\DTO\AccountBalanceDTO;
use App\Models\ChartOfAccount;
use Illuminate\Support\Carbon;
use Illuminate\Support\Number;
use App\DTO\AccountCategoryDTO;
use App\Enums\ChartAccountType;
use App\DTO\CashFlowOverviewDTO;
use App\DTO\AccountTransactionDTO;
use App\Enums\ChartAccountCategory;
use Illuminate\Database\Eloquent\Builder;
use App\Utilities\Currency\CurrencyAccessor;
use App\Utilities\Currency\CurrencyConverter;

class ReportService
{
    public function __construct(
        protected AccountService $accountService,
    ) {}

    public function formatBalances(array $balances): AccountBalanceDTO
    {
        $defaultCurrency = CurrencyAccessor::getDefaultCurrency();

        foreach ($balances as $key => $balance) {
            $balances[$key] = Number::currency($balance, $defaultCurrency);
        }

        return new AccountBalanceDTO(
            startingBalance: $balances['starting_balance'] ?? null,
            debitBalance: $balances['debit_balance'] ?? null,
            creditBalance: $balances['credit_balance'] ?? null,
            netMovement: $balances['net_movement'] ?? null,
            endingBalance: $balances['ending_balance'] ?? null,
        );
    }

    public function buildAccountBalanceReport(string $startDate, string $endDate, array $columns = []): ReportDTO
    {
        $orderedCategories = ChartAccountCategory::getOrderedCategories();

        $accounts = $this->accountService->getAccountBalances($startDate, $endDate)
            ->orderByRaw('LENGTH(gl_code), gl_code')
            ->get();

        $columnNameKeys = array_map(fn (Column $column) => $column->getName(), $columns);

        $accountCategories = [];
        $reportTotalBalances = [];

        foreach ($orderedCategories as $category) {
            $accountsInCategory = $accounts->where('category', $category);

            $relevantFields = array_intersect($category->getRelevantBalanceFields(), $columnNameKeys);

            $categorySummaryBalances = array_fill_keys($relevantFields, 0);

            $categoryAccounts = [];

            /** @var Account $account */
            foreach ($accountsInCategory as $account) {
                $accountBalances = $this->calculateAccountBalances($account);

                foreach ($relevantFields as $field) {
                    $categorySummaryBalances[$field] += $accountBalances[$field];
                }

                $formattedAccountBalances = $this->formatBalances($accountBalances);

                $categoryAccounts[] = new AccountDTO(
                    $account->name,
                    $account->gl_code,
                    $account->id,
                    $formattedAccountBalances,
                    Carbon::parse($startDate)->toDateString(),
                    Carbon::parse($endDate)->toDateString(),
                );
            }

            foreach ($relevantFields as $field) {
                $reportTotalBalances[$field] = ($reportTotalBalances[$field] ?? 0) + $categorySummaryBalances[$field];
            }

            $formattedCategorySummaryBalances = $this->formatBalances($categorySummaryBalances);

            $accountCategories[$category->getPluralLabel()] = new AccountCategoryDTO(
                accounts: $categoryAccounts,
                summary: $formattedCategorySummaryBalances,
            );
        }

        $formattedReportTotalBalances = $this->formatBalances($reportTotalBalances);

        return new ReportDTO(
            categories: $accountCategories,
            overallTotal: $formattedReportTotalBalances,
            fields: $columns,
        );
    }

    public function calculateAccountBalances(ChartOfAccount $account): array
    {
        $category = $account->category;
        $balances = [
            'debit_balance' => $account->total_debit ?? 0,
            'credit_balance' => $account->total_credit ?? 0,
        ];

        if ($category->isNormalDebitBalance()) {
            $balances['net_movement'] = $balances['debit_balance'] - $balances['credit_balance'];
        } else {
            $balances['net_movement'] = $balances['credit_balance'] - $balances['debit_balance'];
        }

        if ($category->isReal()) {
            $balances['starting_balance'] = $account->starting_balance ?? 0;
            $balances['ending_balance'] = $balances['starting_balance'] + $balances['net_movement'];
        }

        return $balances;
    }

    public function calculateRetainedEarnings(?string $startDate, string $endDate): Money
    {
        $startDate ??= Carbon::parse($this->accountService->getEarliestTransactionDate())->toDateTimeString();
        $revenueAccounts = $this->accountService->getAccountBalances($startDate, $endDate)->where('category', ChartAccountCategory::Revenue)->get();

        $expenseAccounts = $this->accountService->getAccountBalances($startDate, $endDate)->where('category', ChartAccountCategory::Expense)->get();

        $revenueTotal = 0;
        $expenseTotal = 0;

        foreach ($revenueAccounts as $account) {
            $revenueBalances = $this->calculateAccountBalances($account);
            $revenueTotal += $revenueBalances['net_movement'];
        }

        foreach ($expenseAccounts as $account) {
            $expenseBalances = $this->calculateAccountBalances($account);
            $expenseTotal += $expenseBalances['net_movement'];
        }

        $retainedEarnings = $revenueTotal - $expenseTotal;

        return new Money($retainedEarnings, CurrencyAccessor::getDefaultCurrency());
    }

    public function buildAccountTransactionsReport(string $startDate, string $endDate, ?array $columns = null, ?string $accountId = 'all'): ReportDTO
    {
        $columns ??= [];
        $defaultCurrency = CurrencyAccessor::getDefaultCurrency();

        $accountIds = $accountId !== 'all' ? [$accountId] : [];

        $query = $this->accountService->getAccountBalances($startDate, $endDate, $accountIds)
            ->orderByRaw('LENGTH(gl_code), gl_code');

        $accounts = $query->with(['journalEntries' => $this->accountService->getTransactionDetailsSubquery($startDate, $endDate)])->get();

        $reportCategories = [];

        foreach ($accounts as $account) {
            $accountTransactions = [];
            $currentBalance = $account->starting_balance;

            $accountTransactions[] = new AccountTransactionDTO(
                id: null,
                date: 'Starting Balance',
                description: '',
                debit: '',
                credit: '',
                balance: Number::currency($currentBalance, $defaultCurrency),
                type: null,
                tableAction: null
            );

            /** @var ChartOfAccount $account */
            foreach ($account->journalEntries as $journalEntry) {
                $transaction = $journalEntry->transaction;
                $signedAmount = $journalEntry->signed_amount;


                if ($account->category->isNormalDebitBalance()) {
                    $currentBalance += $signedAmount;
                } else {
                    $currentBalance -= $signedAmount;
                }

                $formattedAmount = Number::currency($signedAmount, $defaultCurrency);

                $accountTransactions[] = new AccountTransactionDTO(
                    id: $transaction->id,
                    date: $transaction->posted_at->toDefaultDateFormat(),
                    description: $transaction->description ?? 'Add a description',
                    debit: $journalEntry->type->isDebit() ? $formattedAmount : '',
                    credit: $journalEntry->type->isCredit() ? $formattedAmount : '',
                    balance: Number::currency($currentBalance, $defaultCurrency),
                    type: $transaction->type,
                    tableAction: $transaction->type->isJournal() ? 'updateJournalTransaction' : 'updateTransaction'
                );


            }

            $balanceChange = $currentBalance - $account->starting_balance;

            $accountTransactions[] = new AccountTransactionDTO(
                id: null,
                date: 'Totals and Ending Balance',
                description: '',
                debit: Number::currency($account->total_debit, $defaultCurrency),
                credit: Number::currency($account->total_credit, $defaultCurrency),
                balance: Number::currency($currentBalance, $defaultCurrency),
                type: null,
                tableAction: null
            );

            $accountTransactions[] = new AccountTransactionDTO(
                id: null,
                date: 'Balance Change',
                description: '',
                debit: '',
                credit: '',
                balance: Number::currency($balanceChange, $defaultCurrency),
                type: null,
                tableAction: null
            );

            $reportCategories[] = [
                'category' => $account->name,
                'under' => $account->category->getLabel() . ' > ' . $account->subtype->name,
                'transactions' => $accountTransactions,
            ];
        }

        return new ReportDTO(categories: $reportCategories, fields: $columns);
    }

    public function buildTrialBalanceReport(string $trialBalanceType, string $asOfDate, array $columns = []): ReportDTO
    {
        $asOfDateCarbon = Carbon::parse($asOfDate);
        $startDateCarbon = Carbon::parse($this->accountService->getEarliestTransactionDate());

        $orderedCategories = ChartAccountCategory::getOrderedCategories();

        $isPostClosingTrialBalance = $trialBalanceType === 'postClosing';

        $accounts = $this->accountService->getAccountBalances($startDateCarbon->toDateTimeString(), $asOfDateCarbon->toDateTimeString())
            ->when($isPostClosingTrialBalance, fn (Builder $query) => $query->whereNotIn('category', [ChartAccountCategory::Revenue, ChartAccountCategory::Expense]))
            ->orderByRaw('LENGTH(gl_code), gl_code')
            ->get();

        $balanceFields = ['debit_balance', 'credit_balance'];

        $accountCategories = [];
        $reportTotalBalances = array_fill_keys($balanceFields, 0);

        foreach ($orderedCategories as $category) {
            $accountsInCategory = $accounts->where('category', $category);

            $categorySummaryBalances = array_fill_keys($balanceFields, 0);

            $categoryAccounts = [];

            /** @var ChartOfAccount $account */
            foreach ($accountsInCategory as $account) {
                $accountBalances = $this->calculateAccountBalances($account);

                $endingBalance = $accountBalances['ending_balance'] ?? $accountBalances['net_movement'];

                $trialBalance = $this->calculateTrialBalances($account->category, $endingBalance);

                foreach ($trialBalance as $balanceType => $balance) {
                    $categorySummaryBalances[$balanceType] += $balance;
                }

                $formattedAccountBalances = $this->formatBalances($trialBalance);

                $categoryAccounts[] = new AccountDTO(
                    $account->name,
                    $account->gl_code,
                    $account->id,
                    $formattedAccountBalances,
                    startDate: $startDateCarbon->toDateString(),
                    endDate: $asOfDateCarbon->toDateString(),
                );
            }

            if ($category === ChartAccountCategory::Equity && $isPostClosingTrialBalance) {
                $retainedEarningsAmount = $this->calculateRetainedEarnings($startDateCarbon->toDateTimeString(), $asOfDateCarbon->toDateTimeString())->getAmount();
                $isCredit = $retainedEarningsAmount >= 0;

                $categorySummaryBalances[$isCredit ? 'credit_balance' : 'debit_balance'] += abs($retainedEarningsAmount);

                $categoryAccounts[] = new AccountDTO(
                    'Retained Earnings',
                    'RE',
                    null,
                    $this->formatBalances([
                        'debit_balance' => $isCredit ? 0 : abs($retainedEarningsAmount),
                        'credit_balance' => $isCredit ? $retainedEarningsAmount : 0,
                    ]),
                    startDate: $startDateCarbon->toDateString(),
                    endDate: $asOfDateCarbon->toDateString(),
                );
            }

            foreach ($categorySummaryBalances as $balanceType => $balance) {
                $reportTotalBalances[$balanceType] += $balance;
            }

            $formattedCategorySummaryBalances = $this->formatBalances($categorySummaryBalances);

            $accountCategories[$category->getPluralLabel()] = new AccountCategoryDTO(
                accounts: $categoryAccounts,
                summary: $formattedCategorySummaryBalances,
            );
        }

        $formattedReportTotalBalances = $this->formatBalances($reportTotalBalances);

        return new ReportDTO($accountCategories, $formattedReportTotalBalances, $columns, $trialBalanceType);
    }

    public function getRetainedEarningsBalances(string $startDate, string $endDate): AccountBalanceDTO
    {
        $retainedEarningsAmount = $this->calculateRetainedEarnings($startDate, $endDate)->getAmount();

        $isCredit = $retainedEarningsAmount >= 0;
        $retainedEarningsDebitAmount = $isCredit ? 0 : abs($retainedEarningsAmount);
        $retainedEarningsCreditAmount = $isCredit ? $retainedEarningsAmount : 0;

        return $this->formatBalances([
            'debit_balance' => $retainedEarningsDebitAmount,
            'credit_balance' => $retainedEarningsCreditAmount,
        ]);
    }

    private function calculateTrialBalances(ChartAccountCategory $category, int $endingBalance): array
    {
        if ($category->isNormalDebitBalance()) {
            if ($endingBalance >= 0) {
                return ['debit_balance' => $endingBalance, 'credit_balance' => 0];
            }

            return ['debit_balance' => 0, 'credit_balance' => abs($endingBalance)];
        }

        if ($endingBalance >= 0) {
            return ['debit_balance' => 0, 'credit_balance' => $endingBalance];
        }

        return ['debit_balance' => abs($endingBalance), 'credit_balance' => 0];
    }



    public function buildIncomeStatementReport(string $startDate, string $endDate, array $columns = []): ReportDTO
    {
        // Query only relevant accounts and sort them at the query level
        $revenueAccounts = $this->accountService->getAccountBalances($startDate, $endDate)
            ->where('category', ChartAccountCategory::Revenue)
            ->orderByRaw('LENGTH(gl_code), gl_code')
            ->get();

        $cogsAccounts = $this->accountService->getAccountBalances($startDate, $endDate)
            ->whereRelation('subtype', 'name', 'Cost of Goods Sold')
            ->orderByRaw('LENGTH(gl_code), gl_code')
            ->get();

        $expenseAccounts = $this->accountService->getAccountBalances($startDate, $endDate)
            ->where('category', ChartAccountCategory::Expense)
            ->whereRelation('subtype', 'name', '!=', 'Cost of Goods Sold')
            ->orderByRaw('LENGTH(gl_code), gl_code')
            ->get();

        $accountCategories = [];
        $totalRevenue = 0;
        $totalCogs = 0;
        $totalExpenses = 0;

        // Define category groups
        $categoryGroups = [
            ChartAccountCategory::Revenue->getPluralLabel() => [
                'accounts' => $revenueAccounts,
                'total' => &$totalRevenue,
            ],
            'Cost of Goods Sold' => [
                'accounts' => $cogsAccounts,
                'total' => &$totalCogs,
            ],
            ChartAccountCategory::Expense->getPluralLabel() => [
                'accounts' => $expenseAccounts,
                'total' => &$totalExpenses,
            ],
        ];

        // Process each category group
        foreach ($categoryGroups as $label => $group) {
            $categoryAccounts = [];
            $netMovement = 0;

            foreach ($group['accounts'] as $account) {
                // Use the category type based on label
                $category = match ($label) {
                    ChartAccountCategory::Revenue->getPluralLabel() => ChartAccountCategory::Revenue,
                    ChartAccountCategory::Expense->getPluralLabel(), 'Cost of Goods Sold' => ChartAccountCategory::Expense,
                    default => null
                };

                if ($category !== null) {
                    $accountBalances = $this->calculateAccountBalances($account);
                    $movement = $accountBalances['net_movement'];
                    $netMovement += $movement;
                    $group['total'] += $movement;

                    $categoryAccounts[] = new AccountDTO(
                        $account->name,
                        $account->gl_code,
                        $account->id,
                        $this->formatBalances(['net_movement' => $movement]),
                        Carbon::parse($startDate)->toDateString(),
                        Carbon::parse($endDate)->toDateString(),
                    );
                }
            }

            $accountCategories[$label] = new AccountCategoryDTO(
                accounts: $categoryAccounts,
                summary: $this->formatBalances(['net_movement' => $netMovement])
            );
        }

        // Calculate gross and net profit
        $grossProfit = $totalRevenue - $totalCogs;
        $netProfit = $grossProfit - $totalExpenses;
        $formattedReportTotalBalances = $this->formatBalances(['net_movement' => $netProfit]);

        return new ReportDTO(
            categories: $accountCategories,
            overallTotal: $formattedReportTotalBalances,
            fields: $columns,
            startDate: Carbon::parse($startDate),
            endDate: Carbon::parse($endDate),
        );
    }

    public function buildCashFlowStatementReport(string $startDate, string $endDate, array $columns = []): ReportDTO
    {
        $sections = [
            'Operating Activities' => $this->buildOperatingActivities($startDate, $endDate),
            'Investing Activities' => $this->buildInvestingActivities($startDate, $endDate),
            'Financing Activities' => $this->buildFinancingActivities($startDate, $endDate),
        ];

        $totalCashFlows = $this->calculateTotalCashFlows($sections, $startDate);

        $overview = $this->buildCashFlowOverview($startDate, $endDate);

        return new ReportDTO(
            categories: $sections,
            overallTotal: $totalCashFlows,
            fields: $columns,
            overview: $overview,
            startDate: Carbon::parse($startDate),
            endDate: Carbon::parse($endDate),
        );
    }

    private function calculateTotalCashFlows(array $sections, string $startDate): AccountBalanceDTO
    {
        $totalInflow = 0;
        $totalOutflow = 0;
        $startingBalance = $this->accountService->getStartingBalanceForAllBankAccounts($startDate)->getAmount();

        foreach ($sections as $section) {
            $netMovement = $section->summary->netMovement ?? 0;
            //dd($netMovement);
            $numericNetMovement = CurrencyConverter::convertToCents($netMovement);


            if ($numericNetMovement > 0) {
                $totalInflow += $numericNetMovement;
            } else {
                $totalOutflow += $numericNetMovement;
            }
        }

        $netCashChange = $totalInflow + $totalOutflow;
        $endingBalance = $startingBalance + $netCashChange;

        return $this->formatBalances([
            'starting_balance' => $startingBalance,
            'debit_balance' => $totalInflow,
            'credit_balance' => abs($totalOutflow),
            'net_movement' => $netCashChange,
            'ending_balance' => $endingBalance,
        ]);
    }


    private function buildCashFlowOverview(string $startDate, string $endDate): CashFlowOverviewDTO
    {
        $accounts = $this->accountService->getBankAccountBalances($startDate, $endDate)->get();

        $startingBalanceAccounts = [];
        $endingBalanceAccounts = [];

        $startingBalanceTotal = 0;
        $endingBalanceTotal = 0;

        foreach ($accounts as $account) {
            $accountBalances = $this->calculateAccountBalances($account);

            $startingBalanceTotal += $accountBalances['starting_balance'];
            $endingBalanceTotal += $accountBalances['ending_balance'];

            $startingBalanceAccounts[] = new AccountDTO(
                accountName: $account->name,
                accountCode: $account->gl_code,
                accountId: $account->id,
                balance: $this->formatBalances(['starting_balance' => $accountBalances['starting_balance']]),
                startDate: $startDate,
                endDate: $endDate,
            );

            $endingBalanceAccounts[] = new AccountDTO(
                accountName: $account->name,
                accountCode: $account->gl_code,
                accountId: $account->id,
                balance: $this->formatBalances(['ending_balance' => $accountBalances['ending_balance']]),
                startDate: $startDate,
                endDate: $endDate,
            );
        }

        $startingBalanceSummary = $this->formatBalances(['starting_balance' => $startingBalanceTotal]);
        $endingBalanceSummary = $this->formatBalances(['ending_balance' => $endingBalanceTotal]);

        $overviewCategories = [
            'Starting Balance' => new AccountCategoryDTO(
                accounts: $startingBalanceAccounts,
                summary: $startingBalanceSummary,
            ),
            'Ending Balance' => new AccountCategoryDTO(
                accounts: $endingBalanceAccounts,
                summary: $endingBalanceSummary,
            ),
        ];

        return new CashFlowOverviewDTO($overviewCategories);
    }


    private function buildOperatingActivities(string $startDate, string $endDate): AccountCategoryDTO
    {
        $accounts = $this->accountService->getCashFlowAccountBalances($startDate, $endDate)
            ->whereIn('chart_of_accounts.account_type', [
                ChartAccountType::OperatingRevenue,
                ChartAccountType::UncategorizedRevenue,
                ChartAccountType::ContraRevenue,
                ChartAccountType::OperatingExpense,
                ChartAccountType::NonOperatingExpense,
                ChartAccountType::UncategorizedExpense,
                ChartAccountType::ContraExpense,
                ChartAccountType::CurrentAsset,
            ])
            ->whereRelation('subtype', 'name', '!=', 'Cash and Cash Equivalents')
            ->orderByRaw('LENGTH(gl_code), gl_code')
            ->get();

        $adjustments = $this->accountService->getCashFlowAccountBalances($startDate, $endDate)
            ->whereIn('chart_of_accounts.account_type', [
                ChartAccountType::ContraAsset,
                ChartAccountType::CurrentLiability,
            ])
            ->whereRelation('subtype', 'name', '!=', 'Short-Term Borrowings')
            ->orderByRaw('LENGTH(gl_code), gl_code')
            ->get();

        return $this->formatSectionAccounts($accounts, $adjustments, $startDate, $endDate);
    }


    private function buildInvestingActivities(string $startDate, string $endDate): AccountCategoryDTO
    {
        $accounts = $this->accountService->getCashFlowAccountBalances($startDate, $endDate)
            ->whereIn('chart_of_accounts.account_type', [ChartAccountType::NonCurrentAsset])
            ->orderByRaw('LENGTH(gl_code), gl_code')
            ->get();

        $adjustments = $this->accountService->getCashFlowAccountBalances($startDate, $endDate)
            ->whereIn('chart_of_accounts.account_type', [ChartAccountType::NonOperatingRevenue])
            ->orderByRaw('LENGTH(gl_code), gl_code')
            ->get();

        return $this->formatSectionAccounts($accounts, $adjustments, $startDate, $endDate);
    }



    private function buildFinancingActivities(string $startDate, string $endDate): AccountCategoryDTO
    {
        $accounts = $this->accountService->getCashFlowAccountBalances($startDate, $endDate)
            ->where(function (Builder $query) {
                $query->whereIn('chart_of_accounts.account_type', [
                    ChartAccountType::Equity,
                    ChartAccountType::NonCurrentLiability,
                ])
                    ->orWhere(function (Builder $subQuery) {
                        $subQuery->where('chart_of_accounts.account_type', ChartAccountType::CurrentLiability)
                            ->whereRelation('subtype', 'name', 'Short-Term Borrowings');
                    });
            })
            ->orderByRaw('LENGTH(gl_code), gl_code')
            ->get();

        return $this->formatSectionAccounts($accounts, [], $startDate, $endDate);
    }

    private function formatSectionAccounts($accounts, $adjustments, string $startDate, string $endDate): AccountCategoryDTO
    {
        $categoryAccountsByType = [];
        $sectionTotal = 0;
        $subCategoryTotals = [];

        // Process accounts and adjustments
        /** @var ChartOfAccount[] $entries */
        foreach ([$accounts, $adjustments] as $entries) {
            foreach ($entries as $entry) {
                $accountCategory = $entry->account_type->getCategory();
                $accountBalances = $this->calculateAccountBalances($entry);
                $netCashFlow = $accountBalances['net_movement'] ?? 0;

                // if ($entry->subtype->inverse_cash_flow) {
                //     $netCashFlow *= -1;
                // }

                // Accumulate totals
                $sectionTotal += $netCashFlow;
                $accountTypeName = $entry->subtype->name;
                $subCategoryTotals[$accountTypeName] = ($subCategoryTotals[$accountTypeName] ?? 0) + $netCashFlow;

                // Create AccountDTO and group by account type
                $accountDTO = new AccountDTO(
                    $entry->name,
                    $entry->gl_code,
                    $entry->id,
                    $this->formatBalances(['net_movement' => $netCashFlow]),
                    $startDate,
                    $endDate
                );

                $categoryAccountsByType[$accountTypeName][] = $accountDTO;
            }
        }

        // Prepare AccountTypeDTO for each account type with the accumulated totals
        $subCategories = [];
        foreach ($categoryAccountsByType as $typeName => $accountsInType) {
            $typeTotal = $subCategoryTotals[$typeName] ?? 0;
            $formattedTypeTotal = $this->formatBalances(['net_movement' => $typeTotal]);
            $subCategories[$typeName] = new AccountTypeDTO(
                accounts: $accountsInType,
                summary: $formattedTypeTotal
            );
        }

        // Format the overall section total as the section summary
        $formattedSectionTotal = $this->formatBalances(['net_movement' => $sectionTotal]);

        return new AccountCategoryDTO(
            accounts: [], // No direct accounts at the section level
            types: $subCategories, // Grouped by AccountTypeDTO
            summary: $formattedSectionTotal,
        );
    }


    public function buildBalanceSheetReport(string $asOfDate, array $columns = []): ReportDTO
    {
        $asOfDateCarbon = Carbon::parse($asOfDate);
        $startDateCarbon = Carbon::parse($this->accountService->getEarliestTransactionDate());

        $orderedCategories = array_filter(ChartAccountCategory::getOrderedCategories(), fn (ChartAccountCategory $category) => $category->isReal());

        $accounts = $this->accountService->getAccountBalances($startDateCarbon->toDateTimeString(), $asOfDateCarbon->toDateTimeString())
            ->whereIn('category', $orderedCategories)
            ->orderByRaw('LENGTH(gl_code), gl_code')
            ->get();

        $accountCategories = [];
        $reportTotalBalances = [
            'assets' => 0,
            'liabilities' => 0,
            'equity' => 0,
        ];

        foreach ($orderedCategories as $category) {
            $categorySummaryBalances = ['ending_balance' => 0];

            $categoryAccountsByType = [];
            $categoryAccounts = [];
            $subCategoryTotals = [];

            /** @var ChartOfAccount $account */
            foreach ($accounts as $account) {

                if ($account->account_type->getCategory() === $category) {
                    $accountBalances = $this->calculateAccountBalances($account);
                    $endingBalance = $accountBalances['ending_balance'] ?? $accountBalances['net_movement'];

                    $categorySummaryBalances['ending_balance'] += $endingBalance;

                    $formattedAccountBalances = $this->formatBalances($accountBalances);

                    $accountDTO = new AccountDTO(
                        $account->name,
                        $account->gl_code,
                        $account->id,
                        $formattedAccountBalances,
                        startDate: $startDateCarbon->toDateString(),
                        endDate: $asOfDateCarbon->toDateString(),
                    );

                    if ($category === ChartAccountCategory::Equity && $account->account_type === ChartAccountType::Equity) {
                        $categoryAccounts[] = $accountDTO;
                    } else {
                        $accountType = $account->account_type->getPluralLabel();
                        $categoryAccountsByType[$accountType][] = $accountDTO;
                        $subCategoryTotals[$accountType] = ($subCategoryTotals[$accountType] ?? 0) + $endingBalance;
                    }
                }
            }

            if ($category === ChartAccountCategory::Equity) {
                $retainedEarningsAmount = $this->calculateRetainedEarnings($startDateCarbon->toDateTimeString(), $asOfDateCarbon->toDateTimeString())->getAmount();

                $categorySummaryBalances['ending_balance'] += $retainedEarningsAmount;

                $retainedEarningsDTO = new AccountDTO(
                    'Retained Earnings',
                    'RE',
                    null,
                    $this->formatBalances(['ending_balance' => $retainedEarningsAmount]),
                    startDate: $startDateCarbon->toDateString(),
                    endDate: $asOfDateCarbon->toDateString(),
                );

                $categoryAccounts[] = $retainedEarningsDTO;
            }

            $subCategories = [];
            foreach ($categoryAccountsByType as $accountType => $accountsInType) {
                $subCategorySummary = $this->formatBalances([
                    'ending_balance' => $subCategoryTotals[$accountType] ?? 0,
                ]);

                $subCategories[$accountType] = new AccountTypeDTO(
                    accounts: $accountsInType,
                    summary: $subCategorySummary
                );
            }

            $reportTotalBalances[match ($category) {
                ChartAccountCategory::Asset => 'assets',
                ChartAccountCategory::Liability => 'liabilities',
                ChartAccountCategory::Equity => 'equity',
            }] += $categorySummaryBalances['ending_balance'];

            $accountCategories[$category->getPluralLabel()] = new AccountCategoryDTO(
                accounts: $categoryAccounts,
                types: $subCategories,
                summary: $this->formatBalances($categorySummaryBalances),
            );
        }

        $netAssets = $reportTotalBalances['assets'] - $reportTotalBalances['liabilities'];

        $formattedReportTotalBalances = $this->formatBalances(['ending_balance' => $netAssets]);

        return new ReportDTO(
            categories: $accountCategories,
            overallTotal: $formattedReportTotalBalances,
            fields: $columns,
            startDate: $startDateCarbon,
            endDate: $asOfDateCarbon,
        );
    }
}
