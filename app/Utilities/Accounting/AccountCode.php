<?php

namespace App\Utilities\Accounting;



use RuntimeException;
use App\Enums\ChartAccountType;
use App\Models\ChartOfAccountSubtype;

class AccountCode
{
    public static function isValidCode($code, ChartAccountType $type): bool
    {
        $range = self::getRangeForType($type);

        $mainAccountPart = explode('-', $code)[0];

        $numericValue = (int) $mainAccountPart;

        return $numericValue >= $range[0] && $numericValue <= $range[1];
    }

    public static function getMessage(ChartAccountType $type): string
    {
        $range = self::getRangeForType($type);

        return "The account code must range from {$range[0]} to {$range[1]} for a {$type->getLabel()}.";
    }

    public static function getRangeForType(ChartAccountType $type): array
    {
        return match ($type) {
            ChartAccountType::CurrentAsset => [1000, 1499],
            ChartAccountType::NonCurrentAsset => [1500, 1899],
            ChartAccountType::ContraAsset => [1900, 1999],
            ChartAccountType::CurrentLiability => [2000, 2499],
            ChartAccountType::NonCurrentLiability => [2500, 2899],
            ChartAccountType::ContraLiability => [2900, 2999],
            ChartAccountType::Equity => [3000, 3899],
            ChartAccountType::ContraEquity => [3900, 3999],
            ChartAccountType::OperatingRevenue => [4000, 4499],
            ChartAccountType::NonOperatingRevenue => [4500, 4899],
            ChartAccountType::ContraRevenue => [4900, 4949],
            ChartAccountType::UncategorizedRevenue => [4950, 4999],
            ChartAccountType::OperatingExpense => [5000, 5499],
            ChartAccountType::NonOperatingExpense => [5500, 5899],
            ChartAccountType::ContraExpense => [5900, 5949],
            ChartAccountType::UncategorizedExpense => [5950, 5999],
        };
    }

    public static function generate(ChartOfAccountSubtype $accountSubtype): string
    {
        $subtypeName = $accountSubtype->name;
        $typeEnum = $accountSubtype->type;
        $typeValue = $typeEnum->value;

        $baseCode = config("chart-of-accounts.default.{$typeValue}.{$subtypeName}.base_code");
        $range = self::getRangeForType($typeEnum);

        $lastAccount = $accountSubtype->accounts()
            ->whereNotNull('gl_code')
            ->orderBy('gl_code', 'desc')
            ->first();


        $nextNumericValue = $lastAccount ? (int) explode('-', $lastAccount->code)[0] + 1 : (int) $baseCode;

        if ($nextNumericValue > $range[1]) {
            throw new RuntimeException("The account code range for a {$typeEnum->getLabel()} has been exceeded.");
        }

        while ($accountSubtype->accounts()->where('gl_code', '=', (string) $nextNumericValue)->exists()) {
            $nextNumericValue++;

            if ($nextNumericValue > $range[1]) {
                throw new RuntimeException("The account code range for a {$typeEnum->getLabel()} has been exceeded.");
            }
        }

        return (string) $nextNumericValue;
    }
}
