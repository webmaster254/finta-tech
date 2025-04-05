<?php

namespace App\Filament\Resources\LoanTransactionResource\Pages;

use App\Filament\Resources\LoanTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateLoanTransaction extends CreateRecord
{
    protected static string $resource = LoanTransactionResource::class;
}
