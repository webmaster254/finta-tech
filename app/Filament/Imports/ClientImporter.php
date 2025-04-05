<?php

namespace App\Filament\Imports;

use App\Models\Client;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class ClientImporter extends Importer
{
    protected static ?string $model = Client::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('title_id')
                ->numeric()
                ->rules(['integer']),
            ImportColumn::make('loan_officer')
                ->relationship(),
            ImportColumn::make('account_number')
                ->rules(['max:255']),
            ImportColumn::make('first_name')
                ->rules(['max:255']),
            ImportColumn::make('middle_name')
                ->rules(['max:255']),
            ImportColumn::make('last_name')
                ->rules(['max:255']),
            ImportColumn::make('gender')
                ->rules(['max:255']),
            ImportColumn::make('status')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('marital_status')
                ->rules(['max:255']),
            ImportColumn::make('profession')
                ->relationship(),
            ImportColumn::make('client_type')
                ->relationship(),
            ImportColumn::make('mobile')
                ->rules(['max:255']),
            ImportColumn::make('email')
                ->rules(['email', 'max:255']),
            ImportColumn::make('dob')
                ->rules(['date']),
            ImportColumn::make('address')
                ->rules(['max:65535']),
            ImportColumn::make('city')
                ->rules(['max:255']),
            ImportColumn::make('state')
                ->rules(['max:255']),
            ImportColumn::make('photo')
                ->rules(['max:255']),
            ImportColumn::make('notes')
                ->rules(['max:65535']),
            ImportColumn::make('created_date')
                ->rules(['date']),
        ];
    }

    public function resolveRecord(): ?Client
    {
        // return Client::firstOrNew([
        //     // Update existing records, matching them by `$this->data['column_name']`
        //     'email' => $this->data['email'],
        // ]);

        return new Client();
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your client import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
