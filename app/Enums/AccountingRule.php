<?php
namespace App\Enums;
use Filament\Support\Contracts\HasLabel;

enum AccountingRule: string implements HasLabel
            {
                case None = 'none';
                case Cash = 'cash';
                // case AccrualPeriodic = 'accrual_periodic';
                // case AccrualUpfront = 'accrual_upfront';
                
                
                public function getLabel(): ?string
                {
                    return $this->name;
                }
            }