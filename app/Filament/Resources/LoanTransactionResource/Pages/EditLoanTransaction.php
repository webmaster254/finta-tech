<?php

namespace App\Filament\Resources\LoanTransactionResource\Pages;

use App\Filament\Resources\LoanTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLoanTransaction extends EditRecord
{
    protected static string $resource = LoanTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
