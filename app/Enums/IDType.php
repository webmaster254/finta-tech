<?php
namespace App\Enums;
use Filament\Support\Contracts\HasLabel;

enum IDType: string implements HasLabel
            {
                case National_ID = 'National ID';
                case Passport = 'Passport';
                
                public function getLabel(): ?string
                {
                    return match($this) {
                        self::National_ID => 'National ID',
                        self::Passport => 'Passport',
                    };
                }
            }