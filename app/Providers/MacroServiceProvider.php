<?php

namespace App\Providers;

use Closure;
use BackedEnum;
use Akaunting\Money\Money;
use Illuminate\Support\Str;
use Akaunting\Money\Currency;
use Illuminate\Support\Carbon;
use App\Enums\Setting\DateFormat;
use App\Models\Setting\Localization;
use Filament\Forms\Components\Field;
use App\Models\ChartOfAccountSubtype;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\ServiceProvider;
use Filament\Forms\Components\TextInput;
use App\Utilities\Accounting\AccountCode;
use Filament\Forms\Components\DatePicker;
use App\Utilities\Currency\CurrencyAccessor;

class MacroServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        TextInput::macro('money', function (string | Closure | null $currency = null): static {
            $this->extraAttributes(['wire:key' => Str::random()])
                ->prefix(static function (TextInput $component) use ($currency) {
                    $currency = $component->evaluate($currency);

                    return currency($currency)->getPrefix();
                })
                ->suffix(static function (TextInput $component) use ($currency) {
                    $currency = $component->evaluate($currency);

                    return currency($currency)->getSuffix();
                })
                ->mask(static function (TextInput $component) use ($currency) {
                    $currency = $component->evaluate($currency);

                    return moneyMask($currency);
                });

            return $this;
        });

        TextColumn::macro('defaultDateFormat', function (): static {
            $localization = Localization::firstOrFail();

            $dateFormat = $localization->date_format->value ?? DateFormat::DEFAULT;
            $timezone = $localization->timezone ?? Carbon::now()->timezoneName;

            $this->date($dateFormat, $timezone);

            return $this;
        });

        DatePicker::macro('defaultDateFormat', function (): static {
            $localization = Localization::firstOrFail();

            $dateFormat = $localization->date_format->value ?? DateFormat::DEFAULT;
            $timezone = $localization->timezone ?? Carbon::now()->timezoneName;

            $this->displayFormat($dateFormat)
                ->timezone($timezone);

            return $this;
        });

        TextColumn::macro('currency', function (string | Closure | null $currency = null, ?bool $convert = null): static {
            $this->formatStateUsing(static function (TextColumn $column, $state) use ($currency, $convert): ?string {
                if (blank($state)) {
                    return null;
                }

                $currency = $column->evaluate($currency);
                $convert = $column->evaluate($convert);

                return money($state, $currency, $convert)->format();
            });

            return $this;
        });

        TextInput::macro('rate', function (string | Closure | null $computation = null): static {
            $this->extraAttributes(['wire:key' => Str::random()])
                ->prefix(static function (TextInput $component) use ($computation) {
                    $computation = $component->evaluate($computation);

                    return ratePrefix(computation: $computation);
                })
                ->suffix(static function (TextInput $component) use ($computation) {
                    $computation = $component->evaluate($computation);

                    return rateSuffix(computation: $computation);
                })
                ->mask(static function (TextInput $component) use ($computation) {
                    $computation = $component->evaluate($computation);

                    return rateMask(computation: $computation);
                })
                ->rule(static function (TextInput $component) use ($computation) {
                    return static function (string $attribute, $value, Closure $fail) use ($computation, $component) {
                        $computation = $component->evaluate($computation);
                        $numericValue = (float) $value;

                        if ($computation instanceof BackedEnum) {
                            $computation = $computation->value;
                        }

                        if ($computation === 'percentage' || $computation === 'compound') {
                            if ($numericValue < 0 || $numericValue > 100) {
                                $fail(translate('The rate must be between 0 and 100.'));
                            }
                        } elseif ($computation === 'fixed' && $numericValue < 0) {
                            $fail(translate('The rate must be greater than 0.'));
                        }
                    };
                });

            return $this;
        });

        Field::macro('validateAccountCode', function (string | Closure | null $subtype = null): static {
            $this
                ->rules([
                    fn (Field $component): Closure => static function (string $attribute, $value, Closure $fail) use ($subtype, $component) {
                        $subtype = $component->evaluate($subtype);
                        $chartSubtype = ChartOfAccountSubtype::find($subtype);
                        $type = $chartSubtype->type;

                        if (! AccountCode::isValidCode($value, $type)) {
                            $message = AccountCode::getMessage($type);

                            $fail($message);
                        }
                    },
                ]);

            return $this;
        });

        TextColumn::macro('rate', function (string | Closure | null $computation = null): static {
            $this->formatStateUsing(static function (TextColumn $column, $state) use ($computation): ?string {
                $computation = $column->evaluate($computation);

                return rateFormat(state: $state, computation: $computation);
            });

            return $this;
        });

        Field::macro('softRequired', function (): static {
            $this
                ->required()
                ->markAsRequired(false);

            return $this;
        });

        Money::macro('swapAmountFor', function ($newCurrency) {
            $oldCurrency = $this->currency->getCurrency();
            $balanceInSubunits = $this->getAmount();

            $oldCurrencySubunit = currency($oldCurrency)->getSubunit();
            $newCurrencySubunit = currency($newCurrency)->getSubunit();

            $balanceInMajorUnits = $balanceInSubunits / $oldCurrencySubunit;

            $oldRate = currency($oldCurrency)->getRate();
            $newRate = currency($newCurrency)->getRate();

            $ratio = $newRate / $oldRate;
            $convertedBalanceInMajorUnits = $balanceInMajorUnits * $ratio;

            $roundedConvertedBalanceInMajorUnits = round($convertedBalanceInMajorUnits, currency($newCurrency)->getPrecision());

            $convertedBalanceInSubunits = $roundedConvertedBalanceInMajorUnits * $newCurrencySubunit;

            return (int) round($convertedBalanceInSubunits);
        });

        Money::macro('formatWithCode', function (bool $codeBefore = false) {
            $formatted = $this->format();

            $currencyCode = $this->currency->getCurrency();

            if ($currencyCode === CurrencyAccessor::getDefaultCurrency()) {
                return $formatted;
            }

            if ($codeBefore) {
                return $currencyCode . ' ' . $formatted;
            }

            return $formatted . ' ' . $currencyCode;
        });

        Currency::macro('getEntity', function () {
            $currencyCode = $this->getCurrency();

            $entity = config("money.currencies.{$currencyCode}.entity");

            return $entity ?? $currencyCode;
        });

        Currency::macro('getCodePrefix', function () {
            if ($this->isSymbolFirst()) {
                return '';
            }

            return ' ' . $this->getCurrency();
        });

        Currency::macro('getCodeSuffix', function () {
            if ($this->isSymbolFirst()) {
                return ' ' . $this->getCurrency();
            }

            return '';
        });

        Carbon::macro('toDefaultDateFormat', function () {
            return $this->format(DateFormat::DEFAULT);
        });
    }
}
