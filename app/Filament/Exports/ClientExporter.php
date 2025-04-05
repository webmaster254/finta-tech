<?php

namespace App\Filament\Exports;

use App\Models\Client;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Models\Export;
use Filament\Actions\Exports\Enums\ExportFormat;

class ClientExporter extends Exporter
{
    protected static ?string $model = Client::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('title.name'),
            ExportColumn::make('loan_officer.fullname'),
            ExportColumn::make('fullname'),
            ExportColumn::make('account_number'),
            ExportColumn::make('gender'),
            ExportColumn::make('status')
            ->formatStateUsing(fn (Client $record) => $record->getStatus($record->id)),
            ExportColumn::make('marital_status'),
            ExportColumn::make('profession.name'),
            ExportColumn::make('mobile')
                 ->label('Phone Number'),
            ExportColumn::make('email'),
            ExportColumn::make('dob'),
            ExportColumn::make('address'),
            ExportColumn::make('city'),
            ExportColumn::make('state'),
            ExportColumn::make('notes'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your client export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }

    public function getFileName(Export $export): string
    {
        return "clients-{$export->getKey()}.csv";
    }

    public function getFormats(): array
    {
        return [
            ExportFormat::Csv,
        ];
    }
}
