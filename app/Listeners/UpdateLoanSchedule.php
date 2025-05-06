<?php

namespace App\Listeners;

use App\Models\PaymentType;
use App\Models\Transaction;
use App\Models\JournalEntry;
use App\Events\LoanDisbursed;
use App\Models\ClientAccount;
use App\Models\JournalEntries;
use Illuminate\Support\Carbon;
use App\Models\Loan\LoanTransaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\Loan\LoanRepaymentSchedule;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateLoanSchedule
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    /**
     * Determine the period interest rate based on the default interest rate and frequency types
     * 
     * @param float $default_interest_rate The interest rate as a percentage (e.g., 1 for 1%)
     * @param string $repayment_frequency_type The frequency of repayments (days, weeks, months, years)
     * @param string $interest_rate_type How the interest rate is expressed (day, week, month, year)
     * @param int $repayment_frequency Number of periods in each payment cycle
     * @param int $days_in_year Number of days in a year
     * @param int $days_in_month Number of days in a month
     * @param int $weeks_in_year Number of weeks in a year
     * @param int $weeks_in_month Number of weeks in a month
     * @return float The calculated period interest rate as a decimal
     */
    public function determine_period_interest_rate($default_interest_rate, $repayment_frequency_type, $interest_rate_type, $repayment_frequency = 1, $days_in_year = 365, $days_in_month = 30, $weeks_in_year = 52, $weeks_in_month = 4)
    {
        $interest_rate = $default_interest_rate;
        
        // For daily charging products (e.g., 1% daily)
        if ($interest_rate_type == 'day') {
            // If interest is charged daily but repayment is not daily, convert accordingly
            if ($repayment_frequency_type == 'weeks') {
                $interest_rate = $interest_rate * 7; // Daily rate * 7 days
            } elseif ($repayment_frequency_type == 'months') {
                $interest_rate = $interest_rate * $days_in_month; // Daily rate * days in month
            } elseif ($repayment_frequency_type == 'years') {
                $interest_rate = $interest_rate * $days_in_year; // Daily rate * days in year
            }
            // If repayment is also daily, no conversion needed
        }
        // For weekly charging products
        elseif ($interest_rate_type == 'week') {
            if ($repayment_frequency_type == 'days') {
                $interest_rate = $interest_rate / 7; // Weekly rate / 7 days
            } elseif ($repayment_frequency_type == 'months') {
                $interest_rate = $interest_rate * $weeks_in_month; // Weekly rate * weeks in month
            } elseif ($repayment_frequency_type == 'years') {
                $interest_rate = $interest_rate * $weeks_in_year; // Weekly rate * weeks in year
            }
            // If repayment is also weekly, no conversion needed
        }
        // For monthly charging products
        elseif ($interest_rate_type == 'month') {
            if ($repayment_frequency_type == 'days') {
                $interest_rate = $interest_rate / $days_in_month; // Monthly rate / days in month
            } elseif ($repayment_frequency_type == 'weeks') {
                $interest_rate = $interest_rate / $weeks_in_month; // Monthly rate / weeks in month
            } elseif ($repayment_frequency_type == 'years') {
                $interest_rate = $interest_rate * 12; // Monthly rate * 12 months
            }
            // If repayment is also monthly, no conversion needed
        }
        // For yearly charging products
        elseif ($interest_rate_type == 'year') {
            if ($repayment_frequency_type == 'days') {
                $interest_rate = $interest_rate / $days_in_year; // Yearly rate / days in year
            } elseif ($repayment_frequency_type == 'weeks') {
                $interest_rate = $interest_rate / $weeks_in_year; // Yearly rate / weeks in year
            } elseif ($repayment_frequency_type == 'months') {
                $interest_rate = $interest_rate / 12; // Yearly rate / 12 months
            }
            // If repayment is also yearly, no conversion needed
        }
        
        // Convert percentage to decimal and adjust for repayment frequency
        return $interest_rate * $repayment_frequency / 100;
    }

    public function determine_amortized_payment($interest_rate, $balance, $period)
    {
         $monthlyPayment = ($balance * $interest_rate) / (1 - pow(1 + $interest_rate, -$period));

        return $monthlyPayment;

    }

    public function handle(LoanDisbursed $event): void
    {
        $loan = $event->loan;
       
        $client_account = ClientAccount::where('client_id', $loan->client_id)->first();
        $interest_rate = $this->determine_period_interest_rate($loan->interest_rate, $loan->repayment_frequency_type, $loan->interest_rate_type, $loan->repayment_frequency);
        $balance = round($loan->approved_amount, $loan->decimals);
        $interest_balance = round(($interest_rate) * $loan->approved_amount, $loan->decimals);
        $period = ($loan->loan_term / $loan->repayment_frequency);
        $payment_from_date = $loan->disbursed_on_date;
        $next_payment_date = $loan->first_payment_date;
        $total_principal = 0;
        $total_interest = 0;

       
        
        for ($i = 1; $i <= $period; $i++) {
            $loan_repayment_schedule = new LoanRepaymentSchedule();
            $loan_repayment_schedule->created_by_id = Auth::id();
            $loan_repayment_schedule->loan_id = $loan->id;
            $loan_repayment_schedule->installment = $i;
            $loan_repayment_schedule->due_date = $next_payment_date;
            $loan_repayment_schedule->from_date = $payment_from_date;
            $date = explode('-', $next_payment_date);
            $loan_repayment_schedule->month = $date[1];
            $loan_repayment_schedule->year = $date[0];

            //determine which method to use
            //flat  method
            if ($loan->interest_methodology == 'flat') {
                $principal = round($loan->approved_amount / $period, $loan->decimals);
                $rate = round($interest_rate * $loan->approved_amount, $loan->decimals);
                $interest = round($rate /$period, $loan->decimals);
                if ($i == $period) {
                    $loan_repayment_schedule->principal = round($balance, $loan->decimals);
                    $loan_repayment_schedule->interest = round($interest_balance, $loan->decimals);
                } else {
                    $loan_repayment_schedule->principal = $principal;
                    $loan_repayment_schedule->interest = $interest;
                }
                //determine next balance
                $balance = ($balance - $principal);
                $interest_balance = ($interest_balance - $interest);
                
            }
            //reducing balance
            if ($loan->interest_methodology == 'declining_balance') {
                if ($loan->amortization_method == 'equal_installments') {
                    $interest_rate = $this->determine_period_interest_rate($loan->interest_rate, $loan->repayment_frequency_type, $loan->interest_rate_type, $loan->repayment_frequency);
                    $amortized_payment = ($balance * $interest_rate) / (1 - pow(1 + $interest_rate, -$period));
                    //determine if we have grace period for interest
                    $interest = round($interest_rate * $balance, $loan->decimals);
                    $principal = round(($amortized_payment - $interest), $loan->decimals);
                    if ($loan->grace_on_interest_charged >= $i) {
                        $loan_repayment_schedule->interest = 0;
                    } else {
                        $loan_repayment_schedule->interest = $interest;
                    }
                    if ($i == $period) {
                        $loan_repayment_schedule->principal = round($balance, $loan->decimals);
                    } else {
                        $loan_repayment_schedule->principal = $principal;

                    }
                    //determine next balance
                    $balance = ($balance - $principal);
                }
                if ($loan->amortization_method == 'equal_principal_payments') {
                    $principal = round($loan->approved_amount / $period, $loan->decimals);
                    //determine if we have grace period for interest
                    $interest = round($interest_rate * $balance, $loan->decimals);
                    if ($loan->grace_on_interest_charged >= $i) {
                        $loan_repayment_schedule->interest = 0;
                    } else {
                        $loan_repayment_schedule->interest = $interest;
                    }
                    if ($i == $period) {
                        //account for values lost during rounding
                        $loan_repayment_schedule->principal = round($balance, $loan->decimals);
                    } else {
                        $loan_repayment_schedule->principal = $principal;
                    }
                    //determine next balance
                    $balance = ($balance - $principal);
                }

            }
            $payment_from_date = Carbon::parse($next_payment_date)->add(1, 'day')->format("Y-m-d");
            if($loan->repayment_frequency_type=='months'){
                $next_payment_date = Carbon::parse($next_payment_date)->addMonthsNoOverflow($loan->repayment_frequency)->format("Y-m-d");
            }else{
                $next_payment_date = Carbon::parse($next_payment_date)->add($loan->repayment_frequency, $loan->repayment_frequency_type)->format("Y-m-d");
            }
            $total_principal = $total_principal + $loan_repayment_schedule->principal;
            $total_interest = $total_interest + $loan_repayment_schedule->interest;
            $loan_repayment_schedule->total_due = $loan_repayment_schedule->principal + $loan_repayment_schedule->interest;
            $loan_repayment_schedule->save();
        }

                $loan->expected_maturity_date = $next_payment_date;
                $loan->principal_disbursed_derived = $total_principal;
                $loan->interest_disbursed_derived = $total_interest;


                //journal transaction
                $journal_transaction = new Transaction();
                $journal_transaction->loan_id = $loan->id;
                $journal_transaction->branch_id = $loan->branch_id;
                $journal_transaction->chart_of_account_id = $loan->chart_of_account_id;
                $journal_transaction->type = 'journal';
                $journal_transaction->description = 'Loan';
                $journal_transaction->amount= $loan->approved_amount;
                $journal_transaction->posted_at = date("Y-m-d");
                $journal_transaction->save();
                $journal_transaction_id = $journal_transaction->id;

               //add disbursal transaction
                $loan_transaction = new LoanTransaction();
                $loan_transaction->created_by_id = Auth::id();
                $loan_transaction->loan_id = $loan->id;
                $loan_transaction->name = 'Disbursement';
                $loan_transaction->account_number = $loan->account_number;
                $loan_transaction->branch_id = $loan->branch_id;
                $loan_transaction->loan_transaction_type_id = 1;
                $loan_transaction->submitted_on = $loan->disbursed_on_date;
                $loan_transaction->created_on = date("Y-m-d");
                $loan_transaction->amount = $loan->approved_amount;
                $loan_transaction->debit = $loan->approved_amount;
                $loan_transaction->save();
                $disbursal_transaction_id = $loan_transaction->id;

                //add interest transaction
                $loan_transaction = new LoanTransaction();
                $loan_transaction->created_by_id = Auth::id();
                $loan_transaction->loan_id = $loan->id;
                $loan_transaction->account_number = $loan->account_number;
                $loan_transaction->branch_id = $loan->branch_id;
                $loan_transaction->name = 'Interest Applied';
                $loan_transaction->loan_transaction_type_id = 11;
                $loan_transaction->submitted_on = $loan->disbursed_on_date;
                $loan_transaction->created_on = date("Y-m-d");
                $loan_transaction->amount = $total_interest;
                $loan_transaction->debit = $total_interest;
                $loan_transaction->save();
                $installment_fees = 0;
                $disbursement_fees = 0;
  

                //charges
                foreach ($loan->charges as $key) {
                    //disbursement
                    if ($key->loan_charge_type_id == 1) {
                        if ($key->loan_charge_option_id == 1) {
                            $key->calculated_amount = $key->amount;
                            $key->amount_paid_derived = $key->calculated_amount;
                            $key->is_paid = 1;
                            $disbursement_fees = $disbursement_fees + $key->calculated_amount;
                        }
                        if ($key->loan_charge_option_id == 2) {
                            $key->calculated_amount = round(($key->amount * $total_principal / 100), $loan->decimals);
                            $key->amount_paid_derived = $key->calculated_amount;
                            $key->is_paid = 1;
                            $disbursement_fees = $disbursement_fees + $key->calculated_amount;
                        }
                        if ($key->loan_charge_option_id == 3) {
                            $key->calculated_amount = round(($key->amount * ($total_interest + $total_principal) / 100), $loan->decimals);
                            $key->amount_paid_derived = $key->calculated_amount;
                            $key->is_paid = 1;
                            $disbursement_fees = $disbursement_fees + $key->calculated_amount;
                        }
                        if ($key->loan_charge_option_id == 4) {
                            $key->calculated_amount = round(($key->amount * $total_interest / 100), $loan->decimals);
                            $key->amount_paid_derived = $key->calculated_amount;
                            $key->is_paid = 1;
                            $disbursement_fees = $disbursement_fees + $key->calculated_amount;
                        }
                        if ($key->loan_charge_option_id == 5) {
                            $key->calculated_amount = round(($key->amount * $total_principal / 100), $loan->decimals);
                            $key->amount_paid_derived = $key->calculated_amount;
                            $key->is_paid = 1;
                            $disbursement_fees = $disbursement_fees + $key->calculated_amount;
                        }
                        if ($key->loan_charge_option_id == 6) {
                            $key->calculated_amount = round(($key->amount * $total_principal / 100), $loan->decimals);
                            $key->amount_paid_derived = $key->calculated_amount;
                            $key->is_paid = 1;
                            $disbursement_fees = $disbursement_fees + $key->calculated_amount;
                        }
                        if ($key->loan_charge_option_id == 7) {
                            $key->calculated_amount = round(($key->amount * $loan->principal / 100), $loan->decimals);
                            $key->amount_paid_derived = $key->calculated_amount;
                            $key->is_paid = 1;
                            $disbursement_fees = $disbursement_fees + $key->calculated_amount;
                        }
                        if ($key->loan_charge_option_id == 8) {
                            $key->calculated_amount = round(($key->amount * $loan->principal / 100), $loan->decimals);
                            $key->amount_paid_derived = $key->calculated_amount;
                            $key->is_paid = 1;
                            $disbursement_fees = $disbursement_fees + $key->calculated_amount;
                        }

                        if ($disbursement_fees > 0) {


                            // create disbursement fee transaction and get transaction id
                            $loan_transaction = new LoanTransaction();
                            $loan_transaction->created_by_id = Auth::id();
                            $loan_transaction->loan_id = $loan->id;
                            $loan_transaction->branch_id = $loan->branch_id;
                            $loan_transaction->name = $key->name;
                            $loan_transaction->loan_transaction_type_id = 5;
                            $loan_transaction->submitted_on = $loan->disbursed_on_date;
                            $loan_transaction->created_on = date("Y-m-d");
                            $loan_transaction->amount = $disbursement_fees;
                            $loan_transaction->debit = $disbursement_fees;
                            $loan_transaction->credit = 0;
                            $loan_transaction->save();
                            $disbursement_fees_transaction_id = $loan_transaction->id;
                       

                             //check if fee is insurance and create journal and transaction entry
                             if ($key->loan_charge_type_id == 8) {
                                $journal_entry = new JournalEntries();
                                $journal_entry->transaction_id = $disbursement_fees_transaction_id;
                                $journal_entry->type = 'debit';
                                $journal_entry->branch_id = $loan->branch_id;
                                $journal_entry->amount = $disbursement_fees;
                                $journal_entry->description = 'Insurance Fee';
                                $journal_entry->chart_of_account_id = $loan->loan_product->insurance_chart_of_account_id;
                                $journal_entry->save();
                            }   


                        
 
                            //create journal for disbursement fee
                            $journal_entry = new JournalEntries();
                            $journal_entry->transaction_id = $disbursement_fees_transaction_id;
                            $journal_entry->type = 'debit';
                            $journal_entry->branch_id = $loan->branch_id;
                            $journal_entry->amount = $disbursement_fees;
                            $journal_entry->description = $key->name.'For Loan '.$loan->loan_account_number;
                            $journal_entry->chart_of_account_id = $loan->loan_product->administration_fees_chart_of_account_id;
                            $journal_entry->save();

                            //check if client account has funds and pay the fee
                           
                            if ($client_account->balance >= $disbursement_fees) {
                                $client_account->withdraw($disbursement_fees,'fee',$key->name);
                            
                            

                            if($key->loan_charge_type_id == 8){
                                //transaction
                                $loan_transaction = new LoanTransaction();
                                $loan_transaction->created_by_id = Auth::id();
                                $loan_transaction->loan_id = $loan->id;
                                $loan_transaction->branch_id = $loan->branch_id;
                                $loan_transaction->name = $key->name;
                                $loan_transaction->loan_transaction_type_id = 2;
                                $loan_transaction->submitted_on = $loan->disbursed_on_date;
                                $loan_transaction->created_on = date("Y-m-d");
                                $loan_transaction->amount = $disbursement_fees;
                                $loan_transaction->credit = $disbursement_fees;
                                $loan_transaction->debit = 0;
                                $loan_transaction->reference = $client_account->account_number;
                                $loan_transaction->save();
                                $disbursement_fees_transaction_id = $loan_transaction->id;

                                //journal entry
                                $journal_entry = new JournalEntries();
                                $journal_entry->transaction_id = $disbursement_fees_transaction_id;
                                $journal_entry->type = 'credit';
                                $journal_entry->branch_id = $loan->branch_id;
                                $journal_entry->amount = $disbursement_fees;
                                $journal_entry->description = $key->name.'For Loan '.$loan->loan_account_number;
                                $journal_entry->chart_of_account_id = $loan->loan_product->insurance_chart_of_account_id;
                                $journal_entry->save();
                            }

                            //record transactions
                            $loan_transaction = new LoanTransaction();
                            $loan_transaction->created_by_id = Auth::id();
                            $loan_transaction->loan_id = $loan->id;
                            $loan_transaction->branch_id = $loan->branch_id;
                            $loan_transaction->name = $key->name;
                            $loan_transaction->loan_transaction_type_id = 5;
                            $loan_transaction->submitted_on = $loan->disbursed_on_date;
                            $loan_transaction->created_on = date("Y-m-d");
                            $loan_transaction->amount = $disbursement_fees;
                            $loan_transaction->credit = $disbursement_fees;
                            $loan_transaction->debit = 0;
                            $loan_transaction->reference = $client_account->account_number;
                            $loan_transaction->description = $key->name.'For Loan '.$loan->loan_account_number;
                            $loan_transaction->save();
                            $disbursement_fees_transaction_id = $loan_transaction->id;

                            //journal entry
                            $journal_entry = new JournalEntries();
                            $journal_entry->transaction_id = $disbursement_fees_transaction_id;
                            $journal_entry->type = 'credit';
                            $journal_entry->branch_id = $loan->branch_id;
                            $journal_entry->amount = $disbursement_fees;
                            $journal_entry->description = $key->name.'For Loan '.$loan->loan_account_number;
                            $journal_entry->chart_of_account_id = $loan->loan_product->administration_fees_chart_of_account_id;
                            $journal_entry->save();
                        }
                    }
                        $loan->disbursement_charges += $disbursement_fees;
                        $loan->save();
                    }

                     //installment_fee
                    if ($key->loan_charge_type_id == 3) {
                        if ($key->loan_charge_option_id == 1) {
                            $key->calculated_amount = $key->amount;
                            $installment_fees = $installment_fees + $key->calculated_amount;
                        }
                        if ($key->loan_charge_option_id == 2) {
                            $key->calculated_amount = round(($key->amount * $total_principal / 100), $loan->decimals);
                            $installment_fees = $installment_fees + $key->calculated_amount;
                        }
                        if ($key->loan_charge_option_id == 3) {
                            $key->calculated_amount = round(($key->amount * ($total_interest + $total_principal) / 100), $loan->decimals);
                            $installment_fees = $installment_fees + $key->calculated_amount;
                        }
                        if ($key->loan_charge_option_id == 4) {
                            $key->calculated_amount = round(($key->amount * $total_interest / 100), $loan->decimals);
                            $installment_fees = $installment_fees + $key->calculated_amount;
                        }
                        if ($key->loan_charge_option_id == 5) {
                            $key->calculated_amount = round(($key->amount * $total_principal / 100), $loan->decimals);
                            $installment_fees = $installment_fees + $key->calculated_amount;
                        }
                        if ($key->loan_charge_option_id == 6) {
                            $key->calculated_amount = round(($key->amount * $total_principal / 100), $loan->decimals);
                            $installment_fees = $installment_fees + $key->calculated_amount;
                        }
                        if ($key->loan_charge_option_id == 7) {
                            $key->calculated_amount = round(($key->amount * $loan->principal / 100), $loan->decimals);
                            $installment_fees = $installment_fees + $key->calculated_amount;
                        }
                    //create transaction
                    $loan_transaction = new LoanTransaction();
                    $loan_transaction->created_by_id = Auth::id();
                    $loan_transaction->loan_id = $loan->id;
                    $loan_transaction->branch_id = $loan->branch_id;
                    $loan_transaction->name = $key->name;
                    $loan_transaction->loan_transaction_type_id = 10;
                    $loan_transaction->submitted_on = $loan->disbursed_on_date;
                    $loan_transaction->created_on = date("Y-m-d");
                    $loan_transaction->amount = $key->calculated_amount;
                    $loan_transaction->debit = $key->calculated_amount;
                    $loan_transaction->reversible = 1;
                    $loan_transaction->save();
                    $key->loan_transaction_id = $loan_transaction->id;
                    $key->save();
                    //add the charges to the schedule
                    foreach ($loan->repayment_schedules as $loan_repayment_schedule) {
                        if ($key->loan_charge_option_id == 2) {
                            $loan_repayment_schedule->fees = $loan_repayment_schedule->fees + round(($key->amount * $loan_repayment_schedule->principal / 100), $loan->decimals);
                        } elseif ($key->loan_charge_option_id == 3) {
                            $loan_repayment_schedule->fees = $loan_repayment_schedule->fees + round(($key->amount * ($loan_repayment_schedule->interest + $loan_repayment_schedule->principal) / 100), $loan->decimals);
                        } elseif ($key->loan_charge_option_id == 4) {
                            $loan_repayment_schedule->fees = $loan_repayment_schedule->fees + round(($key->amount * $loan_repayment_schedule->interest / 100), $loan->decimals);
                        } else {
                            $loan_repayment_schedule->fees = $loan_repayment_schedule->fees + $key->calculated_amount;
                        }
                        $loan_repayment_schedule->total_due = $loan_repayment_schedule->principal + $loan_repayment_schedule->interest + $loan_repayment_schedule->fees;
                        $loan_repayment_schedule->save();
                       }
                    }

                }
                   


                    //check if accounting is enabled
                    if ($loan->loan_product->accounting_rule == "cash" || $loan->loan_product->accounting_rule == "accrual_periodic" || $loan->loan_product->accounting_rule == "accrual_upfront") {
                        //loan disbursal
                        //debit loan disbursed account
                        $journal_entry = new JournalEntries();
                        $journal_entry->transaction_id = $journal_transaction_id;
                        $journal_entry->type = 'debit';
                        $journal_entry->branch_id = $loan->branch_id;
                        $journal_entry->amount = $loan->approved_amount;
                        $journal_entry->description = 'Loan Disbursed';
                        $journal_entry->chart_of_account_id = $loan->loan_product->portfolio_chart_of_account_id;
                        $journal_entry->save();

                        //debit repayment account
                        $journal_entry = new JournalEntries();
                        $journal_entry->transaction_id = $journal_transaction_id;
                        $journal_entry->type = 'debit';
                        $journal_entry->branch_id = $loan->branch_id;
                        $journal_entry->amount = $loan->approved_amount;
                        $journal_entry->description = 'Loan Disbursed';
                        $journal_entry->chart_of_account_id = $loan->loan_product->repayment_account_id;
                        $journal_entry->save();
                        

                        //credit loan portfolio account
                        $journal_entry = new JournalEntries();
                        $journal_entry->transaction_id = $journal_transaction_id;
                        $journal_entry->type = 'credit';
                        $journal_entry->branch_id = $loan->branch_id;
                        $journal_entry->amount = $loan->approved_amount;
                        $journal_entry->description = 'Loan Disbursed';
                        $journal_entry->chart_of_account_id = $loan->chart_of_account_id;
                        $journal_entry->save();


                         //debit interest accrued account
                         $journal_entry = new JournalEntries();
                         $journal_entry->transaction_id = $journal_transaction_id;
                         $journal_entry->type = 'debit';
                         $journal_entry->branch_id = $loan->branch_id;
                         $journal_entry->amount = $loan->interest_disbursed_derived;
                         $journal_entry->description = 'interest due';
                         $journal_entry->chart_of_account_id = $loan->loan_product->interest_due_chart_of_account_id;
                         $journal_entry->save(); 

                         //credit interest accrued account
                        //  $journal_entry = new JournalEntries();
                        //  $journal_entry->transaction_id = $journal_transaction_id;
                        //  $journal_entry->type = 'credit';
                        //  $journal_entry->branch_id = $loan->branch_id;
                        //  $journal_entry->amount = $loan->interest_disbursed_derived;
                        //  $journal_entry->description = 'interest due';
                        //  $journal_entry->chart_of_account_id = $loan->loan_product->interest_due_chart_of_account_id;
                        //  $journal_entry->save(); 

                        
                        // Check if client still has balance after all fees and use it for loan repayment
                        if ($client_account->balance > 0) {
                            // Determine amount to use for repayment (available balance)
                            $repaymentAmount = $client_account->balance;
                            
                            // Create a loan transaction record for the repayment
                            $loanTransaction = new \App\Models\Loan\LoanTransaction();
                            $loanTransaction->loan_id = $loan->id;
                            $loanTransaction->branch_id = $loan->branch_id;
                            $loanTransaction->payment_detail_id = null;
                            $loanTransaction->loan_transaction_type_id = 2; // Repayment
                            $loanTransaction->submitted_on = date('Y-m-d');
                            $loanTransaction->created_on = date('Y-m-d');
                            $loanTransaction->amount = $repaymentAmount;
                            $loanTransaction->principal_repaid_derived = 0;
                            $loanTransaction->interest_repaid_derived = 0;
                            $loanTransaction->fees_repaid_derived = 0;
                            $loanTransaction->penalties_repaid_derived = 0;
                            $loanTransaction->reference = $client_account->account_number;
                            $loanTransaction->payment_method = 'account_balance';
                            $loanTransaction->save();
                            
                            // Process the repayment using the client account
                            try {
                                // Withdraw from client account for loan repayment
                                $transaction = $client_account->processLoanRepayment(
                                    $repaymentAmount,
                                    $loan->id,
                                    'Automatic loan repayment from account balance',
                                    [
                                        'loan_transaction_id' => $loanTransaction->id,
                                        'payment_method' => 'account_balance',
                                    ]
                                );
                                
                                // Dispatch job to update loan transactions and schedules
                                \App\Jobs\UpdateTransactionsJob::dispatch($loan, [
                                    'amount' => $repaymentAmount,
                                    'payment_method' => 'account_balance',
                                    'reference_number' => 'AUTO-' . date('YmdHis')
                                ]);

                            } catch (\Exception $e) {
                                // Log error but continue with loan disbursement
                                \Log::error('Failed to process automatic repayment: ' . $e->getMessage());
                            }
                        }

                    }

            }


            
                          
    /**
     * Handle the special Wezesha loan product schedule calculation
     * 
     * Wezesha loan has the following characteristics:
     * - Loan amount: 7,000-30,000 KSH
     * - Repayment period: 1-30 days (daily or weekly basis)
     * - Interest: 1% per day of loan amount
     * - Minimum interest: 14% (14 days interest)
     * - Interest structure:
     *   - Fixed at 14% between disbursement and day 14
     *   - After day 14, accrues daily at 1% per day
     */
    private function handleWezeshaLoanSchedule($loan, $payment_from_date, $next_payment_date)
    {
        // Calculate the total loan term in days
        $loanTermDays = $loan->loan_term;
        if ($loan->repayment_frequency_type == 'weeks') {
            $loanTermDays = $loan->loan_term * 7;
        }
        
        $balance = round($loan->approved_amount, $loan->decimals);
        $dailyInterestRate = 0.01; // 1% daily interest rate
        $minimumInterestDays = 14;
        $minimumInterestAmount = round($loan->approved_amount * $dailyInterestRate * $minimumInterestDays, $loan->decimals);
        
        // Determine if this is a daily or weekly repayment schedule
        $isDaily = ($loan->repayment_frequency_type == 'days');
        $period = $isDaily ? $loanTermDays : ceil($loanTermDays / 7);
        
        $disbursed_date = Carbon::parse($loan->disbursed_on_date);
        $payment_from_date = $disbursed_date->format('Y-m-d');
        $next_payment_date = Carbon::parse($loan->first_payment_date);
        
        $total_principal = 0;
        $total_interest = 0;
        
        // Calculate principal per installment
        $principalPerInstallment = round($loan->approved_amount / $period, $loan->decimals);
        
        // For tracking days since disbursement
        $daysSinceDisbursement = 0;
        
        for ($i = 1; $i <= $period; $i++) {
            $loan_repayment_schedule = new LoanRepaymentSchedule();
            $loan_repayment_schedule->created_by_id = Auth::id();
            $loan_repayment_schedule->loan_id = $loan->id;
            $loan_repayment_schedule->installment = $i;
            $loan_repayment_schedule->due_date = $next_payment_date->format('Y-m-d');
            $loan_repayment_schedule->from_date = $payment_from_date;
            $date = explode('-', $next_payment_date->format('Y-m-d'));
            $loan_repayment_schedule->month = $date[1];
            $loan_repayment_schedule->year = $date[0];
            
            // Calculate days in this installment period
            $daysInPeriod = $isDaily ? 1 : 7;
            
            // Calculate interest for this period
            $interestForPeriod = 0;
            
            // For each day in this period
            for ($day = 0; $day < $daysInPeriod; $day++) {
                $currentDay = $daysSinceDisbursement + $day;
                
                // If we're still within the first 14 days, use the minimum interest
                if ($currentDay < $minimumInterestDays) {
                    // Interest is already accounted for in the minimum
                    continue;
                } else {
                    // After 14 days, add 1% daily interest
                    $interestForPeriod += round($loan->approved_amount * $dailyInterestRate, $loan->decimals);
                }
            }
            
            // Update days since disbursement
            $daysSinceDisbursement += $daysInPeriod;
            
            // Set principal for this installment
            if ($i == $period) {
                // Last installment - account for any rounding issues
                $loan_repayment_schedule->principal = round($balance, $loan->decimals);
            } else {
                $loan_repayment_schedule->principal = $principalPerInstallment;
            }
            
            // Set interest for this installment
            if ($i == 1 && $loanTermDays <= $minimumInterestDays) {
                // If loan term is less than or equal to 14 days, charge the minimum interest on the first installment
                $loan_repayment_schedule->interest = $minimumInterestAmount;
            } else if ($i == 1) {
                // First installment for loans longer than 14 days
                // Calculate how much of the minimum interest to allocate to first installment
                $firstInstallmentDays = $isDaily ? 1 : 7;
                $proportionOfMinimum = min(1, $firstInstallmentDays / $minimumInterestDays);
                $loan_repayment_schedule->interest = round($minimumInterestAmount * $proportionOfMinimum, $loan->decimals);
            } else if ($daysSinceDisbursement <= $minimumInterestDays) {
                // Installments that fall within the first 14 days
                $daysInMinimumPeriod = min($daysInPeriod, $minimumInterestDays - ($daysSinceDisbursement - $daysInPeriod));
                $proportionOfMinimum = $daysInMinimumPeriod / $minimumInterestDays;
                $loan_repayment_schedule->interest = round($minimumInterestAmount * $proportionOfMinimum, $loan->decimals);
            } else {
                // Installments after 14 days
                $loan_repayment_schedule->interest = $interestForPeriod;
            }
            
            // Update balance
            $balance -= $loan_repayment_schedule->principal;
            
            // Update payment dates
            $payment_from_date = Carbon::parse($next_payment_date)->addDay()->format('Y-m-d');
            if ($isDaily) {
                $next_payment_date->addDay();
            } else {
                $next_payment_date->addWeek();
            }
            
            // Update totals
            $total_principal += $loan_repayment_schedule->principal;
            $total_interest += $loan_repayment_schedule->interest;
            $loan_repayment_schedule->total_due = $loan_repayment_schedule->principal + $loan_repayment_schedule->interest;
            $loan_repayment_schedule->save();
        }
        
        // Update loan details
        $loan->expected_maturity_date = $next_payment_date->format('Y-m-d');
        $loan->principal_disbursed_derived = $total_principal;
        $loan->interest_disbursed_derived = $total_interest;
        
        // Create journal transaction
        $journal_transaction = new Transaction();
        $journal_transaction->loan_id = $loan->id;
        $journal_transaction->branch_id = $loan->branch_id;
        $journal_transaction->chart_of_account_id = $loan->fund_id;
        $journal_transaction->type = 'journal';
        $journal_transaction->description = 'Wezesha Loan';
        $journal_transaction->amount = $loan->approved_amount;
        $journal_transaction->posted_at = date('Y-m-d');
        $journal_transaction->save();
        $journal_transaction_id = $journal_transaction->id;
        
        //add disbursal transaction
        $loan_transaction = new LoanTransaction();
        $loan_transaction->created_by_id = Auth::id();
        $loan_transaction->loan_id = $loan->id;
        $loan_transaction->name = 'Disbursement';
        $loan_transaction->account_number = $loan->account_number;
        $loan_transaction->branch_id = $loan->branch_id;
        $loan_transaction->loan_transaction_type_id = 1;
        $loan_transaction->submitted_on = $loan->disbursed_on_date;
        $loan_transaction->created_on = date('Y-m-d');
        $loan_transaction->amount = $loan->approved_amount;
        $loan_transaction->debit = $loan->approved_amount;
        $loan_transaction->save();
        $disbursal_transaction_id = $loan_transaction->id;
        
        //add interest transaction
        $loan_transaction = new LoanTransaction();
        $loan_transaction->created_by_id = Auth::id();
        $loan_transaction->loan_id = $loan->id;
        $loan_transaction->account_number = $loan->account_number;
        $loan_transaction->branch_id = $loan->branch_id;
        $loan_transaction->name = 'Interest Applied';
        $loan_transaction->loan_transaction_type_id = 11;
        $loan_transaction->submitted_on = $loan->disbursed_on_date;
        $loan_transaction->created_on = date('Y-m-d');
        $loan_transaction->amount = $total_interest;
        $loan_transaction->debit = $total_interest;
        $loan_transaction->save();
        
        return true;
    }
    }

