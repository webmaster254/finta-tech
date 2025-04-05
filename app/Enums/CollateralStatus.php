<?php
namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum CollateralStatus: string implements HasLabel,  HasColor
        {
            case Active = 'active';
            case Repossessed = 'repossessed';
            case Sold = 'sold';
            case Closed = 'closed';



            public function getLabel(): ?string
            {
                return $this->name;
            }
            public function getColor(): string | array | null
            {
                return match ($this) {
                    self::Pending => 'warning',
                    self::Repossessed => 'danger',
                    self::Active => 'success',
                    self::Sold => 'danger',
                    self::Closed => 'gray',
                };
            }

        }
