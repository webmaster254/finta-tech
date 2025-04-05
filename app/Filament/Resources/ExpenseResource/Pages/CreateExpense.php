<?php

namespace App\Filament\Resources\ExpenseResource\Pages;

use Filament\Actions;
use App\Models\BankAccount;
use App\Models\JournalEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\ExpenseResource;

class CreateExpense extends CreateRecord
{
    protected static string $resource = ExpenseResource::class;

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Expense Created successfully';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }


    // protected function mutateFormDataBeforeCreate(array $data): array
    // {



    //     //update bank account balance
    //     if(!empty($data['expense_chart_of_account_id'])) {
    //         $fundsExpenseAccount = BankAccount::find($data['expense_chart_of_account_id']);
    //         if($fundsExpenseAccount->balance < $data['amount'])
    //         {
    //             Notification::make()
    //                         ->title('Insufficient Funds')
    //                         ->danger()
    //                         ->body('The Expense account has insufficient fund for Expense')
    //                         ->send();

    //             //halt
    //             $this->halt();
    //         } else {
    //             $fundsExpenseAccount->balance -=$data['amount'];
    //             $fundsExpenseAccount->save();

    //              //debit expense
    //             $transaction_number = uniqid();
    //             $journal = new JournalEntry();
    //             $journal->transaction_number = $transaction_number;
    //             $journal->chart_of_account_id = $data['expense_chart_of_account_id'];
    //             $journal->debit = $data['amount'];
    //             $journal->credit = 0;
    //             $journal->date = $data['date'];
    //             $journal->currency_id = $data['currency_id'];
    //             $journal->name = $data['description'];
    //             $journal->created_by_id = $data['created_by_id'];
    //             $journal->transaction_type = 'expense';
    //             $journal->receipt = $transaction_number;
    //             $journal->notes = $data['description'];
    //             $journal->save();
    //         }



    //     }

    //      //update bank account balance
    //      if (!empty($data['asset_chart_of_account_id'])) {
    //         $fundsAssetAccount = BankAccount::find($data['asset_chart_of_account_id']);
    //         if($fundsAssetAccount->balance < $data['amount'])
    //         {
    //             Notification::make()
    //                         ->title('Insufficient Funds')
    //                         ->danger()
    //                         ->body('The Asset account has insufficient fund for Expense')
    //                         ->send();

    //            //halt
    //            $this->halt();
    //         } else {
    //             $fundsAssetAccount->balance +=$data['amount'];
    //             $fundsAssetAccount->save();

    //             //credit asset
    //             $journal = new JournalEntry();
    //             $journal->transaction_number = $transaction_number;
    //             $journal->chart_of_account_id = $data['asset_chart_of_account_id'];
    //             $journal->debit = 0;
    //             $journal->credit = $data['amount'];
    //             $journal->date = $data['date'];
    //             $journal->currency_id = $data['currency_id'];
    //             $journal->name = $data['description'];
    //             $journal->created_by_id = $data['created_by_id'];
    //             $journal->transaction_type = 'expense';
    //             $journal->receipt = $transaction_number;
    //             $journal->notes = $data['description'];
    //             $journal->save();
    //         }



    //     }
    //     return $data;
    // }
}
