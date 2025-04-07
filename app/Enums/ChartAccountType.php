<?php

namespace App\Enums;

use App\Enums\ChartAccountCategory;
use Filament\Support\Contracts\HasLabel;

enum ChartAccountType: string implements HasLabel
{
    case CurrentAsset = 'current_asset';
    case NonCurrentAsset = 'non_current_asset';
    case ContraAsset = 'contra_asset';
    case CurrentLiability = 'current_liability';
    case NonCurrentLiability = 'non_current_liability';
    case ContraLiability = 'contra_liability';
    case Equity = 'equity'; 
    case ContraEquity = 'contra_equity';
    case OperatingRevenue = 'operating_revenue';
    case NonOperatingRevenue = 'non_operating_revenue';
    case ContraRevenue = 'contra_revenue';
    case UncategorizedRevenue = 'uncategorized_revenue';
    case OperatingExpense = 'operating_expense';
    case NonOperatingExpense = 'non_operating_expense';
    case ContraExpense = 'contra_expense';
    case UncategorizedExpense = 'uncategorized_expense';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::CurrentAsset => 'Current Asset',
            self::NonCurrentAsset => 'Non-Current Asset',
            self::ContraAsset => 'Contra Asset',
            self::CurrentLiability => 'Current Liability',
            self::NonCurrentLiability => 'Non-Current Liability',
            self::ContraLiability => 'Contra Liability',
            self::Equity => 'Equity',
            self::ContraEquity => 'Contra Equity',
            self::OperatingRevenue => 'Operating Revenue',
            self::NonOperatingRevenue => 'Non-Operating Revenue',
            self::ContraRevenue => 'Contra Revenue',
            self::UncategorizedRevenue => 'Uncategorized Revenue',
            self::OperatingExpense => 'Operating Expense',
            self::NonOperatingExpense => 'Non-Operating Expense',
            self::ContraExpense => 'Contra Expense',
            self::UncategorizedExpense => 'Uncategorized Expense',
        };
    }

    public function getPluralLabel(): ?string
    {
        return match ($this) {
            self::CurrentAsset => 'Current Assets',
            self::NonCurrentAsset => 'Non-Current Assets',
            self::ContraAsset => 'Contra Assets',
            self::CurrentLiability => 'Current Liabilities',
            self::NonCurrentLiability => 'Non-Current Liabilities',
            self::ContraLiability => 'Contra Liabilities',
            self::Equity => 'Equity',
            self::ContraEquity => 'Contra Equity',
            self::OperatingRevenue => 'Operating Revenue',
            self::NonOperatingRevenue => 'Non-Operating Revenue',
            self::ContraRevenue => 'Contra Revenue',
            self::UncategorizedRevenue => 'Uncategorized Revenue',
            self::OperatingExpense => 'Operating Expenses',
            self::NonOperatingExpense => 'Non-Operating Expenses',
            self::ContraExpense => 'Contra Expenses',
            self::UncategorizedExpense => 'Uncategorized Expenses',
        };
    }

    public function getCategory(): ChartAccountCategory
    {
        return match ($this) {
            self::CurrentAsset, self::NonCurrentAsset, self::ContraAsset => ChartAccountCategory::Asset,
            self::CurrentLiability, self::NonCurrentLiability, self::ContraLiability => ChartAccountCategory::Liability,
            self::Equity, self::ContraEquity => ChartAccountCategory::Equity,
            self::OperatingRevenue, self::NonOperatingRevenue, self::ContraRevenue, self::UncategorizedRevenue => ChartAccountCategory::Revenue,
            self::OperatingExpense, self::NonOperatingExpense, self::ContraExpense, self::UncategorizedExpense => ChartAccountCategory::Expense,
        };
    }

    public function isUncategorized(): bool
    {
        return match ($this) {
            self::UncategorizedRevenue, self::UncategorizedExpense => true,
            default => false,
        };
    }
}
