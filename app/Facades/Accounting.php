<?php

namespace App\Facades;

use App\Contracts\AccountHandler;
use App\Enums\ChartAccountCategory;
use App\Models\ChartOfAccount;
use Illuminate\Support\Facades\Facade;

/**
 * @method static Money getDebitBalance(ChartOfAccount $account, string $startDate, string $endDate)
 * @method static Money getCreditBalance(ChartOfAccount $account, string $startDate, string $endDate)
 * @method static Money getNetMovement(ChartOfAccount $account, string $startDate, string $endDate)
 * @method static Money|null getStartingBalance(ChartOfAccount $account, string $startDate)
 * @method static Money|null getEndingBalance(ChartOfAccount $account, string $startDate, string $endDate)
 * @method static int calculateNetMovementByCategory(ChartAccountCategory $category, int $debitBalance, int $creditBalance)
 * @method static array getBalances(ChartOfAccount $account, string $startDate, string $endDate)
 * @method static AccountBalanceDTO formatBalances(array $balances)
 * @method static AccountBalanceReportDTO buildAccountBalanceReport(string $startDate, string $endDate)
 * @method static Money getTotalBalanceForAllBankAccounts(string $startDate, string $endDate)
 * @method static array getAccountCategoryOrder()
 * @method static string getEarliestTransactionDate()
 *
 * @see AccountHandler
 */
class Accounting extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AccountHandler::class;
    }
}
