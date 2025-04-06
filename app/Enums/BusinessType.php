<?php
namespace App\Enums;
use Filament\Support\Contracts\HasLabel;

enum BusinessType: string implements HasLabel
            {
                case SHOP = 'shop';
                case HOTEL = 'hotel';
                case KIOSK = 'kiosk';
                case GREEN_GROCER = 'green-grocer';
                case SALON = 'salon';
                case BODABODA = 'bodaboda';
                case BARBERSHOP = 'barbershop';
                
                public function getLabel(): ?string
                {
                    return match($this) {
                        self::SHOP => 'Shop',
                        self::HOTEL => 'Hotel',
                        self::KIOSK => 'Kiosk',
                        self::GREEN_GROCER => 'Green Grocer',
                        self::SALON => 'Salon',
                        self::BODABODA => 'Bodaboda',
                        self::BARBERSHOP => 'Barbershop',
                    };
                }
            }