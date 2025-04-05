<?php

namespace App\Filament\Resources\AssetResource\Pages;

use Filament\Actions;
use App\Models\BankAccount;
use App\Filament\Resources\AssetResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAsset extends CreateRecord
{
    protected static string $resource = AssetResource::class;

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Asset Created successfully';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }


     /**
     * Mutate form data before create.
     *
     * @param array $data
     * @return array
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $fundsAssetAccount = BankAccount::find($data['asset_type_id']);
        $fundsAssetAccount->balance += $data['purchase_price'];
        $fundsAssetAccount->save();
        return $data;
    }
}
