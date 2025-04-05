<?php
namespace App\Enums;
use Filament\Support\Contracts\HasLabel;

enum AmortizationMethod: string implements HasLabel
            {
                case EqualInstallments = 'equal_installments';
                case EqualPrincipalPayments = 'equal_principal_payments';
                
                
                public function getLabel(): ?string
                {
                    return match($this) {
                        AmortizationMethod::EqualInstallments => 'Equal Installments',
                        AmortizationMethod::EqualPrincipalPayments => 'Equal Principal Payments',
                    };
                }
            }