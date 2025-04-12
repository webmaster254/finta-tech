<?php

namespace App\Models;

use App\Models\Branch;
use App\Models\Client;
use App\Models\Loan\Loan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientAccountTransaction extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'client_account_id',
        'branch_id',
        'loan_id',
        'transaction_type',
        'transaction_direction',
        'amount',
        'balance_before',
        'balance_after',
        'currency_code',
        'payment_method',
        'reference_number',
        'receipt_number',
        'transaction_id',
        'description',
        'posted_by',
        'status',
        'metadata',
    ];
    
    protected $casts = [
        'amount' => 'float',
        'balance_before' => 'float',
        'balance_after' => 'float',
        'metadata' => 'json',
    ];
    
    /**
     * Get the client account that owns the transaction.
     */
    public function clientAccount(): BelongsTo
    {
        return $this->belongsTo(ClientAccount::class);
    }
    
    /**
     * Get the branch that the transaction belongs to.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
    
    /**
     * Get the loan associated with the transaction, if any.
     */
    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }
    
    /**
     * Get the user who created the transaction.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }
    
    /**
     * Get the user who approved the transaction.
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }
    
    /**
     * Scope a query to only include transactions of a specific type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('transaction_type', $type);
    }
    
    /**
     * Scope a query to only include deposits.
     */
    public function scopeDeposits($query)
    {
        return $query->where('transaction_type', 'deposit');
    }
    
    /**
     * Scope a query to only include withdrawals.
     */
    public function scopeWithdrawals($query)
    {
        return $query->where('transaction_type', 'withdrawal');
    }
    
    /**
     * Scope a query to only include loan disbursements.
     */
    public function scopeLoanDisbursements($query)
    {
        return $query->where('transaction_type', 'loan_disbursement');
    }
    
    /**
     * Scope a query to only include loan repayments.
     */
    public function scopeLoanRepayments($query)
    {
        return $query->where('transaction_type', 'loan_repayment');
    }
    
    /**
     * Scope a query to only include fees.
     */
    public function scopeFees($query)
    {
        return $query->where('transaction_type', 'fee');
    }
    
    /**
     * Determine if the transaction is a credit (increases balance).
     */
    public function isCredit(): bool
    {
        return $this->transaction_direction === 'credit';
    }
    
    /**
     * Determine if the transaction is a debit (decreases balance).
     */
    public function isDebit(): bool
    {
        return $this->transaction_direction === 'debit';
    }
    
    /**
     * Get the transaction amount with the appropriate sign.
     */
    public function getSignedAmountAttribute(): float
    {
        return $this->isCredit() ? $this->amount : -$this->amount;
    }
    
    /**
     * Scope a query to only include credit transactions.
     */
    public function scopeCredits($query)
    {
        return $query->where('transaction_direction', 'credit');
    }
    
    /**
     * Scope a query to only include debit transactions.
     */
    public function scopeDebits($query)
    {
        return $query->where('transaction_direction', 'debit');
    }
}
