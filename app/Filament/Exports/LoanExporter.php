<?php

namespace App\Filament\Exports;

use App\Models\Currency;
use App\Models\Loan\Loan;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Models\Export;
use Filament\Actions\Exports\Enums\ExportFormat;

class LoanExporter extends Exporter
{
    protected static ?string $model = Loan::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')->label('Loan ID'),
            ExportColumn::make('loan_officer.full_name')->label('Loan Officer'),
            ExportColumn::make('client.full_name')->label('Client'),
            ExportColumn::make('client.account_number')->label('Account Number'),
            ExportColumn::make('client.mobile')->label('Phone Number'),
            ExportColumn::make('approved_amount')->label('Approved Amount'),
            ExportColumn::make('principal_disbursed_derived')->label('Principal Amount'),
            ExportColumn::make('interest_rate')->label('Interest Rate')
                ->suffix('%'),
            ExportColumn::make('repayment_schedules.total_due')
                  ->label('Balance')
                  ->state(fn (Loan $record) => $record->getBalance($record->id)),
            ExportColumn::make('repayment_schedules.principal')
                  ->label('Arrears')
                  ->state(fn (Loan $record) => $record->getAmountDue($record->id)),
            ExportColumn::make('arrears_days')->label(' Days In Arrears')
                ->state(fn (Loan $record) => $record->getDaysInArrears($record->id)),

            ExportColumn::make('status')
                ->label('Status')
                ->formatStateUsing(fn (Loan $record) => $record->getStatus($record->id)),
            ExportColumn::make('disbursed_on_date')->label('Disbursed On'),
            ExportColumn::make('expected_maturity_date')->label('Maturity Date'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your loan export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }

    public function getFileName(Export $export): string
    {
        return "Loans-{$export->getKey()}.csv";
    }

    public function getFormats(): array
    {
        return [
            ExportFormat::Csv,
        ];
    }
}
