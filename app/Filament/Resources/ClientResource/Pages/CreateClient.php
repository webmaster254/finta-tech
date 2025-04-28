<?php

namespace App\Filament\Resources\ClientResource\Pages;

use Filament\Forms\Get;
use Filament\Actions;
use Livewire\Component;
use App\Filament\Resources\ClientResource;
use Filament\Forms\Components\Wizard\Step;
use Filament\Resources\Pages\CreateRecord;
use Guava\FilamentDrafts\Admin\Resources\Pages\Create\Draftable;
use App\Filament\Resources\ClientResource\RelationManagers\LoansRelationManager;

class CreateClient extends CreateRecord
{
    use CreateRecord\Concerns\HasWizard;
    //use Draftable;
    protected static string $resource = ClientResource::class;

    /**
     * Retrieves the title for the created notification.
     *
     * @return string|null The title for the created notification.
     */
    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Client Created successfully';
    }
 
    protected function getSteps(): array
    {
        return [
            Step::make('Personal Details')
                ->schema(ClientResource::getPersonalInformation())
                ->afterValidation(function (Step $component) {
                    // Get the validated data from the component
                    $validated = $component->getChildComponentContainer()->validate();
                    
                    
                    // Extract the mobile number from the validated data
                    if (isset($validated['data']['mobile']) && !empty($validated['data']['mobile'])) {
                        // Hash the mobile number using SHA-256
                        $hashedMobile = hash('sha256', $validated['data']['mobile']);
                        
                        // Set the account_number field with the hashed value
                        $this->data['account_number'] = $hashedMobile;
                        //dd($this->data);  
                    }
                }),
            Step::make('Address Details')
                ->schema(ClientResource::getAddressInformation()),
            Step::make('Next of Kin Details')
                ->schema(ClientResource::getNextOfKinInformation()),
            Step::make('Spouse Details')
            ->visible(fn (\Filament\Forms\Get $get) => $get('marital_status') == 'married')
                ->schema(ClientResource::getSpouseInformation()),
            Step::make('Referees Details')
                ->schema(ClientResource::getRefereesInformation()),
            Step::make('Client Lead')
                ->schema(ClientResource::getClientLead()),
                Step::make('Privacy Policy')
                ->schema(ClientResource::getPrivacyPolicyInformation()),
            Step::make('Admin Details')
                ->schema(ClientResource::getAdminInformation()),
            // Step::make('Summary')
            //     ->schema(ClientResource::getClientDetailsSummary()),
        ];
    }

   
}

