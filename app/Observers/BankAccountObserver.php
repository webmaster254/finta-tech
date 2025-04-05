<?php

namespace App\Observers;


use App\Models\BankAccount;
use App\Models\Transaction;
use App\Models\JournalEntries;
use Illuminate\Support\Facades\DB;

class BankAccountObserver
{
    /**
     * Handle the BankAccount "deleting" event.
     */
    public function deleting(BankAccount $bankAccount): void
    {
        DB::transaction(function () use ($bankAccount) {
            $account = $bankAccount->chartOfAccount;


            if ($account) {
                $bankAccount->transactions()->each(fn (Transaction $transaction) => $transaction->delete());
                $account->delete();
            }


        });
    }
    public function created(BankAccount $bankAccount): void
    {

        $journal_transaction = new Transaction();
        $journal_transaction->branch_id = $bankAccount->branch_id;
       $journal_transaction->account_id = $bankAccount->chart_of_account_id;
        $journal_transaction->type = 'journal';
        $journal_transaction->reviewed = 1;
        $journal_transaction->description = 'Fund deposit';
        $journal_transaction->amount= $bankAccount->opening_balance;
        $journal_transaction->posted_at = date("Y-m-d");
        $journal_transaction->save();
        $journal_transaction_id = $journal_transaction->id;

        $journal_entry = new JournalEntries();
                $journal_entry->transaction_id = $journal_transaction_id;
                $journal_entry->type = 'credit';
                $journal_entry->branch_id = $bankAccount->branch_id;
                $journal_entry->amount = $bankAccount->opening_balance;
                $journal_entry->description = 'fund deposit';
                $journal_entry->chart_of_account_id = $bankAccount->chart_of_account_id;
                $journal_entry->save();
    }
}
