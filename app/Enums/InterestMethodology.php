<?php
namespace App\Enums;
use Filament\Support\Contracts\HasLabel;

enum InterestMethodology: string implements HasLabel
            {
                case Flat = 'flat';
                case DecliningBalance = 'declining_balance';
                
                
                public function getLabel(): ?string
                {
                    return $this->name;
                }
            }