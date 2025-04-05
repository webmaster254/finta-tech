<?php
namespace App\Enums;
use Filament\Support\Contracts\HasLabel;

enum SourceOfIncome: string implements HasLabel
            {
                
                case Employed = 'Employed';
                case Business = 'Business';
                
                public function getLabel(): ?string
                {
                    return match($this) {
                        self::Employed => 'Employed',
                        self::Business => 'Business',
                    };
                }
            }