<?php

namespace App\Filament\Resources\LoanResource\Pages;

use Filament\Actions;
use Filament\Forms\Get;
use App\Models\Loan\LoanCharge;
use App\Models\Loan\LoanProduct;
use App\Models\Loan\LoanLinkedCharge;
use App\Filament\Resources\LoanResource;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Wizard\Step;
use Filament\Resources\Pages\CreateRecord;

class CreateLoan extends CreateRecord
{
    use CreateRecord\Concerns\HasWizard;
    protected static string $resource = LoanResource::class;
    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Loan Created successfully';
    }

    protected function getSteps(): array
    {
        return [
            Step::make('Loan Details')
                ->schema(LoanResource::getLoanInformation()),
            Step::make('Guarantors')
                ->schema(LoanResource::getGuarantorsInformation()),
            Step::make('Collateral')
                ->schema(LoanResource::getCollateralInformation())
                ->afterValidation(function (Step $component) {
                    $totalAmount = $this->data['principal'];
                     //check total guaranteed amount from guarantors array
                     $guarantors = $this->data['guarantors'];
                     $totalGuaranteedAmount = 0;
                     foreach ($guarantors as $guarantor) {
                         $totalGuaranteedAmount += $guarantor['guaranteed_amount'];
                     }
                     if ($totalGuaranteedAmount < $totalAmount) {
                         Notification::make()
                             ->warning()
                             ->title('The total guaranteed amount cannot be less than the principal amount')
                             ->body('Enter a value greater than or equal to '.$totalAmount)
                             ->persistent()
                             ->send();
                         $this->halt();
                     }
                }),
            Step::make('Files')
                ->schema(LoanResource::getFilesInformation()),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
{
    // $loanProduct = LoanProduct::find($data['loan_product_id']);
    // if ($loanProduct->maximum_principal < $data['principal']) {
    //     Notification::make()
    //         ->warning()
    //         ->title('The principal Amount cannot be greater than the maximum principal for this loan product')
    //         ->body('Enter a value less than or equal to '.$loanProduct->maximum_principal)
    //         ->persistent()

    //         ->send();

    //     $this->halt();
    // }
    $data['applied_amount'] = $data['principal'];

    return $data;
}


}
