<?php
namespace App\Enums;
use Filament\Support\Contracts\HasLabel;

enum InterestCalculationPeriodType: string implements HasLabel
            {
                case Daily = 'daily';
                case Same = 'same';
                
                
                public function getLabel(): ?string
                {
                    return $this->name;
                }
            }