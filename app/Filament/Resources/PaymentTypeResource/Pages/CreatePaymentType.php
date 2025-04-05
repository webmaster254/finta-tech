<?php

namespace App\Filament\Resources\PaymentTypeResource\Pages;

use Filament\Actions;
use App\Models\PaymentType;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\PaymentTypeResource;

class CreatePaymentType extends CreateRecord
{
    protected static string $resource = PaymentTypeResource::class;

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'payment Type Created successfully';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $activeSystem = PaymentType::where('is_system', true)->exists();
        if ($data['is_system'] && $activeSystem) {
            Notification::make()
                ->warning()
                ->title('System Default Payment Type already exists')
                ->body('Only one system payment type is allowed to be default')
                ->persistent()

                ->send();

            $this->halt();
        }


        return $data;
    }
}
