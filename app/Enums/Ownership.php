<?php
namespace App\Enums;
use Filament\Support\Contracts\HasLabel;

enum Ownership: string implements HasLabel
            {
                case SOLE_PROPRIETORSHIP = 'Sole Proprietorship';
                case PARTNERSHIP = 'Partnership';
                case FAMILY_BUSINESS = 'Family Business';
                
                public function getLabel(): ?string
                {
                    return match($this) {
                        self::SOLE_PROPRIETORSHIP => 'Sole Proprietorship',
                        self::PARTNERSHIP => 'Partnership',
                        self::FAMILY_BUSINESS => 'Family Business',
                    };
                }
            }