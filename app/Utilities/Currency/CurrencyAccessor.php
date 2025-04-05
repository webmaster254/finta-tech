<?php

namespace App\Utilities\Currency;

use App\Models\Currency;


class CurrencyAccessor
{
    public static function getDefaultCurrency(): ?string
    {
        return Currency::query()
            ->where('is_default', true)
            ->value('symbol');
    }



    public static function getAvailableCurrencies(): array
    {


        $storedCurrencies = Currency::query()
            ->pluck('symbol')
            ->toArray();



        return $storedCurrencies;
    }



}
