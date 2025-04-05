<?php

namespace App\Models;

use App\Models\JournalEntry;
use App\Models\ChartOfAccount;
use App\Enums\ChartAccountCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class JournalEntry extends Model
{
    use HasFactory;
    protected $table = 'journal_entries';


    protected $fillable = [
        'created_by_id',
        'transaction_number',
        'payment_detail_id',
        'currency_id',
        'chart_of_account_id',
        'transaction_type',
        'transaction_sub_type',
        'name',
        'date',
        'month',
        'year',
        'reference',
        'client_id',
        'debit',
        'credit',
        'balance',
        'active',
        'reversed',
        'reversible',
        'manual_entry',
        'receipt',
        'notes',
    ];

    public function created_by()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function chart_of_account()
    {
        return $this->belongsTo(ChartOfAccount::class);
    }

    public function reverseJournalEntry($id)
    {

        $journalEntry = $this::where('transaction_number', $id)->get();

        $journal_entry = $this::where('transaction_number', $id)->update(['reversed' => 1, 'reversible' => 0]);


        //create new transactions to reverse these
        $transaction_number = uniqid();

        foreach ($journalEntry as $entry) {
            if(empty($entry->debit)) {
            //credit account
            $reverseEntry = new JournalEntry();
            $reverseEntry->name = $entry->name;
            $reverseEntry->created_by_id = $entry->created_by_id;
            $reverseEntry->transaction_number = $transaction_number;
            $reverseEntry->currency_id = $entry->currency_id;
            $reverseEntry->chart_of_account_id = $entry->chart_of_account_id;
            $reverseEntry->transaction_type = $entry->transaction_type;
            $reverseEntry->date = $entry->date;
            $reverseEntry->month = $entry->month;
            $reverseEntry->year = $entry->year;
            $reverseEntry->reference = $entry->reference;
            $reverseEntry->debit = $entry->credit;
            $reverseEntry->credit = 0;
            $reverseEntry->manual_entry = $entry->manual_entry;
            $reverseEntry->notes = $entry->notes;
            $reverseEntry->save();
        } else {
            //debit account
            $reverseEntry = new JournalEntry();
            $reverseEntry->name = $entry->name;
            $reverseEntry->created_by_id = $entry->created_by_id;
            $reverseEntry->transaction_number = $transaction_number;
            $reverseEntry->currency_id = $entry->currency_id;
            $reverseEntry->chart_of_account_id = $entry->chart_of_account_id;
            $reverseEntry->transaction_type = $entry->transaction_type;
            $reverseEntry->date = $entry->date;
            $reverseEntry->month = $entry->month;
            $reverseEntry->year = $entry->year;
            $reverseEntry->reference = $entry->reference;
            $reverseEntry->credit = $entry->debit;
            $reverseEntry->debit = 0;
            $reverseEntry->manual_entry = $entry->manual_entry;
            $reverseEntry->notes = $entry->notes;
            $reverseEntry->save();
        }
    }
    }

}
