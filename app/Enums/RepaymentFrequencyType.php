<?php
namespace App\Enums;
use Filament\Support\Contracts\HasLabel;

enum RepaymentFrequencyType: string implements HasLabel
            {
                case Days = 'days';
                case Weeks = 'weeks';
                case Months = 'months';
                case Years = 'years';
                
                
                public function getLabel(): ?string
                {
                    return $this->name;
                }
            }