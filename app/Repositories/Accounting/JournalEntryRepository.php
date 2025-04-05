<?php

namespace App\Repositories\Accounting;

use App\Models\ChartOfAccount;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class JournalEntryRepository
{
    public function sumAmounts(ChartOfAccount $account, string $type, ?string $startDate = null, ?string $endDate = null): int
    {
        $query = $account->journalEntries()->where('type', $type);

        if ($startDate && $endDate) {
            $endOfDay = Carbon::parse($endDate)->endOfDay();
            $query->whereHas('transaction', static function (Builder $query) use ($startDate, $endOfDay) {
                $query->whereBetween('posted_at', [$startDate, $endOfDay]);
            });
        } elseif ($startDate) {
            $query->whereHas('transaction', static function (Builder $query) use ($startDate) {
                $query->where('posted_at', '<=', $startDate);
            });
        }

        return $query->sum('amount');
    }

    public function sumDebitAmounts(ChartOfAccount $account, string $startDate, ?string $endDate = null): int
    {
        return $this->sumAmounts($account, 'debit', $startDate, $endDate);
    }

    public function sumCreditAmounts(ChartOfAccount $account, string $startDate, ?string $endDate = null): int
    {
        return $this->sumAmounts($account, 'credit', $startDate, $endDate);
    }
}
