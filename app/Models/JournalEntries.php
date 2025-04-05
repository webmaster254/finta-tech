<?php

namespace App\Models;

use App\Models\Transaction;
use App\Models\ChartOfAccount;
use Filament\Facades\Filament;
use App\Enums\JournalEntryType;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Collections\Accounting\JournalEntryCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class JournalEntries extends Model
{
    use HasFactory;

    protected $table = 'journal_entries2';

    protected $fillable = [
        'branch_id',
        'chart_of_account_id',
        'transaction_id',
        'type',
        'amount',
        'description',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'type' => JournalEntryType::class,

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
    }



    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'chart_of_account_id');
    }


    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->account()->where('accountable_type', BankAccount::class);
    }

    public function isUncategorized(): bool
    {
        return $this->account->isUncategorized();
    }

    // protected static function newFactory(): Factory
    // {
    //     return JournalEntryFactory::new();
    // }

    public function newCollection(array $models = []): JournalEntryCollection
    {
        return new JournalEntryCollection($models);
    }
}
