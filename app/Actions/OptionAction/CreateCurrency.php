<?php

namespace App\Actions\OptionAction;

use App\Models\Currency;
use App\Utilities\Currency\CurrencyAccessor;

class CreateCurrency
{
    public static function create(string $code, string $name, string $rate): Currency
    {
        $defaultCurrency = CurrencyAccessor::getDefaultCurrency();

        $hasDefaultCurrency = $defaultCurrency !== null;
        $currency = currency($code);

        return Currency::create([
            'name' => $name,
            'code' => $code,
            'symbol' => $symbol,
            'position' => 'left',
            'is_default' => 'false',
        ]);
    }
}
