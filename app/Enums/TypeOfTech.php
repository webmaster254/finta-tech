<?php
namespace App\Enums;
use Filament\Support\Contracts\HasLabel;

enum TypeOfTech: string implements HasLabel
            {
                case Featured_Phone = 'Featured Phone';
                case Smartphone = 'Smartphone';
                
                public function getLabel(): ?string
                {
                    return match($this) {
                        self::Featured_Phone => 'Featured Phone',
                        self::Smartphone => 'Smartphone',
                    };
                }
            }