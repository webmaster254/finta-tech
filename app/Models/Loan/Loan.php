<?php

namespace App\Models\Loan;

use Carbon\Carbon;
use Filament\Panel;
use App\Models\Fund;
use App\Models\User;
use App\Models\Branch;
use App\Models\Client;
use App\Casts\MoneyCast;
use App\Models\Currency;
use App\Enums\LoanStatus;
use App\Models\ClientType;
use App\Models\BankAccount;
use App\Models\Transaction;
use App\Models\Loan\LoanFile;
use App\Models\Loan\LoanNote;
use App\Models\ChartOfAccount;
use App\Models\JournalEntries;
use Filament\Facades\Filament;
use App\Models\Loan\LoanCharge;
use App\Models\Loan\LoanProduct;
use App\Models\Loan\LoanPurpose;
use App\Models\Loan\LoanGuarantor;
use Illuminate\Support\Collection;
use App\Models\Loan\LoanCollateral;
use App\Models\Loan\LoanTransaction;
use Illuminate\Support\Facades\Auth;
use App\Models\Loan\LoanLinkedCharge;
use App\Models\Loan\LoanOfficerHistory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Loan\LoanRepaymentSchedule;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Loan\LoanTransactionProcessingStrategy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use RingleSoft\LaravelProcessApproval\Models\ProcessApproval;

class Loan extends Model
{
    use HasFactory;

    protected $casts = [
        'status' => LoanStatus::class,
        //'applied_amount' => MoneyCast::class,
       ];

       protected $fillable = [
           'client_id',
           'client_type_id',
           'created_by_id',
           'branch_id',
           'group_id',
           'currency_id',
           'loan_product_id',
           'loan_account_number',
           'loan_transaction_processing_strategy_id',
           'chart_of_account_id',
           'loan_purpose_id',
           'loan_officer_id',
           'linked_savings_id',
           'loan_disbursement_channel_id',
           'submitted_on_date',
           'submitted_by_user_id',
           'approved_on_date',
           'approved_by_user_id',
           'approved_notes',
           'expected_disbursement_date',
           'expected_first_payment_date',
           'first_payment_date',
           'expected_maturity_date',
           'disbursed_on_date',
           'disbursed_by_user_id',
           'disbursed_notes',
           'rejected_on_date',
           'rejected_by_user_id',
           'rejected_notes',
           'written_off_on_date',
           'written_off_by_user_id',
           'written_off_notes',
           'closed_on_date',
           'closed_by_user_id',
           'closed_notes',
           'rescheduled_on_date',
           'rescheduled_by_user_id',
           'rescheduled_notes',
           'withdrawn_on_date',
           'withdrawn_by_user_id',
           'withdrawn_notes',
           'account_number',
           'principal',
           'applied_amount',
           'approved_amount',
           'interest_rate',
           'decimals',
           'installment_multiple_of',
           'loan_term',
           'repayment_frequency',
           'repayment_frequency_type',
           'interest_rate_type',
           'enable_balloon_payments',
           'allow_schedule_adjustments',
           'grace_on_principal_paid',
           'grace_on_interest_paid',
           'grace_on_interest_charged',
           'allow_custom_grace_period',
           'allow_topup',
           'interest_methodology',
           'interest_recalculation',
           'amortization_method',
           'interest_calculation_period_type',
           'days_in_year',
           'days_in_month',
           'include_in_loan_cycle',
           'lock_guarantee_funds',
           'auto_allocate_overpayments',
           'allow_additional_charges',
           'auto_disburse',
           'status',
           'disbursement_charges',
           'principal_disbursed_derived',
           'principal_repaid_derived',
           'principal_written_off_derived',
           'principal_outstanding_derived',
           'interest_disbursed_derived',
           'interest_repaid_derived',
           'interest_written_off_derived',
           'interest_waived_derived',
           'interest_outstanding_derived',
           'fees_disbursed_derived',
           'fees_repaid_derived',
           'fees_written_off_derived',
           'fees_waived_derived',
           'fees_outstanding_derived',
           'penalties_disbursed_derived',
           'penalties_repaid_derived',
           'penalties_written_off_derived',
           'penalties_waived_derived',
           'penalties_outstanding_derived',
           'total_repaid_derived',
           'total_written_off_derived',
           'total_waived_derived',
           'total_outstanding_derived',
           'rts_reason',

       ];
       public $table = "loans";


       protected static function booted(): void
       {
           static::creating(static function ($model) {
              $model->branch_id = Filament::getTenant()->id;
               $auth = Auth::id();
               $model->created_by_id = $auth;
               $model->submitted_by_user_id= $auth;

                // Generate account number
           $branch = Branch::find($model->branch_id);
           $branchCode = $branch->branch_code ?? '000';
            //get product code
            $product = LoanProduct::find($model->loan_product_id);
            $productCode = $product->product_code ?? '000';

            //count loans
            $loanCount = Loan::where('branch_id', $model->branch_id)->count() + 1;

            // Format: 001201000001 (9 characters - first 3 are branch code, second 3 are product code, last 6 are loan count)
            $model->loan_account_number = $branchCode . $productCode . str_pad($loanCount, 6, '0', STR_PAD_LEFT);

           });

       }

    public function isApprovalCompleted(): bool
    {
        foreach (collect($this->approvalStatus->steps ?? []) as $index => $item) {
            if ($item['process_approval_action'] === null || $item['process_approval_id'] === null) {
                return false;
            }
        }
        return true;
    }

    public function onApprovalCompleted(ProcessApproval $approval): bool
    {
        // Write logic to be executed when the approval process is completed
        return true;
    }


public function charges()
{
    return $this->hasMany(LoanLinkedCharge::class, 'loan_id', 'id');
}

       public function client()
       {
           return $this->hasOne(Client::class, 'id', 'client_id');
       }
       public function client_type()
    {
        return $this->belongsTo(ClientType::class, 'id', 'client_type_id');
    }
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function getTenants(Panel $panel): Collection
     {
         return $this->branch;
     }

       public function loan_product()
       {
           return $this->hasOne(LoanProduct::class, 'id', 'loan_product_id');
       }

       public function currency()
       {
           return $this->belongsTo(Currency::class, 'id', 'currency_id');
       }

       public function loan_officer()
       {
           return $this->belongsTo(User::class, 'loan_officer_id');
       }

       public function loan_purpose()
        {
            return $this->belongsTo(LoanPurpose::class, 'loan_purpose_id');
        }

       public function fund()
       {
           return $this->belongsTo(ChartOfAccount::class);
       }

       public function loan_transaction_processing_strategy()
       {
           return $this->belongsTo(LoanTransactionProcessingStrategy::class);
       }

       public function submitted_by()
       {
           return $this->belongsTo(User::class,'submitted_by_user_id');
       }

       public function approved_by()
       {
           return $this->belongsTo(User::class,'approved_by_user_id');
       }

       public function disbursed_by()
       {
           return $this->belongsTo(User::class,'disbursed_by_user_id');
       }

       public function rejected_by()
       {
           return $this->belongsTo(User::class,'rejected_by_user_id');
       }

       public function written_off_by()
       {
           return $this->belongsTo(User::class, 'id', 'written_off_by_user_id');
       }

       public function closed_by()
       {
           return $this->belongsTo(User::class, 'id', 'closed_by_user_id');
       }

       public function withdrawn_by()
       {
           return $this->belongsTo(User::class, 'id', 'withdrawn_by_user_id');
       }

       public function rescheduled_by()
       {
           return $this->belongsTo(User::class, 'id', 'rescheduled_by_user_id');
       }

       public function files()
       {
           return $this->hasMany(LoanFile::class);
       }

       public function collateral()
       {
           return $this->hasMany(LoanCollateral::class);
       }

       public function notes()
       {
           return $this->hasMany(LoanNote::class, 'loan_id', 'id')->orderBy('created_at', 'desc');
       }

       public function guarantors()
       {
           return $this->hasMany(LoanGuarantor::class, 'loan_id', 'id');
       }

       public function repayment_schedules()
       {
           return $this->hasMany(LoanRepaymentSchedule::class, 'loan_id', 'id')->orderBy('due_date', 'asc');
       }

       public function transactions()
       {
           return $this->hasMany(LoanTransaction::class, 'loan_id', 'id')->orderBy('submitted_on', 'asc')->orderBy('id', 'asc');
       }

       public function journalTransactions()
       {
        return $this->belongsTo(Transaction::class);
       }

       public function saveTransaction(array $data,$record)
       {

        $loan= Loan::find($data['loan_id']);
           //update funds account




           $transaction = new LoanTransaction;
           $transaction->loan_id = $data['loan_id'];
           $transaction->amount = $data['amount'];
           $transaction->branch_id = $loan->branch_id;
           $transaction->first_name = $record->client->first_name;
           //$transaction->payment_detail_id = $data['payment_detail_id'];
           $transaction->submitted_on = $data['submitted_on'];
           //$transaction->created_by_id = Auth::id();
           $transaction->created_on = Carbon::now();
           $transaction->reference = $data['reference'];
           $transaction->name = 'Repayment';
           $transaction->account_number = $data['account_number'];
           $transaction->loan_transaction_type_id = 2;
           $transaction->credit = $data['credit'];
           $transaction->debit = 0;
           $transaction->save();

           return $transaction;
       }


       /**
        * Saves loan charges.
        *
        * @param array $data The data containing the loan charges.
        * @param mixed $record The loan record.
        * @throws Some_Exception_Class Exception description.
        * @return void
        */
       public function saveLoanCharges(array $data,$record)
       {
        $transaction = new LoanTransaction;
        $transaction->loan_id = $this->id;
        $transaction->amount = $data['amount'];
        $transaction->branch_id = $record->branch_id;
        $transaction->first_name = $record->client->first_name;
        //$transaction->payment_detail_id = $data['payment_detail_id'];
        $transaction->submitted_on = Carbon::now();
        $transaction->created_by_id = Auth::id();
        $transaction->created_on = Carbon::now();
        $transaction->reference = $data['name'];
        $transaction->name = $data['name'];
        $transaction->account_number = $record['account_number'];
        $transaction->loan_transaction_type_id = 10;
        $transaction->due_date = $repayment_schedule->due_date;
        $transaction->credit = 0;
        $transaction->debit = $data['amount'];
        $transaction->save();

       
         $loan = $record;
          $loan_linked_charge = new LoanLinkedCharge;
            $loan_linked_charge->loan_transaction_id = $transaction->id;
            $loan_linked_charge->account_number = $record['account_number'];
            $loan_linked_charge->loan_id = $this->id;
            $loan_linked_charge->loan_charge_id = $data['loan_charge_id'];
            $loan_linked_charge->loan_charge_type_id = $data['loan_charge_type_id'];
            $loan_linked_charge->loan_charge_option_id = $data['loan_charge_option_id'];
            $loan_linked_charge->name = $data['name'];
            $loan_linked_charge->amount = $data['amount'];
            $loan_linked_charge->is_penalty = $data['is_penalty'];
            $loan_linked_charge->save();

          //find schedule to apply this charge
          $repayment_schedule = $loan->repayment_schedules->where('due_date', '>=', $record->date)->where('from_date', '<=', $record->date)->first();
          if (empty($repayment_schedule)) {
              if (Carbon::parse($record->date)->lessThan($loan->first_payment_date)) {
                  $repayment_schedule = $loan->repayment_schedules->first();
              } else {
                  $repayment_schedule = $loan->repayment_schedules->last();
              }

          }
          //calculate the amount
          if ($loan_linked_charge->loan_charge_option_id == 1) {
              $amount = $loan_linked_charge->amount;
          }
          if ($loan_linked_charge->loan_charge_option_id == 2) {
              $amount = round(($loan_linked_charge->amount * ($repayment_schedule->principal - $repayment_schedule->principal_repaid_derived - $repayment_schedule->principal_written_off_derived) / 100), $loan->decimals);
          }
          if ($loan_linked_charge->loan_charge_option_id == 3) {
              $amount = round(($loan_linked_charge->amount * (($repayment_schedule->interest - $repayment_schedule->interest_repaid_derived - $repayment_schedule->interest_waived_derived - $repayment_schedule->interest_written_off_derived) + ($repayment_schedule->principal - $repayment_schedule->principal_repaid_derived - $repayment_schedule->principal_written_off_derived)) / 100), $loan->decimals);
          }
          if ($loan_linked_charge->loan_charge_option_id == 4) {
              $amount = round(($loan_linked_charge->amount * ($repayment_schedule->interest - $repayment_schedule->interest_repaid_derived - $repayment_schedule->interest_waived_derived - $repayment_schedule->interest_written_off_derived) / 100), $loan->decimals);
          }
          if ($loan_linked_charge->loan_charge_option_id == 5) {
              $amount = round(($loan_linked_charge->amount * (($loan->repayment_schedules->sum('principal')-$loan->repayment_schedules->sum('principal_repaid_derived')-$loan->repayment_schedules->sum('principal_written_off_derived'))
              + ($loan->repayment_schedules->sum('interest')-$loan->repayment_schedules->sum('interest_repaid_derived')-$loan->repayment_schedules->sum('interest_written_off_derived'))
              ) / 100), $loan->decimals);
          }
          if ($loan_linked_charge->loan_charge_option_id == 6) {
              $amount = round(($loan_linked_charge->amount * $loan->principal / 100), $loan->decimals);
          }
          if ($loan_linked_charge->loan_charge_option_id == 7) {
              $amount = round(($loan_linked_charge->amount * $loan->principal / 100), $loan->decimals);
          }
          $repayment_schedule->fees = $repayment_schedule->fees + $amount;
          $repayment_schedule->total_due =$repayment_schedule->total_due + $amount;
          $repayment_schedule->save();
          $loan_linked_charge->calculated_amount = $amount;
          
           
           

          // $loan_linked_charge->due_date = $repayment_schedule->due_date;
            //create transaction
            // $loan_transaction = new LoanTransaction();
            // $loan_transaction->created_by_id = Auth::id();
            // $loan_transaction->loan_id = $loan->id;
            // $loan_transaction->name = 'Fee Applied';
            // $loan_transaction->loan_transaction_type_id = 10;
            // $loan_transaction->submitted_on = date("Y-m-d");
            // $loan_transaction->created_on = date("Y-m-d");
            // $loan_transaction->amount = $loan_linked_charge->calculated_amount;
            // $loan_transaction->account_number = $loan->account_number;
            // $loan_transaction->due_date = $repayment_schedule->due_date;
            // $loan_transaction->debit = $loan_linked_charge->calculated_amount;
            // $loan_transaction->reversible = 1;
            // $loan_transaction->save();
            // $loan_linked_charge->loan_transaction_id = $loan_transaction->id;
            // $loan_linked_charge->save();
            
            
       }


/**
 * Changes the loan officer for a given record.
 *
 * @param array $data The data containing the new loan officer ID.
 * @param mixed $record The record to update.
 * @throws Some_Exception_Class If there is an error saving the record or updating the loan officer history.
 * @return void
 */
    public function changeLoanOfficer(array $data, $record)
    {
        $loan = $record;
        $previousLoanOfficerId = $record->loan_officer_id;
        $record->loan_officer_id = $data['loan_officer_id'];
        $record->save();

        if ($previousLoanOfficerId != $data['loan_officer_id']) {
            $previousLoanOfficer = LoanOfficerHistory::where('loan_id', $loan->id)
                ->where('loan_officer_id', $data['loan_officer_id'])
                ->where('end_date', '')
                ->first();

            if (!empty($previousLoanOfficer)) {
                $previousLoanOfficer->end_date = date("Y-m-d");
                $previousLoanOfficer->save();
            }

            $loanOfficerHistory = new LoanOfficerHistory();
            $loanOfficerHistory->loan_id = $loan->id;
            $loanOfficerHistory->created_by_id = Auth::id();
            $loanOfficerHistory->loan_officer_id = $data['loan_officer_id'];
            $loanOfficerHistory->start_date = date("Y-m-d");
            $loanOfficerHistory->save();
        }
    }
    public function getBalance($loan_id)
    {
        $balance = LoanRepaymentSchedule::where('loan_id', $loan_id)->sum('total_due');

        return $balance;
    }
    public function getAmountDue($loan_id)
    {
        $amountDue = LoanRepaymentSchedule::where('loan_id', $loan_id)
                    ->where('total_due', '>', 0)
                    ->where('due_date', '<', Carbon::now())
                    ->sum('total_due');

        return $amountDue;
    }

    public function getAmountRepaid($loanId)
    {
        $loan = LoanRepaymentSchedule::where('loan_id', $loanId)
                        ->get();
        $amountRepaid = $loan->sum('principal_repaid_derived')
                    + $loan->sum('interest_repaid_derived') + $loan->sum('fees_repaid_derived')
                    + $loan->sum('penalties_repaid_derived');

        return $amountRepaid;
    }

    public function getInterestPaid($loanId)
    {
        $loan = LoanRepaymentSchedule::where('loan_id', $loanId)
                        ->get();
        $interestPaid = $loan->sum('interest_repaid_derived');

        return $interestPaid;
    }

    public function getInterestDisbursed($loanId)
    {
        $loan = $this->where('id', $loanId)
                        ->get();
        $interestDisbursed = $loan->sum('interest_disbursed_derived');

        return $interestDisbursed;
    }


    public function getCharges($loanId)
    {
        $loan = LoanRepaymentSchedule::where('loan_id', $loanId)
                        ->get();
        $totalCharges = $loan->sum('fees') - $loan->sum('fees_repaid_derived')
                    - $loan->sum('Penalties_repaid_derived') - $loan->sum('fees_waived_derived')
                    - $loan->sum('penalties_waived_derived') - $loan->sum('fees_written_off_derived')
                    - $loan->sum('penalties_written_off_derived');

        return $totalCharges;
    }


    public function approveLoan(array $data)
    {
        $this->update([
            'status' => 'approved',
            'approved_amount' => $data['approved_amount'],
            //'approved_notes' => $data['approved_notes'],
            'approved_by_user_id' => Auth::id(),
            'approved_on_date' => $data['approved_on_date'],
            ]);
    }

    public function BulkLoanApprove(array $data)
    {
        $this->update([
            'status' => 'approved',
            'approved_amount' => $this->principal,
            'approved_notes' => $data['approved_notes'],
            'approved_by_user_id' => Auth::id(),
            'approved_on_date' => $data['approved_on_date'],
            ]);
    }


    public function unapproveLoan()
    {
        $this->update([
            'status' => 'pending',
            'approved_amount' => null,
            'approved_by_user_id' => null,
            'approved_on_date' => null,
            'approved_notes' => null,
            ]);
    }

    public function rejectLoan(array $data)
    {
        $this->update([
            'status' => 'rejected',
            'rejected_by_user_id' => Auth::id(),
            'rejected_on_date' => $data['rejected_on_date'],
            'rejected_notes' => $data['rejected_notes'],
            ]);
    }


    public function undoLoanReject()
    {
        $this->update([
            'status' => 'pending',
            'rejected_by_user_id' => null,
            'rejected_on_date' => null,
            'rejected_notes' => null,
            ]);
    }


    public function disburseLoan(array $data, $record)
    {

        //dump($data);
        $loan = $record;
        $disbursedAmount = $data['approved_amount'];
        $disbursedNotes = $data['disbursed_notes'];
        $firstPaymentDate = $data['first_payment_date'];
        $loan->status = 'active';
        $loan->disbursed_on_date = $data['disbursed_on_date'];
        $loan->disbursed_by_user_id = Auth::id();
        $loan->principal_disbursed_derived = $disbursedAmount;
        $loan->disbursed_notes = $disbursedNotes;
        $loan->first_payment_date = $firstPaymentDate;
        $loan->save();

// Deduct the amount disbursed from the funds account

        // $fundsAccount = BankAccount::find($record->fund_id);
        // $fundsAccount->balance -= $disbursedAmount;
        // $fundsAccount->save();
    }

    public function undisburseLoan($record)
    {

         // Add the amount disbursed back to the funds account
        // $fundsAccount = BankAccount::find($record->fund_id);
        // $fundsAccount->balance += $record->principal_disbursed_derived;
        // $fundsAccount->save();

        $loan = $record;
        $loan->status = 'approved';
        $loan->disbursed_on_date = null;
        $loan->disbursed_by_user_id = null;
        $loan->disbursed_notes = null;
        $loan->disbursed_notes = null;
        $loan->principal_disbursed_derived = 0;
        $loan->first_payment_date = null;
        $loan->save();


    }

    public function closeLoan()
    {
        $this->update([
            'status' => 'closed',
            'closed_by_user_id' => Auth::id(),
            'closed_on_date' => Carbon::now(),
            'closed_notes' => 'Loan has been closed',
            ]);
    }

    public function activateLoan()    {
        $this->update([
            'status' => 'active',
            'closed_by_user_id' => null,
            'closed_on_date' => null,
            'closed_notes' => null,
            ]);
    }
   public function rescheduleLoan(array $data)
    {
        $notes = $data['rescheduled_notes'];
        $this->update([
            'status' => 'rescheduled',
            'rescheduled_by_user_id' => Auth::id(),
            'rescheduled_on_date' => Carbon::now(),
            'rescheduled_notes' => $notes,
            ]);
    }

public function getStatus($loanId)
{
    $loan = Loan::find($loanId);
    $status = $loan->status;

    return  $status->name;
}

public function getDaysInArrears($loanId)
{
    $lastRepayment = LoanRepaymentSchedule::where('loan_id', $loanId)
                            ->where('due_date', '<', Carbon::now())
                            ->where('total_due', '>', 0)
                            ->where('paid_by_date', '=', null)
                            ->orderBy('due_date', 'asc')
                            ->value('due_date');

    $today = Carbon::now();
    $daysInArrears = $today->diffInDays($lastRepayment);

    return $daysInArrears;
}

}
