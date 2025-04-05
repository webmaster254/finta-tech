<?php

namespace App\Filament\Resources\MpesaTransactionsResource\Pages;

use App\Filament\Resources\MpesaTransactionsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMpesaTransactions extends ListRecords
{
    protected static string $resource = MpesaTransactionsResource::class;
    protected static ?string $title = 'Mpesa Reconciliation';

    protected function getHeaderActions(): array
    {
        return [
            //Actions\CreateAction::make(),
        ];
    }
}
