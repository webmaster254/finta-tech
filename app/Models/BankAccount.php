<?php

namespace App\Models;

use App\Models\Branch;
use App\Models\Transaction;
use App\Concerns\HasDefault;
use App\Models\JournalEntries;
use Filament\Facades\Filament;
use App\Concerns\CurrentBranch;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use App\Enums\Banking\BankAccountType;
use App\Observers\BankAccountObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;

#[ObservedBy(BankAccountObserver::class)]
class BankAccount extends Model
{
    use HasFactory;
    use HasDefault;
    use CurrentBranch;
    protected $table = 'bank_accounts';
    protected $fillable = [
        'chart_of_account_id',
        'bank_holder_name',
        'name',
        'type',
        'mobile',
        'account_number',
        'branch_name',
        'opening_balance',
        'balance',
        'address',
        'enabled',
        'branch_id',
    ];

    protected $casts = [
        'type' => BankAccountType::class,
        'enabled' => 'boolean',
    ];

    protected $appends = [
        'mask',
    ];


    protected static function booted(): void
    {
        static::creating(static function ($model) {
            $model->branch_id = Filament::getTenant()->id;
        });

        static::observe(BankAccountObserver::class);
    }


    public function chartOfAccount(): MorphOne
    {
        return $this->MorphOne(ChartOfAccount::class,'accountable');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'bank_account_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class,'branch_id');
    }

    public function getTenants(Panel $panel): Collection
    {
        return $this->branch;
    }

    protected function mask(): Attribute
    {
        return Attribute::get(static function (mixed $value, array $attributes): ?string {
            return $attributes['account_number'] ? '•••• ' . substr($attributes['account_number'], -4) : null;
        });
    }

    public function addFunds($record,$data)
    {

        $journal_transaction = new Transaction();
        $journal_transaction->branch_id = $record->branch_id;
       $journal_transaction->account_id = $bankAccount->chart_of_account_id;
        $journal_transaction->type = 'journal';
        $journal_transaction->bank_account_id= $this->id;
        $journal_transaction->reviewed = 1;
        $journal_transaction->description = 'Fund Deposit';
        $journal_transaction->amount= $data['amount'];
        $journal_transaction->posted_at = date("Y-m-d");
        $journal_transaction->save();
        $journal_transaction_id = $journal_transaction->id;


        $journal_entry = new JournalEntries();
        $journal_entry->transaction_id = $journal_transaction_id;
        $journal_entry->type = 'credit';
        $journal_entry->branch_id = $record->branch_id;
        $journal_entry->amount = $data['amount'];
        $journal_entry->description = 'fund Deposit';
        $journal_entry->chart_of_account_id = $record->chart_of_account_id;
        $journal_entry->save();

    }


}
