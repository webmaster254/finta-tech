<?php

namespace App\Contracts;


use App\ValueObjects\Money;
use App\Models\ChartOfAccount;
use App\Enums\ChartAccountCategory;

interface AccountHandler
{
    public function getDebitBalance(ChartOfAccount $account, string $startDate, string $endDate): Money;

    public function getCreditBalance(ChartOfAccount $account, string $startDate, string $endDate): Money;

    public function getNetMovement(ChartOfAccount $account, string $startDate, string $endDate): Money;

    public function getStartingBalance(ChartOfAccount $account, string $startDate): ?Money;

    public function getEndingBalance(ChartOfAccount $account, string $startDate, string $endDate): ?Money;



    public function getBalances(ChartOfAccount $account, string $startDate, string $endDate, array $fields): array;



    public function getTotalBalanceForAllBankAccounts(string $startDate, string $endDate): Money;


    public function getEarliestTransactionDate(): string;
}
