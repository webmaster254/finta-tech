<?php

namespace App\Filament\Resources\MpesaTransactionsResource\Pages;

use App\Filament\Resources\MpesaTransactionsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMpesaTransactions extends EditRecord
{
    protected static string $resource = MpesaTransactionsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
