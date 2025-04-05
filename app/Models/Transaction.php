<?php

namespace App\Models;

use Filament\Panel;
use App\Models\Branch;
use App\Models\Loan\Loan;
use App\Models\BankAccount;
use App\Models\JournalEntry;
use App\Enums\TransactionType;
use App\Models\ChartOfAccount;
use App\Models\JournalEntries;
use Filament\Facades\Filament;
use Illuminate\Support\Collection;
use App\Casts\TransactionAmountCast;
use Illuminate\Support\Facades\Auth;
use App\Observers\TransactionObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;

#[ObservedBy(TransactionObserver::class)]
class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'loan_id',
        'account_id', // Account from Chart of Accounts (Income/Expense accounts)
        'bank_account_id', // Cash/Bank Account
        'type', // 'deposit', 'withdrawal', 'journal',
        'description',
        'notes',
        'reference',
        'amount',
        'pending',
        'reviewed',
        'posted_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'type' => TransactionType::class,
        'pending' => 'boolean',
        'reviewed' => 'boolean',
        'posted_at' => 'datetime',
    ];


    protected static function booted(): void
    {
        static::creating(static function ($model) {
            //$model->branch_id = Filament::getTenant()->id;
            $auth = Auth::id();
            $model->created_by = $auth;
            $model->updated_by = $auth;
        });

        static::updating(static function ($model) {
            $auth = Auth::id();
            $model->updated_by = $auth;
        });

        static::observe(TransactionObserver::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class,'branch_id');
    }

    public function getTenants(Panel $panel): Collection
    {
        return $this->branch;
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }



    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntries::class, 'transaction_id');
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class, 'loan_id');
    }

    public function isUncategorized(): bool
    {
        return $this->journalEntries->filter(fn (JournalEntries $entry) => $entry->account !== null && $entry->account->isUncategorized())->isNotEmpty();
    }

    public function updateAmountIfBalanced(): void
    {
        if ($this->journalEntries->areBalanced() && $this->journalEntries->sumDebits()->formatSimple() !== $this->getAttributeValue('amount')) {
            $this->setAttribute('amount', $this->journalEntries->sumDebits()->formatSimple());
            $this->save();
        }
    }

    // protected static function newFactory(): Factory
    // {
    //     return TransactionFactory::new();
    // }
}
