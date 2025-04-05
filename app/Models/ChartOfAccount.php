<?php

namespace App\Models;

use App\Models\Branch;
use App\Models\Currency;
use App\Facades\Accounting;
use App\Models\Transaction;
use App\Models\ChartOfAccount;
use App\Models\JournalEntries;
use Filament\Facades\Filament;
use Illuminate\Support\Carbon;
use App\Enums\ChartAccountType;
use Illuminate\Support\Collection;
use App\Enums\ChartAccountCategory;
use App\Models\ChartOfAccountSubtype;
use Illuminate\Database\Eloquent\Model;
use App\Observers\ChartOfAccountObserver;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[ObservedBy(ChartOfAccountObserver::class)]
class ChartOfAccount extends Model
{
    use HasFactory;



    protected $fillable = [
        'parent_id',
        'subtype_id',
        'name',
        'gl_code',
        'account_type',
        'category',
        'allow_manual',
        'active',
        'description',
        'currency_code',
        'branch_id',
        'accountable_id',
        'default',
        'accountable_type',
    ];

    protected $casts = [
        'category' => ChartAccountCategory::class,
        'account_type' => ChartAccountType::class,

       ];

       protected static function booted(): void
       {
           static::creating(static function ($model) {
             $model->branch_id = Filament::getTenant()->id ?? 1;
           });

           static::observe(ChartOfAccountObserver::class);

       }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class,'branch_id');
    }

    public function getTenants(Panel $panel): Collection
    {
        return $this->branch;
    }
    public function subtype(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccountSubtype::class, 'subtype_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(__CLASS__, 'parent_id')
            ->whereKeyNot($this->getKey());
    }

    public function children(): HasMany
    {
        return $this->hasMany(__CLASS__, 'parent_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_code', 'symbol');
    }

    public function accountable(): MorphTo
    {
        return $this->morphTo();
    }



    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'account_id');
    }


    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntries::class, 'chart_of_account_id');
    }

    public function getLastTransactionDate(): ?string
    {
        $lastJournalEntryTransaction = $this->journalEntries()
            ->join('transactions', 'journal_entries2.transaction_id', '=', 'transactions.id')
            ->max('transactions.posted_at');

        if ($lastJournalEntryTransaction) {
            return Carbon::parse($lastJournalEntryTransaction)->format('F j, Y');
        }

        return null;
    }

    public function isUncategorized(): bool
    {
        return $this->account_type->isUncategorized();
    }

    protected function endingBalance(): Attribute
    {
        return Attribute::get(function () {
            $branch = Filament::getTenant();
            $fiscalYearStart = $branch->fiscalYearStartDate();
            $fiscalYearEnd = $branch->fiscalYearEndDate();

            return Accounting::getEndingBalance($this, $fiscalYearStart, $fiscalYearEnd);
        });
    }

}
