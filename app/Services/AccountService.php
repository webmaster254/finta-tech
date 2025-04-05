<?php

namespace App\Services;



use Closure;
use App\Models\Transaction;
use App\ValueObjects\Money;
use App\Models\ChartOfAccount;
use Illuminate\Support\Facades\DB;
use App\Enums\ChartAccountCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use App\Utilities\Currency\CurrencyAccessor;
use App\Repositories\Accounting\JournalEntryRepository;

class AccountService
{
    public function __construct(
        protected JournalEntryRepository $journalEntryRepository
    ) {}

    public function getDebitBalance(ChartOfAccount $account, string $startDate, string $endDate): Money
    {
        $query = $this->getAccountBalances($startDate, $endDate, [$account->id])->first();

        return new Money($query->total_debit, $account->currency_code);
    }

    public function getCreditBalance(ChartOfAccount $account, string $startDate, string $endDate): Money
    {
        $query = $this->getAccountBalances($startDate, $endDate, [$account->id])->first();

        return new Money($query->total_credit, $account->currency_code);
    }

    public function getNetMovement(ChartOfAccount $account, string $startDate, string $endDate): Money
    {
        $query = $this->getAccountBalances($startDate, $endDate, [$account->id])->first();

        $netMovement = $this->calculateNetMovementByCategory(
            $account->category,
            $query->total_debit ?? 0,
            $query->total_credit ?? 0
        );

        return new Money($netMovement, $account->currency_code);
    }

    public function getStartingBalance(ChartOfAccount $account, string $startDate, bool $override = false): ?Money
    {
        if ($override === false && $account->category->isNominal()) {
            return null;
        }

        $query = $this->getAccountBalances($startDate, $startDate, [$account->id])->first();

        return new Money($query->starting_balance ?? 0, $account->currency_code);
    }

    public function getEndingBalance(ChartOfAccount $account, string $startDate, string $endDate): ?Money
    {
        $query = $this->getAccountBalances($startDate, $endDate, [$account->id])->first();

        $netMovement = $this->calculateNetMovementByCategory(
            $account->category,
            $query->total_debit ?? 0,
            $query->total_credit ?? 0
        );

        if ($account->category->isNominal()) {
            return new Money($netMovement, $account->currency_code);
        }

        $endingBalance = ($query->starting_balance ?? 0) + $netMovement;

        return new Money($endingBalance, $account->currency_code);
    }

    private function calculateNetMovementByCategory(ChartAccountCategory $category, int $debitBalance, int $creditBalance): int
    {
        if ($category->isNormalDebitBalance()) {
            return $debitBalance - $creditBalance;
        } else {
            return $creditBalance - $debitBalance;
        }
    }

    public function getBalances(ChartOfAccount $account, string $startDate, string $endDate): array
    {
        $query = $this->getAccountBalances($startDate, $endDate, [$account->id])->first();

        $needStartingBalances = $account->category->isReal();

        $netMovement = $this->calculateNetMovementByCategory(
            $account->category,
            $query->total_debit ?? 0,
            $query->total_credit ?? 0
        );

        $balances = [
            'debit_balance' => $query->total_debit,
            'credit_balance' => $query->total_credit,
            'net_movement' => $netMovement,
            'starting_balance' => $needStartingBalances ? ($query->starting_balance ?? 0) : null,
            'ending_balance' => $needStartingBalances
                ? ($query->starting_balance ?? 0) + $netMovement
                : $netMovement, // For nominal accounts, ending balance is just the net movement
        ];

        // Return balances, filtering out any null values
        return array_filter($balances, static fn ($value) => $value !== null);
    }

    public function getTransactionDetailsSubquery(string $startDate, string $endDate): Closure
    {
        return static function ($query) use ($startDate, $endDate) {
            $query->select(
                'journal_entries2.id',
               // 'journal_entries2.chart_of_account_id',
                'journal_entries2.transaction_id',
                'journal_entries2.type',
                'journal_entries2.amount',
                DB::raw('journal_entries2.amount * IF(journal_entries2.type = "debit", 1, -1) AS signed_amount')
            )
                ->whereBetween('transactions.posted_at', [$startDate, $endDate])
                ->join('transactions', 'transactions.id', '=', 'journal_entries2.transaction_id')
                ->orderBy('transactions.posted_at')
                ->with('transaction:id,type,description,posted_at');
        };
    }

    public function getAccountBalances(string $startDate, string $endDate, array $accountIds = []): Builder
    {
        $accountIds = array_map('intval', $accountIds);

        $query = ChartOfAccount::query()
            ->select([
                'chart_of_accounts.id',
                'chart_of_accounts.name',
                'chart_of_accounts.category',
                'chart_of_accounts.account_type',
                'chart_of_accounts.subtype_id',
                'chart_of_accounts.currency_code',
                'chart_of_accounts.gl_code',
            ])
            ->addSelect([
                DB::raw("
                    COALESCE(
                        IF(chart_of_accounts.category IN ('asset', 'expense'),
                            SUM(IF(journal_entries2.type = 'debit' AND transactions.posted_at < ?, journal_entries2.amount, 0)) -
                            SUM(IF(journal_entries2.type = 'credit' AND transactions.posted_at < ?, journal_entries2.amount, 0)),
                            SUM(IF(journal_entries2.type = 'credit' AND transactions.posted_at < ?, journal_entries2.amount, 0)) -
                            SUM(IF(journal_entries2.type = 'debit' AND transactions.posted_at < ?, journal_entries2.amount, 0))
                        ), 0
                    ) AS starting_balance
                "),
                DB::raw("
                    COALESCE(SUM(
                        IF(journal_entries2.type = 'debit' AND transactions.posted_at BETWEEN ? AND ?, journal_entries2.amount, 0)
                    ), 0) AS total_debit
                "),
                DB::raw("
                    COALESCE(SUM(
                        IF(journal_entries2.type = 'credit' AND transactions.posted_at BETWEEN ? AND ?, journal_entries2.amount, 0)
                    ), 0) AS total_credit
                "),
            ])
            ->join('journal_entries2', 'journal_entries2.chart_of_account_id', '=', 'chart_of_accounts.id')
            ->join('transactions', function (JoinClause $join) use ($endDate) {
                $join->on('transactions.id', '=', 'journal_entries2.transaction_id')
                    ->where('transactions.posted_at', '<=', $endDate);
            })
            ->groupBy([
                'chart_of_accounts.id',
                'chart_of_accounts.name',
                'chart_of_accounts.category',
                'chart_of_accounts.account_type',
                'chart_of_accounts.subtype_id',
                'chart_of_accounts.currency_code',
                'chart_of_accounts.gl_code',
            ])
            ->with(['subtype:id,name']);

        if (! empty($accountIds)) {
            $query->whereIn('chart_of_accounts.id', $accountIds);
        }

        $query->addBinding([$startDate, $startDate, $startDate, $startDate, $startDate, $endDate, $startDate, $endDate], 'select');

        return $query;
    }

    public function getCashFlowAccountBalances(string $startDate, string $endDate, array $accountIds = []): Builder
    {
        $accountIds = array_map('intval', $accountIds);

        $query = ChartOfAccount::query()
            ->select([
                'chart_of_accounts.id',
                'chart_of_accounts.name',
                'chart_of_accounts.category',
                'chart_of_accounts.account_type',
                'chart_of_accounts.subtype_id',
                'chart_of_accounts.currency_code',
                'chart_of_accounts.gl_code',
            ])
            ->addSelect([
                DB::raw("
                    COALESCE(
                        IF(chart_of_accounts.category IN ('asset', 'expense'),
                            SUM(IF(journal_entries2.type = 'debit' AND transactions.posted_at < ?, journal_entries2.amount, 0)) -
                            SUM(IF(journal_entries2.type = 'credit' AND transactions.posted_at < ?, journal_entries2.amount, 0)),
                            SUM(IF(journal_entries2.type = 'credit' AND transactions.posted_at < ?, journal_entries2.amount, 0)) -
                            SUM(IF(journal_entries2.type = 'debit' AND transactions.posted_at < ?, journal_entries2.amount, 0))
                        ), 0
                    ) AS starting_balance
                "),
                DB::raw("
                    COALESCE(SUM(
                        IF(journal_entries2.type = 'debit' AND transactions.posted_at BETWEEN ? AND ?, journal_entries2.amount, 0)
                    ), 0) AS total_debit
                "),
                DB::raw("
                    COALESCE(SUM(
                        IF(journal_entries2.type = 'credit' AND transactions.posted_at BETWEEN ? AND ?, journal_entries2.amount, 0)
                    ), 0) AS total_credit
                "),
            ])
            ->join('journal_entries2', 'journal_entries2.chart_of_account_id', '=', 'chart_of_accounts.id')
            ->join('transactions', function (JoinClause $join) use ($endDate) {
                $join->on('transactions.id', '=', 'journal_entries2.transaction_id')
                    ->where('transactions.posted_at', '<=', $endDate);
            })
            // ->whereExists(function (\Illuminate\Database\Query\Builder $subQuery) {
            //     $subQuery->select(DB::raw(1))
            //         ->from('journal_entries as je')
            //         ->join('chart_of_accounts as bank_accounts', 'bank_accounts.id', '=', 'je.account_id')
            //         ->whereNotNull('bank_accounts.bank_account_id')
            //         ->whereColumn('je.transaction_id', 'journal_entries.transaction_id');
            // })
            ->groupBy([
                'chart_of_accounts.id',
                'chart_of_accounts.name',
                'chart_of_accounts.category',
                'chart_of_accounts.account_type',
                'chart_of_accounts.subtype_id',
                'chart_of_accounts.currency_code',
                'chart_of_accounts.gl_code',
            ])
            ->with(['subtype:id,name']);

        if (! empty($accountIds)) {
            $query->whereIn('chart_of_accounts.id', $accountIds);
        }

        $query->addBinding([$startDate, $startDate, $startDate, $startDate, $startDate, $endDate, $startDate, $endDate], 'select');

        return $query;
    }

    public function getTotalBalanceForAllBankAccounts(string $startDate, string $endDate): Money
    {
        $accountIds = ChartOfAccount::whereHas('accountable')
            ->pluck('id')
            ->toArray();

        if (empty($accountIds)) {
            return new Money(0, CurrencyAccessor::getDefaultCurrency());
        }

        $result = DB::table('journal_entries2')
            ->join('transactions', function (JoinClause $join) use ($endDate) {
                $join->on('transactions.id', '=', 'journal_entries2.transaction_id')
                    ->where('transactions.posted_at', '<=', $endDate);
            })
            ->whereIn('journal_entries2.chart_of_account_id', $accountIds)
            ->selectRaw('
            SUM(CASE
                WHEN transactions.posted_at < ? AND journal_entries2.type = "debit" THEN journal_entries2.amount
                WHEN transactions.posted_at < ? AND journal_entries2.type = "credit" THEN -journal_entries2.amount
                ELSE 0
            END) AS totalStartingBalance,
            SUM(CASE
                WHEN transactions.posted_at BETWEEN ? AND ? AND journal_entries2.type = "debit" THEN journal_entries2.amount
                WHEN transactions.posted_at BETWEEN ? AND ? AND journal_entries2.type = "credit" THEN -journal_entries2.amount
                ELSE 0
            END) AS totalNetMovement
        ', [
                $startDate,
                $startDate,
                $startDate,
                $endDate,
                $startDate,
                $endDate,
            ])
            ->first();

        $totalBalance = $result->totalStartingBalance + $result->totalNetMovement;

        return new Money($totalBalance, CurrencyAccessor::getDefaultCurrency());
    }

    public function getStartingBalanceForAllBankAccounts(string $startDate): Money
    {
        $accountIds = ChartOfAccount::whereHas('accountable')
            ->pluck('id')
            ->toArray();

        if (empty($accountIds)) {
            return new Money(0, CurrencyAccessor::getDefaultCurrency());
        }

        $result = DB::table('journal_entries2')
            ->join('transactions', function (JoinClause $join) use ($startDate) {
                $join->on('transactions.id', '=', 'journal_entries2.transaction_id')
                    ->where('transactions.posted_at', '<', $startDate);
            })
            ->whereIn('journal_entries2.chart_of_account_id', $accountIds)
            ->selectRaw('
            SUM(CASE
                WHEN transactions.posted_at < ? AND journal_entries2.type = "debit" THEN journal_entries2.amount
                WHEN transactions.posted_at < ? AND journal_entries2.type = "credit" THEN -journal_entries2.amount
                ELSE 0
            END) AS totalStartingBalance
        ', [
                $startDate,
                $startDate,
            ])
            ->first();

        return new Money($result->totalStartingBalance ?? 0, CurrencyAccessor::getDefaultCurrency());
    }

    public function getBankAccountBalances(string $startDate, string $endDate): Builder | array
    {
        $accountIds = ChartOfAccount::whereHas('accountable')
            ->pluck('id')
            ->toArray();

        if (empty($accountIds)) {
            return [];
        }

        return $this->getAccountBalances($startDate, $endDate, $accountIds);
    }

    public function getEarliestTransactionDate(): string
    {
        $earliestDate = Transaction::min('posted_at');

        return $earliestDate ?? today()->toDateTimeString();
    }
}
