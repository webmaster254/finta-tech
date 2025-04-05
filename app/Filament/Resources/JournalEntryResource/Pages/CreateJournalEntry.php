<?php

namespace App\Filament\Resources\JournalEntryResource\Pages;

use Filament\Actions;
use App\Models\JournalEntry;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\JournalEntryResource;

class CreateJournalEntry extends CreateRecord
{
    protected static string $resource = JournalEntryResource::class;

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Journal Entry Created successfully';
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
{

    //dump($data);
         // debit record
        //  $transaction_number = uniqid();
        //  $debit = new JournalEntry();
        //  $debit->created_by_id = auth()->id();
        //  $debit->currency_id = $data['currency_id'];
        //  $debit->transaction_type = 'manual_entry';
        //  $debit->debit = $data['amount'];
        //  $debit->manual_entry = 1;
        //  $debit->name = $data['name'];
        //  $debit->transaction_number = $transaction_number;
        //  $debit->date = $data['date'];
        //  $debit->reference = $data['reference'];
        //  $debit->receipt = $data['receipt'];
        //  $debit->notes = $data['notes'];
        //  $debit->active = $data['active'];
        //  $debit->chart_of_account_id = $data['debit'];
        //  $debit->save();

         // credit record
         $credit = new JournalEntry();
         $credit->created_by_id = auth()->id();
         $credit->currency_id = $data['currency_id'];
         $credit->transaction_type = 'manual_entry';
         $credit->credit = $data['debit'];
         $credit->manual_entry = 1;
         $credit->name = $data['name'];
         $credit->transaction_number = $data['transaction_number'];
         $credit->debit = 0;
         $credit->date = $data['date'];
         $credit->reference = $data['reference'];
         $credit->receipt = $data['receipt'];
         $credit->notes = $data['notes'];
         $credit->active = $data['active'];
         $credit->chart_of_account_id = $data['credit_account'];
         $credit->save();

         return $data;

}

}
