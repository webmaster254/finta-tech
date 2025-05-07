<?php

namespace App\Filament\Imports;


use App\Models\MpesaC2B;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Models\Import;

class MpesaC2BImporter extends Importer
{
    protected static ?string $model = MpesaC2B::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('Transaction_type')
                ->rules(['max:255']),
            ImportColumn::make('Transaction_ID')
                ->rules(['required', 'max:255']),
            ImportColumn::make('Transaction_Time')
                ->rules(['max:255']),
            ImportColumn::make('Amount')
                ->rules(['required', 'max:255'])
                ->numeric(),
            ImportColumn::make('Business_Shortcode')
                ->rules(['max:255']),
            ImportColumn::make('Account_Number')
                ->rules(['required', 'max:255']),
            ImportColumn::make('status')
                ->rules(['required', 'max:255'])
                ->requiredMapping(),
            ImportColumn::make('Organization_Account_Balance')
                ->rules(['max:255'])
                ->numeric(),
            ImportColumn::make('ThirdParty_Transaction_ID')
                ->rules(['max:255']),
            ImportColumn::make('Phonenumber')
                ->rules(['max:255']),
            ImportColumn::make('FirstName')
                ->rules(['max:255']),
            ImportColumn::make('MiddleName')
                ->rules(['max:255']),
            ImportColumn::make('LastName')
                ->rules(['max:255']),
        ];
    }

    public function resolveRecord(): ?MpesaC2B
    {
        // return MpesaC2B::firstOrNew([
        //     // Update existing records, matching them by `$this->data['column_name']`
        //     'email' => $this->data['email'],
        // ]);

        return new MpesaC2B();
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your mpesa payments import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}

