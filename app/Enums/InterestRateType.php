<?php
namespace App\Enums;
use Filament\Support\Contracts\HasLabel;

enum InterestRateType: string implements HasLabel
            {
                case Day = 'day';
                case Week = 'week';
                case Month = 'month';
                case Year = 'year';
                
                
                public function getLabel(): ?string
                {
                    return $this->name;
                }
            }