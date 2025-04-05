<?php

namespace App\Jobs;

use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use App\Models\JournalEntries;
use Filament\Facades\Filament;
use App\Models\Loan\LoanTransaction;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class UpdateTransactionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $loan;
    public  $loanTransaction;

    /**
     * Create a new job instance.
     */
    public function __construct( $loan, $loanTransaction)
    {
        //
        $this->loan = $loan;
        $this->loanTransaction = $loanTransaction;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $loan = $this->loan;
        $data=$this->loanTransaction;
        $repayment_schedules = $loan->repayment_schedules;

        $journal_transaction = new Transaction();
        $journal_transaction->loan_id = $loan->id;
        $journal_transaction->branch_id = $loan->branch_id;
        $journal_transaction->account_id = $loan->fund_id;
        $journal_transaction->type = 'journal';
        $journal_transaction->reviewed = 1;
        $journal_transaction->description = 'Loan repayment';
        $journal_transaction->amount= $data['amount'];
        $journal_transaction->posted_at = date("Y-m-d");
        $journal_transaction->save();
        $journal_transaction_id = $journal_transaction->id;

        //$branchId = Filament::getTenant()->id;
        $original_transactions = LoanTransaction::where('loan_id', $loan->id)->whereIn('loan_transaction_type_id', [2, 6, 8])->orderBy('submitted_on', 'asc')->orderBy('id', 'asc')->get();
        $transactions = LoanTransaction::where('loan_id', $loan->id)->whereIn('loan_transaction_type_id', [2, 6, 8])->orderBy('submitted_on', 'asc')->orderBy('id', 'asc')->get();
        //set paid derived to zero in repayment schedules

        foreach ($repayment_schedules as &$repayment_schedule) {
            $repayment_schedule->total_due = ($repayment_schedule->principal - $repayment_schedule->principal_written_off_derived - $repayment_schedule->principal_repaid_derived) + ($repayment_schedule->interest - $repayment_schedule->interest_written_off_derived - $repayment_schedule->interest_repaid_derived - $repayment_schedule->interest_waived_derived) + ($repayment_schedule->fees - $repayment_schedule->fees_written_off_derived - $repayment_schedule->fees_repaid_derived - $repayment_schedule->fees_waived_derived) + ($repayment_schedule->penalties - $repayment_schedule->penalties_written_off_derived - $repayment_schedule->penalties_repaid_derived - $repayment_schedule->penalties_waived_derived);
            $repayment_schedule->principal_repaid_derived = 0;
            $repayment_schedule->fees_repaid_derived = 0;
            $repayment_schedule->interest_repaid_derived = 0;
            $repayment_schedule->penalties_repaid_derived = 0;

            $repayment_schedule->save();
        }

        foreach ($transactions as &$transaction) {
            $amount = $transaction->amount;
            $principal_repaid_derived = 0;
            $interest_repaid_derived = 0;
            $fees_repaid_derived = 0;
            $penalties_repaid_derived = 0;
            //loop through repayment schedules
            foreach ($repayment_schedules as &$repayment_schedule) {
                if ($amount <= 0) {
                    break;
                }
                $principal = $repayment_schedule->principal - $repayment_schedule->principal_written_off_derived - $repayment_schedule->principal_repaid_derived;
                $interest = $repayment_schedule->interest - $repayment_schedule->interest_written_off_derived - $repayment_schedule->interest_repaid_derived - $repayment_schedule->interest_waived_derived;
                $fees = $repayment_schedule->fees - $repayment_schedule->fees_written_off_derived - $repayment_schedule->fees_repaid_derived - $repayment_schedule->fees_waived_derived;
                $penalties = $repayment_schedule->penalties - $repayment_schedule->penalties_written_off_derived - $repayment_schedule->penalties_repaid_derived - $repayment_schedule->penalties_waived_derived;
                $due = $principal + $interest + $fees + $penalties;
                if ($due <= 0) {
                    continue;
                }

                //allocate the payment
                if ($loan->loan_transaction_processing_strategy_id == 1) {
                    //penalties
                    if ($amount >= $penalties && $penalties > 0) {
                        $repayment_schedule->penalties_repaid_derived = $repayment_schedule->penalties_repaid_derived + $penalties;
                        $penalties_repaid_derived = $penalties_repaid_derived + $penalties;
                        $amount = $amount - $penalties;
                    } elseif ($amount < $penalties && $penalties > 0) {
                        $repayment_schedule->penalties_repaid_derived = $repayment_schedule->penalties_repaid_derived + $amount;
                        $penalties_repaid_derived = $penalties_repaid_derived + $amount;
                        $amount = 0;
                    }
                    //fees
                    if ($amount >= $fees && $fees > 0) {
                        $repayment_schedule->fees_repaid_derived = $repayment_schedule->fees_repaid_derived + $fees;
                        $fees_repaid_derived = $fees_repaid_derived + $fees;
                        $amount = $amount - $fees;
                    } elseif ($amount < $fees && $fees > 0) {
                        $repayment_schedule->fees_repaid_derived = $repayment_schedule->fees_repaid_derived + $amount;
                        $fees_repaid_derived = $fees_repaid_derived + $amount;
                        $amount = 0;
                    }
                    //interest
                    if ($amount >= $interest && $interest > 0) {
                        $repayment_schedule->interest_repaid_derived = $repayment_schedule->interest_repaid_derived + $interest;
                        $interest_repaid_derived = $interest_repaid_derived + $interest;
                        $amount = $amount - $interest;
                    } elseif ($amount < $interest && $interest > 0) {
                        $repayment_schedule->interest_repaid_derived = $repayment_schedule->interest_repaid_derived + $amount;
                        $interest_repaid_derived = $interest_repaid_derived + $amount;
                        $amount = 0;
                    }
                    //principal
                    if ($amount >= $principal && $principal > 0) {
                        $repayment_schedule->principal_repaid_derived = $repayment_schedule->principal_repaid_derived + $principal;
                        $principal_repaid_derived = $principal_repaid_derived + $principal;
                        $amount = $amount - $principal;

                    } elseif ($amount < $principal && $principal > 0) {
                        $repayment_schedule->principal_repaid_derived = $repayment_schedule->principal_repaid_derived + $amount;
                        $principal_repaid_derived = $principal_repaid_derived + $amount;
                        $amount = 0;
                    }

                }
                if ($loan->loan_transaction_processing_strategy_id == 2) {

                    //principal
                    if ($amount >= $principal && $principal > 0) {
                        $repayment_schedule->principal_repaid_derived = $repayment_schedule->principal_repaid_derived + $principal;
                        $principal_repaid_derived = $principal_repaid_derived + $principal;
                        $amount = $amount - $principal;

                    } elseif ($amount < $principal && $principal > 0) {
                        $repayment_schedule->principal_repaid_derived = $repayment_schedule->principal_repaid_derived + $amount;
                        $principal_repaid_derived = $principal_repaid_derived + $amount;
                        $amount = 0;
                    }
                    //interest
                    if ($amount >= $interest && $interest > 0) {
                        $repayment_schedule->interest_repaid_derived = $repayment_schedule->interest_repaid_derived + $interest;
                        $interest_repaid_derived = $interest_repaid_derived + $interest;
                        $amount = $amount - $interest;
                    } elseif ($amount < $interest && $interest > 0) {
                        $repayment_schedule->interest_repaid_derived = $repayment_schedule->interest_repaid_derived + $amount;
                        $interest_repaid_derived = $interest_repaid_derived + $amount;
                        $amount = 0;
                    }
                    //penalties
                    if ($amount >= $penalties && $penalties > 0) {
                        $repayment_schedule->penalties_repaid_derived = $repayment_schedule->penalties_repaid_derived + $penalties;
                        $penalties_repaid_derived = $penalties_repaid_derived + $penalties;
                        $amount = $amount - $penalties;
                    } elseif ($amount < $penalties && $penalties > 0) {
                        $repayment_schedule->penalties_repaid_derived = $repayment_schedule->penalties_repaid_derived + $amount;
                        $penalties_repaid_derived = $penalties_repaid_derived + $amount;
                        $amount = 0;
                    }
                    //fees
                    if ($amount >= $fees && $fees > 0) {
                        $repayment_schedule->fees_repaid_derived = $repayment_schedule->fees_repaid_derived + $fees;
                        $fees_repaid_derived = $fees_repaid_derived + $fees;
                        $amount = $amount - $fees;
                    } elseif ($amount < $fees && $fees > 0) {
                        $repayment_schedule->fees_repaid_derived = $repayment_schedule->fees_repaid_derived + $amount;
                        $fees_repaid_derived = $fees_repaid_derived + $amount;
                        $amount = 0;
                    }

                }
                if ($loan->loan_transaction_processing_strategy_id == 3) {

                    //interest
                    if ($amount >= $interest && $interest > 0) {
                        $repayment_schedule->interest_repaid_derived = $repayment_schedule->interest_repaid_derived + $interest;
                        $interest_repaid_derived = $interest_repaid_derived + $interest;
                        $amount = $amount - $interest;
                    } elseif ($amount < $interest && $interest > 0) {
                        $repayment_schedule->interest_repaid_derived = $repayment_schedule->interest_repaid_derived + $amount;
                        $interest_repaid_derived = $interest_repaid_derived + $amount;
                        $amount = 0;
                    }
                    //principal
                    if ($amount >= $principal && $principal > 0) {
                        $repayment_schedule->principal_repaid_derived = $repayment_schedule->principal_repaid_derived + $principal;
                        $principal_repaid_derived = $principal_repaid_derived + $principal;
                        $amount = $amount - $principal;

                    } elseif ($amount < $principal && $principal > 0) {
                        $repayment_schedule->principal_repaid_derived = $repayment_schedule->principal_repaid_derived + $amount;
                        $principal_repaid_derived = $principal_repaid_derived + $amount;
                        $amount = 0;
                    }
                    //penalties
                    if ($amount >= $penalties && $penalties > 0) {
                        $repayment_schedule->penalties_repaid_derived = $repayment_schedule->penalties_repaid_derived + $penalties;
                        $penalties_repaid_derived = $penalties_repaid_derived + $penalties;
                        $amount = $amount - $penalties;
                    } elseif ($amount < $penalties && $penalties > 0) {
                        $repayment_schedule->penalties_repaid_derived = $repayment_schedule->penalties_repaid_derived + $amount;
                        $penalties_repaid_derived = $penalties_repaid_derived + $amount;
                        $amount = 0;
                    }
                    //fees
                    if ($amount >= $fees && $fees > 0) {
                        $repayment_schedule->fees_repaid_derived = $repayment_schedule->fees_repaid_derived + $fees;
                        $fees_repaid_derived = $fees_repaid_derived + $fees;
                        $amount = $amount - $fees;
                    } elseif ($amount < $fees && $fees > 0) {
                        $repayment_schedule->fees_repaid_derived = $repayment_schedule->fees_repaid_derived + $amount;
                        $fees_repaid_derived = $fees_repaid_derived + $amount;
                        $amount = 0;
                    }
                }
                if (($repayment_schedule->principal - $repayment_schedule->principal_written_off_derived - $repayment_schedule->principal_repaid_derived) + ($repayment_schedule->interest - $repayment_schedule->interest_written_off_derived - $repayment_schedule->interest_repaid_derived - $repayment_schedule->interest_waived_derived) + ($repayment_schedule->fees - $repayment_schedule->fees_written_off_derived - $repayment_schedule->fees_repaid_derived - $repayment_schedule->fees_waived_derived) + ($repayment_schedule->penalties - $repayment_schedule->penalties_written_off_derived - $repayment_schedule->penalties_repaid_derived - $repayment_schedule->penalties_waived_derived) <= 0) {
                    $repayment_schedule->paid_by_date = $transaction->submitted_on;
                }
                $repayment_schedule->total_due=($repayment_schedule->principal - $repayment_schedule->principal_written_off_derived - $repayment_schedule->principal_repaid_derived)+($repayment_schedule->interest - $repayment_schedule->interest_written_off_derived - $repayment_schedule->interest_repaid_derived - $repayment_schedule->interest_waived_derived)+($repayment_schedule->fees - $repayment_schedule->fees_written_off_derived - $repayment_schedule->fees_repaid_derived - $repayment_schedule->fees_waived_derived)+($repayment_schedule->penalties - $repayment_schedule->penalties_written_off_derived - $repayment_schedule->penalties_repaid_derived - $repayment_schedule->penalties_waived_derived);
                $repayment_schedule->save();
                if ($amount <= 0) {
                    break;
                }
            }
            $transaction->principal_repaid_derived = $principal_repaid_derived;
            $transaction->interest_repaid_derived = $interest_repaid_derived;
            $transaction->fees_repaid_derived = $fees_repaid_derived;
            $transaction->penalties_repaid_derived = $penalties_repaid_derived;
            $transaction->save();

            if ($amount <= 0) {
                continue;
            }
        }


        //echo json_encode($transactions);
        $unchanged_transactions = [];
        foreach ($original_transactions as $key) {
            array_push($unchanged_transactions, [
                $key->id,
                $key->loan_id,
                $key->amount,
                $key->principal_repaid_derived,
                $key->interest_repaid_derived,
                $key->fees_repaid_derived,
                $key->penalties_repaid_derived,
                $key->submitted_on,
            ]);
        }

        $changed_transactions = [];
        $count = 1;
        foreach ($transactions as $key) {
            array_push($changed_transactions, [
                $key->id,
                $key->loan_id,
                $key->amount,
                $key->principal_repaid_derived,
                $key->interest_repaid_derived,
                $key->fees_repaid_derived,
                $key->penalties_repaid_derived,
                $key->submitted_on,
            ]);
            $count++;
        }
        function compare_multi_dimensional_array($array1, $array2) {
            $result = array();
            foreach ($array1 as $key => $value) {
                if (!is_array($array2) || !array_key_exists($key, $array2)) {
                    $result[$key] = $value;
                    continue;
                }
                if (is_array($value)) {
                    $recursiveArrayDiff = compare_multi_dimensional_array($value, $array2[$key]);
                    if (!empty($recursiveArrayDiff)) {
                        $result[$key] = $recursiveArrayDiff;
                    }
                    continue;
                }
                if ($value !== $array2[$key]) {
                    $result[$key] = $value;
                }
            }
            return $result;
        }

        $transactions_to_be_updated = compare_multi_dimensional_array($changed_transactions, $unchanged_transactions);
        foreach ($transactions_to_be_updated as $key => $value) {
            $transaction = $unchanged_transactions[$key];

        //check if accounting is enabled
        if ($loan->loan_product->accounting_rule == "cash" || $loan->loan_product->accounting_rule == "accrual_periodic" || $loan->loan_product->accounting_rule == "accrual_upfront") {
            //reverse all journal entries linked to this transactions

            //principal repaid
            if ($transaction[3]> 0) {
                //debit account
                $journal_entry = new JournalEntries();
                $journal_entry->transaction_id = $journal_transaction_id;
                $journal_entry->type = 'debit';
                $journal_entry->branch_id = $loan->branch_id;
                $journal_entry->amount = $transaction[3];
                $journal_entry->description = 'principal repayment';
                $journal_entry->chart_of_account_id = $loan->loan_product->fund_source_chart_of_account_id;
                $journal_entry->save();

                //credit account
                $journal_entry = new JournalEntries();
                $journal_entry->transaction_id = $journal_transaction_id;
                $journal_entry->type = 'credit';
                $journal_entry->branch_id = $loan->branch_id;
                $journal_entry->amount = $transaction[3];
                $journal_entry->description = 'principal repayment';
                $journal_entry->chart_of_account_id = $loan->loan_product->loan_portfolio_chart_of_account_id;
                $journal_entry->save();
            }
            //interest repaid
            if ($transaction[4] > 0) {
                //credit account
                $journal_entry = new JournalEntries();
                $journal_entry->transaction_id = $journal_transaction_id;
                $journal_entry->type = 'credit';
                $journal_entry->branch_id = $loan->branch_id;
                $journal_entry->amount = $transaction[4];
                $journal_entry->description = 'interest repayment';
                $journal_entry->chart_of_account_id = $loan->loan_product->income_from_interest_chart_of_account_id;
                $journal_entry->save();

            }
            //fees repaid
            if ($transaction[5] > 0) {
                //credit account
                $journal_entry = new JournalEntries();
                $journal_entry->transaction_id = $journal_transaction_id;
                $journal_entry->type = 'credit';
                $journal_entry->branch_id = $loan->branch_id;
                $journal_entry->amount = $transaction[5];
                $journal_entry->description = 'fees repayment';
                $journal_entry->chart_of_account_id = $loan->loan_product->income_from_fees_chart_of_account_id;
                $journal_entry->save();

            }
            //penalties repaid
            if ($transaction[6]> 0) {
                //credit account
                $journal_entry = new JournalEntries();
                $journal_entry->transaction_id = $journal_transaction_id;
                $journal_entry->type = 'credit';
                $journal_entry->branch_id = $loan->branch_id;
                $journal_entry->amount = $transaction[6];
                $journal_entry->description = 'fees repayment';
                $journal_entry->chart_of_account_id = $loan->loan_product->income_from_penalties_chart_of_account_id;
                $journal_entry->save();

            }
        }

    }
    }

}
