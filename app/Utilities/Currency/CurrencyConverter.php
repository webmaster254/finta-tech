<?php

namespace App\Utilities\Currency;


use Filament\Forms\Set;

class CurrencyConverter
{
    public static function convertAndSet($newCurrency, $oldCurrency, $amount): ?string
    {
        if ($newCurrency === null || $oldCurrency === $newCurrency) {
            return null;
        }

        $old_attr = currency($oldCurrency);
        $new_attr = currency($newCurrency);
        $temp_balance = str_replace([$old_attr->getThousandsSeparator(), $old_attr->getDecimalMark()], ['', '.'], $amount);

        return number_format((float) $temp_balance, $new_attr->getPrecision(), $new_attr->getDecimalMark(), $new_attr->getThousandsSeparator());
    }

    public static function convertBalance(int $balance, string $oldCurrency, string $newCurrency): int
    {
        return money($balance, $oldCurrency)->swapAmountFor($newCurrency);
    }

    public static function prepareForMutator(int $balance, string $currency): string
    {
        return money($balance, $currency)->formatSimple();
    }

    public static function prepareForAccessor(string $balance, string $currency): int
    {
        return money($balance, $currency, true)->getAmount();
    }

    public static function convertToCents(string | float $amount, ?string $currency = null): int
    {
        $currency ??= CurrencyAccessor::getDefaultCurrency();

        return money($amount, $currency)->getAmount();
    }
    public static function formatCentsToMoney(int $amount, ?string $currency = null): string
    {
        $currency ??= CurrencyAccessor::getDefaultCurrency();

        return money($amount, $currency)->format();
    }



}
