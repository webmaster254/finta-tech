<?php

namespace App\Enums;

use App\Enums\Concerns\ParsesEnum;
use Filament\Support\Contracts\HasLabel;

enum TransactionType: string implements HasLabel
{
    use ParsesEnum;

    case Deposit = 'deposit';
    case Withdrawal = 'withdrawal';
    case Journal = 'journal';

    public function getLabel(): ?string
    {
        return $this->name;
    }

    public function isDeposit(): bool
    {
        return $this === self::Deposit;
    }

    public function isWithdrawal(): bool
    {
        return $this === self::Withdrawal;
    }

    public function isJournal(): bool
    {
        return $this === self::Journal;
    }
}
