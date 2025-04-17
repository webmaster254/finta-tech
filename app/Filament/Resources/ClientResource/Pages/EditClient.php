<?php

namespace App\Filament\Resources\ClientResource\Pages;

use App\Filament\Resources\ClientResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Guava\FilamentDrafts\Admin\Resources\Pages\Edit\Draftable;

class EditClient extends EditRecord
{
    //use Draftable;
    protected static string $resource = ClientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        //check if status is submitted and update to pending
        if($this->getRecord()->status == 'submitted') {
            $data['status'] = 'pending';
        }
        return $data;
    }

    protected function getUpdatedNotificationTitle(): ?string
    {
        return 'Client Updated successfully';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    public function hasCombinedRelationManagerTabsWithContent(): bool
{
    return true;
}
}
