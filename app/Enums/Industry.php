<?php
namespace App\Enums;
use Filament\Support\Contracts\HasLabel;

enum Industry: string implements HasLabel
            {
                case GENERAL_MERCHANDISE = 'General Merchandise';
                case FOOD_AND_BEVERAGES = 'Food and Beverages';
                case TRANSPORT_AND_COURIER = 'Transport and Courier';
                case TEXTILE_LEATHER_AND_FASHION = 'Textile, Leather and Fashion';
                case BEAUTY_AND_COSMETICS = 'Beauty and Cosmetics';
                case MINIRETAIL = 'Mini Retail';
                case BULDING_AND_CONSTRUCTION = 'Building and Construction';
                case MANUFACTURING = 'Manufacturing';
                case ICT = 'ICT';
                case ENTERTAINMENT = 'Entertainment';
                case ENERGY_AND_PETROLEUM = 'Energy and Petroleum';
                case ARTISANRY_AND_DRAFTSMANSHIP = 'Artisanry and Draftsmanship';
                case JUAKALI = 'Juakali';
                
                public function getLabel(): ?string
                {
                    return match($this) {
                        self::GENERAL_MERCHANDISE => 'General Merchandise',
                        self::FOOD_AND_BEVERAGES => 'Food and Beverages',
                        self::TRANSPORT_AND_COURIER => 'Transport and Courier',
                        self::TEXTILE_LEATHER_AND_FASHION => 'Textile, Leather and Fashion',
                        self::BEAUTY_AND_COSMETICS => 'Beauty and Cosmetics',
                        self::MINIRETAIL => 'Mini Retail',
                        self::BULDING_AND_CONSTRUCTION => 'Building and Construction',
                        self::MANUFACTURING => 'Manufacturing',
                        self::ICT => 'ICT',
                        self::ENTERTAINMENT => 'Entertainment',
                        self::ENERGY_AND_PETROLEUM => 'Energy and Petroleum',
                        self::ARTISANRY_AND_DRAFTSMANSHIP => 'Artisanry and Draftsmanship',
                        self::JUAKALI => 'Juakali',
                    };
                }
            }