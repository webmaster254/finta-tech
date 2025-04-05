<?php

namespace App\Filament\Imports;

use App\Models\Loan\Loan;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class LoanImporter extends Importer
{
    protected static ?string $model = Loan::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('loan_officer_id')
                ->relationship(),
            ImportColumn::make('created_by_id')
                ->relationship(),
            ImportColumn::make('client_type'),
            ImportColumn::make('currency_id')
                ->relationship(),
            ImportColumn::make('loan_product_id')
                ->relationship(),
            ImportColumn::make('loan_transaction_processing_strategy_id')
                ->relationship(),
            ImportColumn::make('fund_id')
                ->relationship(),
            ImportColumn::make('loan_purpose_id')
                ->relationship(),
            ImportColumn::make('submitted_on_date'),
            ImportColumn::make('client_id')
                ->relationship(),
            ImportColumn::make('principal_disbursed_derived')
                ->numeric()
                ->rules(['numeric', 'min:0']),

            ImportColumn::make('interest_rate')
                ->numeric()
                ->rules(['numeric', 'min:0']),
            ImportColumn::make('repayment_schedules.total_due')
                ->numeric()
                ->rules(['numeric', 'min:0']),

        ];
    }

    public function resolveRecord(): ?Loan
    {
        // return Loan::firstOrNew([
        //     // Update existing records, matching them by `$this->data['column_name']`
        //     'email' => $this->data['email'],
        // ]);

        return new Loan();
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your loan import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
