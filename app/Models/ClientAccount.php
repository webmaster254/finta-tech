<?php

namespace App\Models;

use App\Models\User;
use App\Models\Branch;
use App\Models\Client;
use App\Models\ClientAccount;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ClientAccount extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'client_id',
        'branch_id',
        'account_number',
        'balance',
        'available_balance',
        'hold_amount',
        'currency_code',
        'status',
        'activated_at',
        'closed_at',
        'created_by_id',
        'closed_by_id',
        'notes',
    ];

    protected static function booted(): void
    {
        static::creating(static function ($model) {
           $model->branch_id = Filament::getTenant()->id;
            $auth = Auth::id();
            $model->created_by_id = $auth;
            
            // Generate account number
            $branch = Branch::find($model->branch_id);
            $branchCode = $branch->branch_code ?? '000';

            // Generate account code
            $accountCode = $branchCode . '101';
            
            // Count clients in this branch and add 1 for the new client
            $account_number = ClientAccount::where('branch_id', $model->branch_id)->count() + 1;
            
            // Format: 001101000001 (10 characters - first 3 are branch code,3 are repayment code last 6 are client count)
            $model->account_number = $accountCode . str_pad($account_number, 6, '0', STR_PAD_LEFT);
        });
    }
    
    /**
     * Get the client that owns the account.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
    
    /**
     * Get the branch that the account belongs to.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
    
    /**
     * Get the user who created the account.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }
    
    /**
     * Get the user who closed the account.
     */
    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by_id');
    }
    
    /**
     * Get the transactions for the account.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(ClientAccountTransaction::class)->orderBy('created_at', 'desc');
    }
    
    /**
     * Deposit money into the account.
     *
     * @param float $amount
     * @param string $transactionType
     * @param string|null $description
     * @param array $metadata
     * @return ClientAccountTransaction
     */
    public function deposit(
        float $amount, 
        string $transactionType = 'deposit', 
        ?string $description = null, 
        array $metadata = []
    ): ClientAccountTransaction {
        $balanceBefore = $this->balance;
        $this->balance += $amount;
        $this->available_balance += $amount;
        $this->save();
        
        return $this->recordTransaction(
            $amount,
            $transactionType,
            $balanceBefore,
            $this->balance,
            $description,
            $metadata
        );
    }
    
    /**
     * Withdraw money from the account.
     *
     * @param float $amount
     * @param string $transactionType
     * @param string|null $description
     * @param array $metadata
     * @return ClientAccountTransaction
     * @throws \Exception
     */
    public function withdraw(
        float $amount, 
        string $transactionType = 'withdrawal', 
        ?string $description = null, 
        ?string $referenceNumber = null, 
        ?string $receiptNumber = null, 
        ?string $transactionId = null, 
        array $metadata = []
    ): ClientAccountTransaction {
        if ($this->balance < $amount) {
            throw new \Exception('Insufficient funds');
        }
        
        $balanceBefore = $this->balance;
        $this->balance -= $amount;
        $this->available_balance -= $amount;
        $this->save();
        
        return $this->recordTransaction(
            -$amount,
            $transactionType,
            $balanceBefore,
            $this->balance,
            $description,
            $metadata
        );
    }
    
    /**
     * Record a transaction for the account.
     *
     * @param float $amount
     * @param string $transactionType
     * @param float $balanceBefore
     * @param float $balanceAfter
     * @param string|null $description
     * @param array $metadata
     * @return ClientAccountTransaction
     */
    protected function recordTransaction(
        float $amount,
        string $transactionType,
        float $balanceBefore,
        float $balanceAfter,
        ?string $description = null,
        array $metadata = []
    ): ClientAccountTransaction {
        // Determine if this is a credit or debit transaction
        $direction = $amount >= 0 ? 'credit' : 'debit';
        
        return $this->transactions()->create([
            'branch_id' => $this->branch_id,
            'transaction_type' => $transactionType,
            'transaction_direction' => $direction,
            'amount' => abs($amount),
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'currency_code' => $this->currency_code,
            'description' => $description,
            'posted_by' => auth()->id(),
            'metadata' => $metadata ? json_encode($metadata) : null,
        ]);
    }
    
    /**
     * Hold an amount in the account (mark part of the balance as reserved).
     *
     * @param float $amount
     * @return bool
     */
    public function holdAmount(float $amount): bool
    {
        if ($this->balance < $amount) {
            return false;
        }
        
        $this->available_balance -= $amount;
        $this->hold_amount += $amount;
        
        return $this->save();
    }
    
    /**
     * Release a held amount back to available balance.
     *
     * @param float $amount
     * @return bool
     */
    public function releaseHold(float $amount): bool
    {
        // If you have available_balance and hold_amount fields, uncomment these lines
        $amountToRelease = min($amount, $this->hold_amount);
        $this->available_balance += $amountToRelease;
        $this->hold_amount -= $amountToRelease;
        return $this->save();
    }
    
    /**
     * Process a loan repayment from the account.
     *
     * @param float $amount The amount to repay
     * @param int $loanId The loan ID being repaid
     * @param string|null $description Description of the repayment
     * @param array $metadata Additional metadata
     * @return ClientAccountTransaction The transaction record
     * @throws \Exception If insufficient funds
     */
    public function processLoanRepayment(
        float $amount,
        int $loanId,
        ?string $description = null,
        array $metadata = []
    ): ClientAccountTransaction {
        if ($this->balance < $amount) {
            throw new \Exception('Insufficient funds for loan repayment');
        }
        
        $balanceBefore = $this->balance;
        $this->balance -= $amount;
        $this->available_balance -= $amount;
        $this->save();
        
        // Add loan ID to metadata
        $metadata['loan_id'] = $loanId;
        
        // Record the transaction with detailed breakdown
        return $this->recordTransaction(
            -$amount,
            'loan_repayment',
            $balanceBefore,
            $this->balance,
            $description ?? 'Loan repayment for loan #' . $loanId,
            $metadata
        );
    }
}
